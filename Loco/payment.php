<?php
require_once __DIR__ . '/session.php';
require_once 'db.php';
if (!isset($_SESSION['user_id'], $_SESSION['username'])) { header('Location: login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['checkout_csrf'])) $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sandboxCardResult(string $result): ?string {
    if ($result === 'approved') return null;
    if ($result === 'declined') return 'Card declined. No charge was made.';
    return 'Card details incomplete. Use the listed test card numbers.';
}

$error = '';
$errorMethod = null;
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
        $errorMethod = $method;
        if (!in_array($method, ['wallet', 'sandbox_visa', 'sandbox_mastercard', 'sandbox_gcash', 'sandbox_paypal'], true)) {
            $error = 'Choose a supported payment method.';
        } elseif (in_array($method, ['sandbox_visa', 'sandbox_mastercard'], true)) {
            $error = sandboxCardResult((string)($_POST['demo_card_result'] ?? '')) ?? '';
        } elseif (in_array($method, ['sandbox_gcash','sandbox_paypal'], true) && (string)($_POST['demo_confirm'] ?? '') !== 'yes') {
            $error = 'Confirm the test payment to continue. No real account or funds are used.';
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
                if ($method === 'wallet' && $balance + 0.00001 < $total) throw new RuntimeException('Insufficient wallet balance. Balance: ₱' . number_format($balance, 2) . '; total: ₱' . number_format($total, 2) . '. Add funds to your wallet.');

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
                $txMethod = $method === 'wallet' ? 'wallet' : 'sandbox_card'; // Preserve the existing transaction schema; provider is distinguished in notes.
                $historyStmt = $pdo->prepare("INSERT INTO transaction_history (user_id, transaction_type, payment_method, amount, status, reference_code, items_json, notes) VALUES (?, 'purchase', ?, ?, 'completed', ?, ?, ?)");
                $historyStmt->execute([$userId, $txMethod, $total, $reference, json_encode(array_column($items, 'title'), JSON_UNESCAPED_UNICODE), $method === 'wallet' ? 'Paid using LOCO wallet' : 'Test '.ucfirst(str_replace('sandbox_','',$method)).' payment; no real payment was processed']);
                $pdo->commit();
                $_SESSION['cart'] = [];
                $_SESSION['wallet_balance'] = number_format($method === 'wallet' ? $balance - $total : $balance, 2, '.', '');
                $receipt = ['reference' => $reference, 'total' => $total, 'items' => $items, 'method' => $method];
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
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOCO | Checkout</title><link rel="stylesheet" href="style.css"></head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="store-wrapper loco-sandbox-page" style="display:block">
 <div class="loco-sandbox-step">LOCO STORE CHECKOUT <span style="float:right">PAYMENT METHOD › REVIEW › RECEIPT</span></div>
 <?php if($receipt!==null): ?>
  <section style="padding:22px">
   <h1 class="loco-sandbox-heading">Purchase Complete</h1>
   <div class="loco-checkout-receipt loco-provider-card <?=h($receipt['method']==='sandbox_paypal'?'paypal':'')?>">
    <div class="receipt-brand"><span><?=h($receipt['method']==='sandbox_gcash'?'GCash':($receipt['method']==='sandbox_paypal'?'PayPal':($receipt['method']==='sandbox_visa'?'Visa':($receipt['method']==='sandbox_mastercard'?'Mastercard':'LOCO'))))?></span><small>LOCO payment receipt</small></div>
    <div class="loco-payment-warning"><strong>Test payment</strong><span>No real account or funds were used. This receipt is for a LOCO test purchase.</span></div>
    <div class="receipt-content">
      <div style="display:flex;align-items:center;gap:12px;margin:0 0 14px">
        <img src="profile_avatar.php?id=<?=$userId?>" alt="LOCO player avatar" style="width:44px;height:44px;object-fit:cover;border:1px solid #b6bec8">
        <div><strong style="display:block"><?=h($_SESSION['username'])?></strong><small>LOCO player</small></div>
      </div>
      <p style="font-weight:800;font-size:1.2rem;margin:3px 0">₱<?=number_format((float)$receipt['total'],2)?></p>
      <div class="loco-receipt-row"><span>Merchant</span><strong>LOCO / Stoker Studios</strong></div>
      <div class="loco-receipt-row"><span>Player</span><strong><?=h($_SESSION['username'])?></strong></div>
      <div class="loco-receipt-row"><span>Payment</span><strong><?=h(['wallet'=>'LOCO Wallet','sandbox_card'=>'Test Card','sandbox_visa'=>'Visa (Test)','sandbox_mastercard'=>'Mastercard (Test)','sandbox_gcash'=>'GCash (Test)','sandbox_paypal'=>'PayPal (Test)'][$receipt['method']]??$receipt['method'])?></strong></div>
      <div class="loco-receipt-row"><span>Reference</span><strong><?=h($receipt['reference'])?></strong></div>
      <div class="loco-receipt-row"><span>Status</span><strong>Completed</strong></div>
      <?php foreach($receipt['items'] as $item): ?><div class="loco-receipt-row"><span><?=h($item['title'])?></span><strong>₱<?=number_format((float)$item['price'],2)?></strong></div><?php endforeach; ?>
      <p style="font-size:.8rem">Your games have been added to your LOCO Library.</p>
    </div>
   </div>
   <p><a class="loco-wishlist-action" href="profile.php#library">VIEW LIBRARY</a>　<a class="loco-wishlist-remove" href="transaction_history.php">Transaction History</a>　<a href="products.php">Back to Store</a></p>
  </section>
 <?php else: ?>
  <div class="loco-sandbox-layout" style="padding:20px">
   <section class="loco-sandbox-well">
    <h1 class="loco-sandbox-heading">PAYMENT METHOD</h1>
    <?php if($error): ?><p class="loco-notice error" role="alert" id="checkout-error" data-payment-method="<?=h($errorMethod ?? '')?>"><?=h($error)?></p><?php endif; ?>
    <?php if(!$cartGames): ?>
      <p>Your cart is empty. <a href="products.php">Browse the Store</a>.</p>
    <?php else: ?>
      <div class="loco-cart-summary" style="margin-bottom:18px">
       <?php foreach($_SESSION['cart'] as $entry): $id=(int)$entry['game_id'];if(!isset($cartGames[$id]))continue;$g=$cartGames[$id]; ?>
       <div class="loco-cart-item-box" style="border-radius:0">
        <div class="loco-cart-item-details"><img class="loco-cart-thumb" src="<?=h($g['image_url']?:'images/gameplay_01.jpg')?>" alt="<?=h($g['title'])?>"><div class="loco-cart-text-meta"><h4><?=h($g['title'])?></h4><span class="loco-cart-device-tag">For my LOCO account</span></div></div>
        <div class="loco-cart-price-side"><span class="loco-cart-item-cost"><?=(float)$g['price']>0?'₱'.number_format((float)$g['price'],2):'Free'?></span>
         <form method="post" action="payment.php"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['checkout_csrf'])?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="game_id" value="<?=$id?>"><button class="loco-cart-remove-link" type="submit">Remove</button></form>
        </div>
       </div>
       <?php endforeach; ?>
      </div>
      <form method="post" id="checkout-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?=h($_SESSION['checkout_csrf'])?>">
        <input type="hidden" name="action" value="checkout">
        <label class="loco-sandbox-label" for="payment-method">Please select a payment method</label>
        <select id="payment-method" name="payment_method" class="loco-sandbox-choice" required>
          <option value="wallet" <?=($_POST['payment_method']??'wallet')==='wallet'?'selected':''?>>LOCO Wallet</option>
          <option value="sandbox_visa" <?=($_POST['payment_method']??'wallet')==='sandbox_visa'?'selected':''?>>Visa (Test)</option>
          <option value="sandbox_mastercard" <?=($_POST['payment_method']??'wallet')==='sandbox_mastercard'?'selected':''?>>Mastercard (Test)</option>
          <option value="sandbox_gcash" <?=($_POST['payment_method']??'wallet')==='sandbox_gcash'?'selected':''?>>GCash (Test)</option>
          <option value="sandbox_paypal" <?=($_POST['payment_method']??'wallet')==='sandbox_paypal'?'selected':''?>>PayPal (Test)</option>
        </select>
        <p id="wallet-summary" style="font-size:.8rem;color:#c5cdd8">Wallet balance: <strong><?=$walletBalance===false||$walletBalance===null?'Unavailable':'₱'.number_format((float)$walletBalance,2)?></strong>　<a href="wallet.php">Add Funds</a></p>
        <section id="card-panel" class="loco-sandbox-well" style="margin-top:14px" hidden>
          <h2 class="loco-sandbox-heading" id="checkout-card-title">VISA CARD INFORMATION</h2>
          <p class="loco-sandbox-label" id="checkout-card-instructions">Use Visa test numbers only: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Never enter real card details.</p>
          <div style="display:grid;gap:9px">
            <label class="loco-sandbox-label" for="checkout-card-number">Test card number</label>
            <input class="loco-sandbox-field" id="checkout-card-number" inputmode="numeric" maxlength="23" placeholder="4242 4242 4242 4242" autocomplete="off">
            <label class="loco-sandbox-label" for="checkout-card-expiry">Expiration month</label>
            <input class="loco-sandbox-field" id="checkout-card-expiry" type="month" autocomplete="off">
            <label class="loco-sandbox-label" for="checkout-card-cvv">Test security code</label>
            <input class="loco-sandbox-field" id="checkout-card-cvv" inputmode="numeric" maxlength="4" placeholder="123" autocomplete="off">
          </div>
          <input type="hidden" name="demo_card_result" id="checkout-card-result" value="">
          <p id="checkout-card-help" class="loco-sandbox-label" style="margin-top:12px">Complete the card details to continue. Test payments only; no real money is charged.</p>
          <p id="checkout-card-message" class="loco-notice error" role="alert" hidden></p>
        </section>
        <section id="provider-panel" class="loco-provider-stage" style="display:none" aria-label="Test payment provider">
          <div class="loco-checkout-receipt loco-provider-card" id="provider-card">
            <div class="receipt-brand">
              <span id="provider-brand" class="loco-provider-wordmark">GCash</span>
              <small>LOCO checkout</small>
            </div>
            <div class="loco-payment-warning"><strong>Test payment</strong><span>No real PayPal or GCash account, OTP, wallet, or funds are used.</span></div>
            <div class="receipt-content">
              <div class="loco-provider-amount">
                <span>Amount due</span>
                <strong>PHP <?=number_format($total,2)?></strong>
              </div>
              <div class="loco-provider-details">
                <div class="loco-provider-payer">
                  <img src="profile_avatar.php?id=<?=$userId?>" alt="Your LOCO profile picture">
                  <div><strong><?=h($_SESSION['username'])?></strong><span>LOCO player account</span></div>
                </div>
                <div class="loco-provider-detail-row"><span>Merchant</span><strong>LOCO / STOKER STUDIOS</strong></div>
                <div class="loco-provider-detail-row"><span>Player account</span><strong><?=h($_SESSION['username'])?></strong></div>
                <div class="loco-provider-detail-row"><span>Reference</span><strong>LOCO-SIM-<?=str_pad((string)$userId,4,'0',STR_PAD_LEFT)?></strong></div>
                <div class="loco-provider-detail-row"><span>Status</span><strong>Pending</strong></div>
              </div>
              <p class="loco-provider-explanation">Your LOCO account details were filled automatically. This checkout is for testing only. No real money is sent.</p>
              <input type="hidden" name="demo_confirm" value="yes">
              <button type="submit" class="loco-provider-confirm">Confirm test payment</button>
              <button type="button" class="loco-provider-cancel" id="provider-cancel">Cancel and return to merchant</button>
              <p class="loco-provider-footer-note">Test checkout hosted by LOCO / Stoker Studios</p>
            </div>
          </div>
        </section>
        <p class="loco-sandbox-label" style="margin:18px 0">You can review your order above. No real payment information is stored.</p>
        <button type="submit" class="loco-brand-gloss-btn" style="min-width:200px;border-radius:0">CONFIRM ₱<?=number_format($total,2)?></button>
        <a href="products.php" class="loco-cart-secondary-btn" style="margin-left:12px">Continue Shopping</a>
      </form>
    <?php endif; ?>
   </section>
   <aside class="loco-sandbox-sidebar">
    <h3>PAYMENT METHODS</h3>
    <p>Card, GCash, and PayPal are available for testing. No real charges are made.</p>
    <p><span class="loco-pay-demo-badge">VISA</span> <span class="loco-pay-demo-badge">Mastercard</span></p>
    <p><span class="loco-pay-demo-badge">GCash (Test)</span> <span class="loco-pay-demo-badge">PayPal (Test)</span></p>
    <h3 style="margin-top:26px">ORDER TOTAL</h3>
    <p style="font-size:1.4rem;color:#84e28c;font-weight:800">₱<?=number_format($total,2)?></p>
    <p>All game prices come from the LOCO database. Completed purchases are recorded in your account's library.</p>
    <a href="wallet.php" class="loco-wishlist-action" style="display:inline-block">ADD WALLET FUNDS</a>
   </aside>
  </div>
 <?php endif; ?>
</main>
<script>
(function(){
 const method=document.getElementById('payment-method');
 if(!method)return;
 const card=document.getElementById('card-panel'),provider=document.getElementById('provider-panel'),wallet=document.getElementById('wallet-summary');
 function update(){
  const m=method.value,isCard=(m==='sandbox_visa'||m==='sandbox_mastercard'),isProvider=(m==='sandbox_gcash'||m==='sandbox_paypal');
  card.hidden=!isCard;
  const mastercard=m==='sandbox_mastercard';
  document.getElementById('checkout-card-title').textContent=mastercard?'MASTERCARD INFORMATION':'VISA CARD INFORMATION';
  document.getElementById('checkout-card-instructions').textContent=mastercard
   ?'Use Mastercard test numbers only: 5555 5555 5555 4444 (approved) or 5105 1051 0510 5100 (declined). Never enter real card details.'
   :'Use Visa test numbers only: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Never enter real card details.';
  document.getElementById('checkout-card-number').placeholder=mastercard?'5555 5555 5555 4444':'4242 4242 4242 4242';
  provider.style.display=isProvider?'block':'none';
  provider.classList.toggle('paypal',m==='sandbox_paypal');
  document.getElementById('provider-brand').textContent=m==='sandbox_paypal'?'PayPal':'GCash';
  document.getElementById('provider-card').classList.toggle('paypal',m==='sandbox_paypal');
  document.querySelector('.loco-sandbox-page').classList.toggle('loco-provider-mode',isProvider);
  document.body.classList.toggle('loco-provider-screen',isProvider);
  if(wallet)wallet.hidden=m!=='wallet';
  const serverError=document.getElementById('checkout-error');
  if(serverError && serverError.dataset.paymentMethod && serverError.dataset.paymentMethod!==m)serverError.hidden=true;
  if(!isCard)document.getElementById('checkout-card-message').hidden=true;
 }
 const form=document.getElementById('checkout-form');
 const cardNumber=document.getElementById('checkout-card-number');
 const cardExpiry=document.getElementById('checkout-card-expiry');
 const cardCvv=document.getElementById('checkout-card-cvv');
 const cardResult=document.getElementById('checkout-card-result');
 const cardMessage=document.getElementById('checkout-card-message');
 function cardError(message,field){
  cardMessage.textContent=message;
  cardMessage.hidden=false;
  if(field){field.setCustomValidity(message);field.reportValidity();}
 }
 [cardNumber,cardExpiry,cardCvv].forEach(function(field){
  field.addEventListener('input',function(){field.setCustomValidity('');cardMessage.hidden=true;});
  field.addEventListener('change',function(){field.setCustomValidity('');cardMessage.hidden=true;});
 });
 form.addEventListener('submit',function(event){
  if(method.value!=='sandbox_visa'&&method.value!=='sandbox_mastercard')return;
  const digits=cardNumber.value.replace(/\D/g,'');
  const isMastercard=method.value==='sandbox_mastercard';
  const approved=isMastercard?'5555555555554444':'4242424242424242';
  const declined=isMastercard?'5105105105105100':'4000000000000002';
  if(!digits || !cardExpiry.value || !cardCvv.value){
   event.preventDefault();cardError('Card details incomplete. Enter the test card number, expiration month, and security code.');return;
  }
  if(digits!==approved && digits!==declined){
   event.preventDefault();cardError('Use one of the listed test card numbers.',cardNumber);return;
  }
  const now=new Date();
  const thisMonth=now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0');
  if(!/^\d{4}-(0[1-9]|1[0-2])$/.test(cardExpiry.value)||cardExpiry.value<thisMonth){
   event.preventDefault();cardError('Choose the current month or a future expiration month.',cardExpiry);return;
  }
  if(!/^\d{3,4}$/.test(cardCvv.value)){
   event.preventDefault();cardError('Enter a 3- or 4-digit test security code.',cardCvv);return;
  }
  // Only a test outcome is submitted, never the card-style field values.
  cardResult.value=digits===approved?'approved':'declined';
 });
 const cancel=document.getElementById('provider-cancel');
 cancel.addEventListener('click',function(){method.value='wallet';update();method.focus();});
 method.addEventListener('change',update);update();
})();
</script>
</body></html>
