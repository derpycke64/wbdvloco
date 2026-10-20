<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'], $_SESSION['username'])) { header('Location: Login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];
if (empty($_SESSION['wallet_csrf'])) $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
$message = '';
$error = '';
$allowedAmounts = ['100.00', '250.00', '500.00', '1000.00'];

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function walletSandboxCardError(string $number, string $expiry, string $cvv): ?string {
    $digits = preg_replace('/\D+/', '', $number);
    if (!in_array($digits, ['4242424242424242', '4000000000000002'], true)) return 'Use a supported test card: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Do not enter real card details.';
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $expiry)) return 'Enter a valid future expiry month.';
    [$year, $month] = array_map('intval', explode('-', $expiry));
    $now = new DateTimeImmutable('first day of this month');
    $expires = DateTimeImmutable::createFromFormat('!Y-n', $year . '-' . $month);
    if (!$expires || $expires < $now) return 'The test card expiry date must be this month or later.';
    if (!preg_match('/^\d{3,4}$/', $cvv)) return 'Enter a 3- or 4-digit test CVV.';
    if ($digits === '4000000000000002') return 'The sandbox simulated a card decline. No balance was added.';
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $amount = (string)($_POST['amount'] ?? '');
    if (!hash_equals($_SESSION['wallet_csrf'], $csrf)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (!in_array($amount, $allowedAmounts, true)) {
        $error = 'Choose one of the available demo top-up amounts.';
    } else {
        $cardError = walletSandboxCardError((string)($_POST['card_number'] ?? ''), (string)($_POST['card_expiry'] ?? ''), (string)($_POST['card_cvv'] ?? ''));
        if ($cardError !== null) {
            $error = $cardError;
        } else {
            $value = (float)$amount;
            $reference = 'TOPUP-' . strtoupper(bin2hex(random_bytes(6)));
            try {
                $pdo->beginTransaction();
                $balanceStmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :user_id');
                $balanceStmt->execute([':amount' => $value, ':user_id' => $userId]);
                if ($balanceStmt->rowCount() !== 1) throw new RuntimeException('Could not update the wallet for this account.');
                $txStmt = $pdo->prepare(
                    "INSERT INTO transaction_history (user_id, transaction_type, payment_method, amount, status, reference_code, notes)
                     VALUES (:user_id, 'wallet_topup', 'sandbox_card', :amount, 'completed', :reference, 'Simulated sandbox card top-up; no real payment was processed')"
                );
                $txStmt->execute([':user_id' => $userId, ':amount' => $value, ':reference' => $reference]);
                $pdo->commit();
                $message = 'Sandbox payment approved. ₱' . number_format($value, 2) . ' was added to your LOCO wallet. Reference: ' . $reference . '. No real money was charged.';
                $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($e instanceof PDOException) error_log('LOCO sandbox wallet top-up database error: ' . $e->getMessage());
                $error = 'Wallet top-up failed. Make sure the transaction_history migration has been imported, then try again.';
            }
        }
    }
}

try {
    $stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $walletBalance = $stmt->fetchColumn();
    if ($walletBalance === false) throw new RuntimeException('User not found');
} catch (Throwable $e) {
    $walletBalance = null;
    $error = $error ?: 'Unable to load your wallet balance.';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>LOCO - Payment Sandbox</title><link rel="stylesheet" href="style.css">
<style>.sandbox-card-fields{display:grid;gap:12px;margin-top:18px}.sandbox-card-fields input,.sandbox-card-fields select{padding:12px;border-radius:5px;border:1px solid #777;background:#111;color:#fff;width:100%;box-sizing:border-box}.sandbox-note{font-size:.9rem;color:#aaa}</style></head>
<body class="loco-store-body"><?php include_once 'nav.php'; ?>
<main class="store-wrapper" style="max-width:760px;margin:40px auto;display:block"><section class="loco-panel" style="max-width:100%;padding:28px">
<p style="color:#aaa;letter-spacing:.12em;font-size:.75rem">LOCO PAYMENT SANDBOX</p><h1>Wallet balance</h1>
<p style="font-size:2rem;font-weight:700">₱<?= $walletBalance === null ? '—' : number_format((float)$walletBalance, 2) ?></p>
<p class="sandbox-note">This is a classroom payment simulation. Use only the test card numbers shown below. No real provider is connected, no real money is charged, and card details are never stored.</p>
<?php if ($message): ?><p role="status" style="padding:12px;background:#173522;border-radius:4px"><?=h($message)?></p><?php endif; ?>
<?php if ($error): ?><p role="alert" style="padding:12px;background:#422222;border-radius:4px"><?=h($error)?></p><?php endif; ?>
<form method="post" class="sandbox-card-fields"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['wallet_csrf'])?>">
<label for="amount">Choose wallet top-up amount</label><select id="amount" name="amount" required><option value="">Select an amount</option><?php foreach ($allowedAmounts as $option): ?><option value="<?=h($option)?>">₱<?=number_format((float)$option,2)?></option><?php endforeach; ?></select>
<h2>Simulated card details</h2><p class="sandbox-note">Approved: <strong>4242 4242 4242 4242</strong><br>Declined: <strong>4000 0000 0000 0002</strong><br>Use any future expiry month and a 3- or 4-digit CVV.</p>
<label for="card_number">Test card number</label><input id="card_number" name="card_number" inputmode="numeric" autocomplete="off" placeholder="4242 4242 4242 4242" maxlength="23" required>
<label for="card_expiry">Expiry month</label><input id="card_expiry" name="card_expiry" type="month" required>
<label for="card_cvv">Test CVV</label><input id="card_cvv" name="card_cvv" inputmode="numeric" autocomplete="off" placeholder="123" maxlength="4" required>
<button type="submit" class="loco-brand-gloss-btn" style="padding:12px">Simulate payment and add balance</button></form>
<p style="margin-top:22px"><a href="transaction_history.php">View transaction history →</a></p><p><a href="products.php">Return to store →</a></p>
</section></main></body></html>
