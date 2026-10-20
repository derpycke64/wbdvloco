<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'], $_SESSION['username'])) { header('Location: Login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];
if (empty($_SESSION['wallet_csrf'])) $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
$message = (string)($_SESSION['wallet_notice'] ?? '');
unset($_SESSION['wallet_notice']);
$receipt = isset($_GET['receipt']) ? ($_SESSION['last_wallet_receipt'] ?? null) : null;
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
        $method=(string)($_POST['payment_method']??'sandbox_card');
        $paymentError=null;
        if(!in_array($method,['sandbox_card','sandbox_gcash','sandbox_paypal'],true))$paymentError='Choose a supported demo payment method.';
        elseif($method==='sandbox_card')$paymentError=walletSandboxCardError((string)($_POST['card_number'] ?? ''), (string)($_POST['card_expiry'] ?? ''), (string)($_POST['card_cvv'] ?? ''));
        elseif((string)($_POST['demo_confirm']??'')!=='yes')$paymentError='Confirm that this is a simulated provider payment.';
        if ($paymentError !== null) {
            $error = $paymentError;
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
                     VALUES (:user_id, 'wallet_topup', 'sandbox_card', :amount, 'completed', :reference, :notes)"
                );
                $txStmt->execute([':user_id' => $userId, ':amount' => $value, ':reference' => $reference, ':notes'=>'Simulated '.str_replace('sandbox_','',$method).' wallet top-up; no real payment processed']);
                $pdo->commit();
                $_SESSION['wallet_notice']='Simulated top-up complete. ₱'.number_format($value,2).' added to your LOCO wallet.';
                $_SESSION['last_wallet_receipt']=['reference'=>$reference,'amount'=>$value,'method'=>$method];
                $_SESSION['wallet_csrf']=bin2hex(random_bytes(32));
                header('Location: wallet.php?receipt=1');
                exit;
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
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOCO | Add Wallet Funds</title><link rel="stylesheet" href="style.css"></head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="store-wrapper loco-sandbox-page" style="display:block">
  <div class="loco-sandbox-step">LOCO WALLET　›　 ADD FUNDS <span style="float:right">PAYMENT SIMULATION</span></div>
  <?php if($message): ?><p role="status" class="loco-notice success" style="margin:12px"><?=h($message)?></p><?php endif; ?>
  <?php if($error): ?><p role="alert" class="loco-notice error" style="margin:12px"><?=h($error)?></p><?php endif; ?>
  <?php if($receipt): ?>
    <div class="loco-checkout-receipt <?=($receipt['method']==='sandbox_paypal')?'paypal':''?>">
      <div class="receipt-brand"><?=h($receipt['method']==='sandbox_gcash'?'GCash · DEMO':($receipt['method']==='sandbox_paypal'?'PayPal · DEMO':'LOCO PAYMENT RECEIPT'))?></div>
      <div class="loco-payment-warning">PAYMENT SIMULATION — NO REAL ACCOUNT OR FUNDS USED</div>
      <div class="receipt-content">
        <div style="display:flex;gap:12px;align-items:center">
          <img src="profile_avatar.php?id=<?=$userId?>" alt="Player avatar" style="width:44px;height:44px;object-fit:cover;border:1px solid #cbd0dc">
          <div><strong><?=h($_SESSION['username'])?></strong><p style="font-size:.72rem;margin:3px 0">Demo payer</p></div>
        </div>
        <p style="font-size:.74rem;margin:13px 0 3px">AMOUNT ADDED TO LOCO WALLET</p><h2 style="font-size:1.75rem;margin:3px 0 17px">₱<?=number_format((float)$receipt['amount'],2)?></h2>
        <div class="loco-receipt-row"><span>Merchant</span><strong>LOCO / Stoker Studios</strong></div>
        <div class="loco-receipt-row"><span>Payment</span><strong><?=h(['sandbox_card'=>'Demo Card','sandbox_gcash'=>'GCash Simulation','sandbox_paypal'=>'PayPal Simulation'][$receipt['method']]??'Demo')?></strong></div>
        <div class="loco-receipt-row"><span>Reference</span><strong><?=h($receipt['reference'])?></strong></div>
        <div class="loco-receipt-row"><span>Status</span><strong>Completed (Demo)</strong></div>
      </div>
    </div>
    <p style="padding:12px 20px"><a href="wallet.php" class="loco-wishlist-action">ADD MORE FUNDS</a>　<a href="Products.php">RETURN TO STORE</a>　<a href="transaction_history.php">Transaction History</a></p>
  <?php else: ?>
  <div class="loco-sandbox-layout" style="padding:20px">
    <section class="loco-sandbox-well">
      <h1 class="loco-sandbox-heading">ADD WALLET FUNDS</h1>
      <p style="font-size:.83rem">Current wallet balance: <strong style="color:#82d784">₱<?=$walletBalance===null?'—':number_format((float)$walletBalance,2)?></strong></p>
      <form method="post" id="wallet-topup-form" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?=h($_SESSION['wallet_csrf'])?>">
        <label class="loco-sandbox-label" for="amount">Choose amount</label>
        <select id="amount" name="amount" class="loco-sandbox-choice" required>
          <option value="">Select a top-up amount</option>
          <?php foreach($allowedAmounts as $option): ?><option value="<?=h($option)?>" <?=($_POST['amount']??'')===$option?'selected':''?>>₱<?=number_format((float)$option,2)?></option><?php endforeach; ?>
        </select>
        <p class="loco-sandbox-label" style="margin-top:16px">Please select a payment method</p>
        <select id="topup-method" name="payment_method" class="loco-sandbox-choice">
          <option value="sandbox_card">Visa / Mastercard (Demo Card)</option>
          <option value="sandbox_gcash">GCash (Payment Simulation)</option>
          <option value="sandbox_paypal">PayPal (Payment Simulation)</option>
        </select>
        <div id="topup-card" style="margin:17px 0" class="loco-sandbox-well">
          <h2 class="loco-sandbox-heading">CARD INFORMATION</h2>
          <p class="loco-sandbox-label">Only demo cards are supported: approved 4242 4242 4242 4242, declined 4000 0000 0000 0002.</p>
          <div style="display:grid;gap:9px">
            <label class="loco-sandbox-label" for="card_number">Test card number</label><input class="loco-sandbox-field" name="card_number" id="card_number" placeholder="4242 4242 4242 4242" maxlength="23" inputmode="numeric">
            <label class="loco-sandbox-label" for="card_expiry">Expiration month</label><input class="loco-sandbox-field" name="card_expiry" id="card_expiry" type="month">
            <label class="loco-sandbox-label" for="card_cvv">Test security code</label><input class="loco-sandbox-field" name="card_cvv" id="card_cvv" maxlength="4" placeholder="123" inputmode="numeric">
          </div>
        </div>
        <div id="topup-provider" class="loco-checkout-receipt" style="display:none">
          <div class="receipt-brand" id="topup-provider-brand">GCash · DEMO</div>
          <div class="loco-payment-warning">CLASS PROJECT SIMULATION — DO NOT ENTER REAL PAYMENT INFORMATION</div>
          <div class="receipt-content">
            <div style="display:flex;align-items:center;gap:12px">
              <img src="profile_avatar.php?id=<?=$userId?>" alt="Player avatar" style="width:42px;height:42px;object-fit:cover">
              <div><strong><?=h($_SESSION['username'])?></strong><p style="font-size:.72rem;margin:3px 0">Demo LOCO account</p></div>
            </div>
            <div class="loco-receipt-row"><span>Merchant</span><strong>LOCO / Stoker Studios</strong></div>
            <div class="loco-receipt-row"><span>Payment</span><strong>Sandbox wallet top-up</strong></div>
            <div class="loco-receipt-row"><span>Status</span><strong>Awaiting demo confirmation</strong></div>
            <label style="font-size:.76rem;color:#172333;display:block;margin:17px 0 0"><input type="checkbox" name="demo_confirm" value="yes" id="topup-demo-confirm"> I confirm this is only a simulated payment.</label>
          </div>
        </div>
        <p class="loco-sandbox-label" style="margin:18px 0">No real provider is contacted, no real money is charged, and no card details are stored.</p>
        <button type="submit" class="loco-brand-gloss-btn" style="border-radius:0">CONFIRM DEMO TOP-UP</button>
      </form>
    </section>
    <aside class="loco-sandbox-sidebar">
      <h3>LOCO PAYMENT METHODS</h3><p>Card test data, GCash-style simulated confirmation, and PayPal-style simulated confirmation.</p>
      <p><span class="loco-pay-demo-badge">VISA</span> <span class="loco-pay-demo-badge">Mastercard</span></p>
      <p><span class="loco-pay-demo-badge">GCash Demo</span> <span class="loco-pay-demo-badge">PayPal Demo</span></p>
      <h3 style="margin-top:25px">TRANSACTION HISTORY</h3><p>All completed simulated wallet top-ups are recorded in MySQL.</p>
      <a href="transaction_history.php" style="color:#fff">View transaction history →</a>
      <p style="margin-top:25px"><a href="Products.php">← Return to LOCO Store</a></p>
    </aside>
  </div>
  <?php endif; ?>
</main>
<script>
(function(){
 const sel=document.getElementById('topup-method');if(!sel)return;
 const card=document.getElementById('topup-card'),provider=document.getElementById('topup-provider'),check=document.getElementById('topup-demo-confirm');
 function update(){
  const m=sel.value,cardMode=m==='sandbox_card';
  card.hidden=!cardMode;
  provider.style.display=cardMode?'none':'block';
  provider.classList.toggle('paypal',m==='sandbox_paypal');
  document.getElementById('topup-provider-brand').textContent=m==='sandbox_paypal'?'PayPal · DEMO':'GCash · DEMO';
  card.querySelectorAll('input').forEach(f=>f.required=cardMode);
  check.required=!cardMode;
 }
 sel.addEventListener('change',update);update();
})();
</script>
</body></html>