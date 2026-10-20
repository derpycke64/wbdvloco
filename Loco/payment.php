<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'], $_SESSION['username'])) { header('Location: Login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['checkout_csrf'])) $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sandboxCardResult(string $number, string $expiry, string $cvv): ?string {
    $digits = preg_replace('/\D+/', '', $number);
    if (!in_array($digits, ['4242424242424242', '4000000000000002'], true)) {
        return 'Use a supported test card: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Do not enter real card details.';
    }
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $expiry)) return 'Enter a valid future expiry month.';
    [$year, $month] = array_map('intval', explode('-', $expiry));
    $now = new DateTimeImmutable('first day of this month');
    $expires = DateTimeImmutable::createFromFormat('!Y-n', $year . '-' . $month);
    if (!$expires || $expires < $now) return 'The test card expiry date must be this month or later.';
    if (!preg_match('/^\d{3,4}$/', $cvv)) return 'Enter a 3- or 4-digit test CVV.';
    if ($digits === '4000000000000002') return 'The sandbox simulated a card decline. No charge was made.';
    return null;
}

$error = '';
$receipt = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['checkout_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session check expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'remove') {
        $removeId = (int)($_POST['game_id'] ?? 0);
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => (int)($i['game_id'] ?? 0) !== $removeId));
        header('Location: payment.php'); exit();
    } elseif (($_POST['action'] ?? '') === 'checkout') {
        $method = (string)($_POST['payment_method'] ?? 'wallet');
        if (!in_array($method, ['wallet', 'sandbox_card'], true)) {
            $error = 'Choose a supported payment method.';
        } elseif ($method === 'sandbox_card') {
            $error = sandboxCardResult((string)($_POST['card_number'] ?? ''), (string)($_POST['card_expiry'] ?? ''), (string)($_POST['card_cvv'] ?? '')) ?? '';
        }
        if ($error === '') {
            try {
                $ids = array_values(array_unique(array_map(fn($i) => (int)($i['game_id'] ?? 0), $_SESSION['cart'])));
                if (!$ids || in_array(0, $ids, true)) throw new RuntimeException('Your cart is empty or contains an invalid item.');
                $pdo->beginTransaction();
                $accountStmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
                $accountStmt->execute([$userId]);
                $account = $accountStmt->fetch();
                if (!$account) throw new RuntimeException('Your account could not be found.');

                $marks = implode(',', array_fill(0, count($ids), '?'));
                $gameStmt = $pdo->prepare("SELECT id, title, price FROM games WHERE id IN ($marks) ORDER BY id FOR UPDATE");
                $gameStmt->execute($ids);
                $games = [];
                foreach ($gameStmt->fetchAll() as $g) $games[(int)$g['id']] = $g;
                if (count($games) !== count($ids)) throw new RuntimeException('A game in your cart is no longer available.');

                $ownedStmt = $pdo->prepare('SELECT id FROM library WHERE user_id = ? AND game_id = ? LIMIT 1');
                $items = []; $total = 0.0;
                foreach ($ids as $id) {
                    $ownedStmt->execute([$userId, $id]);
                    if ($ownedStmt->fetch()) throw new RuntimeException('You already own ' . $games[$id]['title'] . '. Remove it from your cart.');
                    $price = (float)$games[$id]['price'];
                    if ($price < 0) throw new RuntimeException('A game has an invalid price.');
                    $total += $price;
                    $items[] = ['game_id' => $id, 'title' => $games[$id]['title'], 'price' => number_format($price, 2, '.', '')];
                }
                $total = round($total, 2);
                $balance = (float)$account['wallet_balance'];
                if ($method === 'wallet' && $balance + 0.00001 < $total) throw new RuntimeException('Insufficient wallet balance. Balance: ₱' . number_format($balance, 2) . '; total: ₱' . number_format($total, 2) . '. Add funds to your wallet or choose the payment sandbox.');

                $reference = 'LOCO-' . strtoupper(bin2hex(random_bytes(8)));
                $orderMethod = $method === 'wallet' ? 'wallet' : 'external';
                $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, reference_code, subtotal, total_amount, payment_method, status, completed_at) VALUES (?, ?, ?, ?, ?, \'completed\', CURRENT_TIMESTAMP)');
                $orderStmt->execute([$userId, $reference, $total, $total, $orderMethod]);
                $orderId = (int)$pdo->lastInsertId();
                $lineStmt = $pdo->prepare('INSERT INTO order_items (order_id, game_id, game_title, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, 1, ?)');
                $libraryStmt = $pdo->prepare('INSERT INTO library (user_id, game_id) VALUES (?, ?)');
                foreach ($items as $item) {
                    $lineStmt->execute([$orderId, $item['game_id'], $item['title'], $item['price'], $item['price']]);
                    $libraryStmt->execute([$userId, $item['game_id']]);
                }
                if ($method === 'wallet' && $total > 0) {
                    $deductStmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?');
                    $deductStmt->execute([$total, $userId, $total]);
                    if ($deductStmt->rowCount() !== 1) throw new RuntimeException('Your wallet balance changed. Please try again.');
                }
                $txMethod = $method === 'wallet' ? 'wallet' : 'sandbox_card';
                $historyStmt = $pdo->prepare("INSERT INTO transaction_history (user_id, transaction_type, payment_method, amount, status, reference_code, items_json, notes) VALUES (?, 'purchase', ?, ?, 'completed', ?, ?, ?)");
                $historyStmt->execute([$userId, $txMethod, $total, $reference, json_encode(array_column($items, 'title'), JSON_UNESCAPED_UNICODE), $method === 'wallet' ? 'Paid using LOCO wallet' : 'Simulated sandbox card payment; no real payment was processed']);
                $pdo->commit();
                $_SESSION['cart'] = [];
                $_SESSION['wallet_balance'] = number_format($method === 'wallet' ? $balance - $total : $balance, 2, '.', '');
                $receipt = ['reference' => $reference, 'total' => $total, 'items' => $items, 'method' => $txMethod];
                $_SESSION['last_checkout_receipt'] = $receipt;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($e instanceof PDOException) {
                    error_log('LOCO checkout database error: ' . $e->getMessage());
                    $error = 'Checkout could not be completed. Ensure all database migrations are imported, then try again.';
                } elseif ($e instanceof RuntimeException) {
                    $error = $e->getMessage();
                } else {
                    error_log('LOCO checkout failed: ' . $e->getMessage());
                    $error = 'Checkout could not be completed. Please try again.';
                }
            }
        }
    }
}
if ($receipt === null && isset($_SESSION['last_checkout_receipt']) && isset($_GET['receipt'])) $receipt = $_SESSION['last_checkout_receipt'];

$cartIds = array_values(array_unique(array_map(fn($i) => (int)($i['game_id'] ?? 0), $_SESSION['cart'])));
$cartIds = array_values(array_filter($cartIds, fn($id) => $id > 0));
$cartGames = [];
if ($cartIds) {
    $marks = implode(',', array_fill(0, count($cartIds), '?'));
    $stmt = $pdo->prepare("SELECT id, title, price, image_url FROM games WHERE id IN ($marks)");
    $stmt->execute($cartIds);
    foreach ($stmt->fetchAll() as $g) $cartGames[(int)$g['id']] = $g;
    $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($i) => isset($cartGames[(int)($i['game_id'] ?? 0)])));
}
$total = 0.0;
foreach ($_SESSION['cart'] as $i) if (isset($cartGames[(int)$i['game_id']])) $total += (float)$cartGames[(int)$i['game_id']]['price'];
$walletBalance = null;
try {
    $balanceStmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $balanceStmt->execute([$userId]);
    $walletBalance = $balanceStmt->fetchColumn();
} catch (Throwable $e) { error_log('LOCO checkout wallet balance read failed: ' . $e->getMessage()); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>LOCO - Checkout</title><link rel="stylesheet" href="style.css">
<style>.sandbox-card-fields{display:grid;gap:12px;padding:16px;margin:16px 0;border:1px solid #555;border-radius:8px}.sandbox-card-fields[hidden]{display:none}.sandbox-card-fields input{padding:11px;border-radius:5px;border:1px solid #777;background:#111;color:#fff;width:100%;box-sizing:border-box}.sandbox-note{font-size:.9rem;color:#aaa}.payment-choice{display:flex;gap:10px;align-items:center;margin:12px 0}</style></head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<div class="store-wrapper">
<?php if ($receipt !== null): ?>
 <main class="loco-store-main-col"><div class="loco-receipt-card"><h2>Purchase Complete!</h2><p>Your payment was recorded and the games were added to your library.</p><p><strong>Payment method:</strong> <?=h($receipt['method'] === 'wallet' ? 'LOCO Wallet' : 'LOCO Payment Sandbox (test card)')?></p><p><strong>Reference:</strong> <?=h($receipt['reference'])?></p><p><strong>Total paid:</strong> ₱<?=number_format((float)$receipt['total'],2)?></p><ul><?php foreach ($receipt['items'] as $i): ?><li><?=h($i['title'])?> — ₱<?=number_format((float)$i['price'],2)?></li><?php endforeach; ?></ul><a href="profile.php#library" class="loco-success-btn-override">View My Library</a> <a href="transaction_history.php" class="loco-cart-secondary-btn">Transaction History</a></div></main>
<?php else: ?>
 <main class="loco-store-main-col"><h1 class="loco-logo-text" style="font-size:1.6rem">Your Shopping Cart (<?=count($_SESSION['cart'])?>)</h1>
 <?php if ($error): ?><p class="loco-alert-error" role="alert"><?=h($error)?></p><?php endif; ?>
 <?php if (!$cartGames): ?><div class="loco-cart-item-box" style="padding:30px;justify-content:center"><p>Your shopping cart is empty.</p></div><a href="products.php" class="loco-cart-secondary-btn">Continue Shopping</a>
 <?php else: ?>
 <div style="display:flex;flex-direction:column;gap:10px"><?php foreach ($_SESSION['cart'] as $i): $id=(int)$i['game_id']; if (!isset($cartGames[$id])) continue; $g=$cartGames[$id]; ?>
 <div class="loco-cart-item-box"><div class="loco-cart-item-details"><img src="<?=h($g['image_url'] ?: 'images/gameplay_01.jpg')?>" class="loco-cart-thumb" alt="<?=h($g['title'])?>"><div class="loco-cart-text-meta"><h4><?=h($g['title'])?></h4><span class="loco-cart-device-tag">For my account</span></div></div><div class="loco-cart-price-side"><span class="loco-cart-item-cost"><?=$g['price'] <= 0 ? 'Free' : '₱'.number_format((float)$g['price'],2)?></span><form method="post"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['checkout_csrf'])?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="game_id" value="<?=$id?>"><button class="loco-cart-remove-link" type="submit">Remove</button></form></div></div>
 <?php endforeach; ?></div>
 <p>Wallet balance: <strong><?=$walletBalance === false || $walletBalance === null ? 'Unavailable' : '₱'.number_format((float)$walletBalance,2)?></strong></p>
 <form method="post" id="checkout-form"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['checkout_csrf'])?>"><input type="hidden" name="action" value="checkout">
 <label class="payment-choice"><input type="radio" name="payment_method" value="wallet" checked> Pay with LOCO wallet</label>
 <label class="payment-choice"><input type="radio" name="payment_method" value="sandbox_card"> Pay with Payment Sandbox (test card)</label>
 <section class="sandbox-card-fields" id="sandbox-card-fields" hidden><h3>LOCO Payment Sandbox</h3><p class="sandbox-note">Test simulation only. Do not enter real card details. Approved test card: <strong>4242 4242 4242 4242</strong>. Declined test card: <strong>4000 0000 0000 0002</strong>.</p>
 <label for="card_number">Test card number</label><input id="card_number" name="card_number" inputmode="numeric" autocomplete="off" placeholder="4242 4242 4242 4242" maxlength="23">
 <label for="card_expiry">Expiry month</label><input id="card_expiry" name="card_expiry" type="month">
 <label for="card_cvv">Test CVV</label><input id="card_cvv" name="card_cvv" inputmode="numeric" autocomplete="off" placeholder="123" maxlength="4">
 </section>
 <p class="sandbox-note">The sandbox simulates card approval or decline. It does not contact PayPal or any real payment provider, and card details are not stored.</p>
 <button type="submit" class="loco-brand-gloss-btn" onclick="return confirm('Continue with the selected payment method for ₱<?=number_format($total,2,'.','')?>?')">Pay ₱<?=number_format($total,2)?></button></form>
 <a href="wallet.php" class="loco-cart-secondary-btn">Add funds to wallet</a> <a href="products.php" class="loco-cart-secondary-btn">Continue Shopping</a>
 <?php endif; ?></main>
 <aside class="loco-store-sidebar-col"><div class="sidebar-panel" style="padding:15px"><div style="display:flex;justify-content:space-between"><span>Estimated total</span><strong>₱<?=number_format($total,2)?></strong></div><p>Prices are retrieved from the database.</p></div></aside>
<?php endif; ?>
</div><footer class="loco-footer-bar"><div class="loco-sub-footer-inner"><div class="loco-footer-brand-col"><span class="loco-footer-logo-text">LOCO</span><span class="loco-footer-logo-sub">Stoker Studios</span><p class="loco-copyright-text">© 2026 Stoker Studios. All rights reserved.</p></div></div></footer>
<script>const radios=document.querySelectorAll('input[name="payment_method"]');const fields=document.getElementById('sandbox-card-fields');function toggleCardFields(){if(!fields)return;const card=document.querySelector('input[name="payment_method"]:checked')?.value==='sandbox_card';fields.hidden=!card;fields.querySelectorAll('input').forEach(input=>input.required=card);}radios.forEach(r=>r.addEventListener('change',toggleCardFields));toggleCardFields();</script></body></html>
