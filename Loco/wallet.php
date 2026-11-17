<?php
require_once __DIR__ . '/session.php';
require_once 'db.php';
if (!isset($_SESSION['user_id'], $_SESSION['username'])) { header('Location: login.php'); exit(); }
$userId = (int)$_SESSION['user_id'];
if (empty($_SESSION['wallet_csrf'])) $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
$message = (string)($_SESSION['wallet_notice'] ?? '');
unset($_SESSION['wallet_notice']);
$receipt = isset($_GET['receipt']) ? ($_SESSION['last_wallet_receipt'] ?? null) : null;
$error = '';
$allowedAmounts = ['100.00', '250.00', '500.00', '1000.00'];

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function walletSandboxCardError(string $result): ?string {
    if ($result === 'approved') return null;
    if ($result === 'declined') return 'Card declined. No funds were added.';
    return 'Card details incomplete. Use the listed test card numbers.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $amount = (string)($_POST['amount'] ?? '');
    if (!hash_equals($_SESSION['wallet_csrf'], $csrf)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (!in_array($amount, $allowedAmounts, true)) {
        $error = 'Choose one of the available top-up amounts.';
    } else {
        $method=(string)($_POST['payment_method']??'sandbox_visa');
        $paymentError=null;
        if(!in_array($method,['sandbox_visa','sandbox_mastercard','sandbox_gcash','sandbox_paypal'],true))$paymentError='Choose a supported payment method.';
        elseif(in_array($method,['sandbox_visa','sandbox_mastercard'],true))$paymentError=walletSandboxCardError((string)($_POST['demo_card_result'] ?? ''));
        elseif((string)($_POST['demo_confirm']??'')!=='yes')$paymentError='Confirm the test payment to continue.';
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
                $txStmt->execute([':user_id' => $userId, ':amount' => $value, ':reference' => $reference, ':notes'=>'Test '.ucfirst(str_replace('sandbox_','',$method)).' wallet top-up; no real payment processed']);
                $pdo->commit();
                $_SESSION['wallet_notice']='Top-up complete. ₱'.number_format($value,2).' added to your LOCO wallet.';
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
  <div class="loco-sandbox-step">LOCO WALLET　›　 ADD FUNDS <span style="float:right">TEST PAYMENTS ONLY</span></div>
  <?php if($message): ?><p role="status" class="loco-notice success" style="margin:12px"><?=h($message)?></p><?php endif; ?>
  <?php if($error): ?><p role="alert" class="loco-notice error" style="margin:12px"><?=h($error)?></p><?php endif; ?>
  <?php if($receipt): ?>
    <div class="loco-checkout-receipt loco-provider-card <?=($receipt['method']==='sandbox_paypal')?'paypal':''?>">
      <div class="receipt-brand"><span><?=h($receipt['method']==='sandbox_gcash'?'GCash':($receipt['method']==='sandbox_paypal'?'PayPal':($receipt['method']==='sandbox_visa'?'Visa':($receipt['method']==='sandbox_mastercard'?'Mastercard':'LOCO'))))?></span><small>LOCO wallet receipt</small></div>
      <div class="loco-payment-warning"><strong>Test payment</strong><span>No real account or funds were used. This confirms a local wallet top-up test.</span></div>
      <div class="receipt-content">
        <div style="display:flex;gap:12px;align-items:center">
          <img src="profile_avatar.php?id=<?=$userId?>" alt="Player avatar" style="width:44px;height:44px;object-fit:cover;border:1px solid #cbd0dc">
          <div><strong><?=h($_SESSION['username'])?></strong><p style="font-size:.72rem;margin:3px 0">LOCO player account</p></div>
        </div>
        <p style="font-size:.74rem;margin:13px 0 3px">AMOUNT ADDED TO LOCO WALLET</p><h2 style="font-size:1.75rem;margin:3px 0 17px">₱<?=number_format((float)$receipt['amount'],2)?></h2>
        <div class="loco-receipt-row"><span>Merchant</span><strong>LOCO / Stoker Studios</strong></div>
        <div class="loco-receipt-row"><span>Payment</span><strong><?=h(['sandbox_card'=>'Card','sandbox_visa'=>'Visa','sandbox_mastercard'=>'Mastercard','sandbox_gcash'=>'GCash','sandbox_paypal'=>'PayPal'][$receipt['method']]??'Test payment')?></strong></div>
        <div class="loco-receipt-row"><span>Reference</span><strong><?=h($receipt['reference'])?></strong></div>
        <div class="loco-receipt-row"><span>Status</span><strong>Completed</strong></div>
      </div>
    </div>
    <p style="padding:12px 20px"><a href="wallet.php" class="loco-wishlist-action">ADD MORE FUNDS</a>　<a href="products.php">RETURN TO STORE</a>　<a href="transaction_history.php">Transaction History</a></p>
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
          <option value="sandbox_visa" <?=($_POST['payment_method']??'sandbox_visa')==='sandbox_visa'?'selected':''?>>Visa</option>
          <option value="sandbox_mastercard" <?=($_POST['payment_method']??'sandbox_visa')==='sandbox_mastercard'?'selected':''?>>Mastercard</option>
          <option value="sandbox_gcash" <?=($_POST['payment_method']??'sandbox_visa')==='sandbox_gcash'?'selected':''?>>GCash</option>
          <option value="sandbox_paypal" <?=($_POST['payment_method']??'sandbox_visa')==='sandbox_paypal'?'selected':''?>>PayPal</option>
        </select>
        <div id="topup-card" style="margin:17px 0" class="loco-sandbox-well">
          <h2 class="loco-sandbox-heading" id="topup-card-title">VISA CARD INFORMATION</h2>
          <p class="loco-sandbox-label" id="topup-card-instructions">Use Visa test numbers only: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Never enter real card details.</p>
          <div style="display:grid;gap:9px">
            <label class="loco-sandbox-label" for="card_number">Test card number</label>
            <input class="loco-sandbox-field" id="card_number" placeholder="4242 4242 4242 4242" maxlength="23" inputmode="numeric" autocomplete="off">
            <label class="loco-sandbox-label" for="card_expiry">Expiration month</label>
            <input class="loco-sandbox-field" id="card_expiry" type="month" autocomplete="off">
            <label class="loco-sandbox-label" for="card_cvv">Test security code</label>
            <input class="loco-sandbox-field" id="card_cvv" maxlength="4" placeholder="123" inputmode="numeric" autocomplete="off">
          </div>
          <!-- Only the approved/declined simulation token is submitted; card-style fields have no name attributes. -->
          <input type="hidden" name="demo_card_result" id="demo_card_result" value="">
        </div>
        <section id="topup-provider" class="loco-provider-stage" style="display:none" aria-label="Test wallet top-up provider">
          <div class="loco-checkout-receipt loco-provider-card" id="topup-provider-card">
            <div class="receipt-brand">
              <span class="loco-provider-wordmark" id="topup-provider-brand">GCash</span>
              <small>LOCO wallet checkout</small>
            </div>
            <div class="loco-payment-warning"><strong>Test payment</strong><span>No real PayPal or GCash account, OTP, wallet, or funds are used.</span></div>
            <div class="receipt-content">
              <div class="loco-provider-amount">
                <span>Amount due</span>
                <strong id="topup-demo-amount">Select an amount</strong>
              </div>
              <div class="loco-provider-details">
                <div class="loco-provider-payer">
                  <img src="profile_avatar.php?id=<?=$userId?>" alt="Your LOCO profile picture">
                  <div><strong><?=h($_SESSION['username'])?></strong><span>LOCO player account</span></div>
                </div>
                <div class="loco-provider-detail-row"><span>Merchant</span><strong>LOCO / STOKER STUDIOS</strong></div>
                <div class="loco-provider-detail-row"><span>Player account</span><strong><?=h($_SESSION['username'])?></strong></div>
                <div class="loco-provider-detail-row"><span>Reference</span><strong>LOCO-WALLET-<?=str_pad((string)$userId,4,'0',STR_PAD_LEFT)?></strong></div>
                <div class="loco-provider-detail-row"><span>Status</span><strong>Pending</strong></div>
              </div>
              <p class="loco-provider-explanation">Your LOCO account details were filled automatically. This adds test funds to your LOCO wallet; no real funds move.</p>
              <input type="hidden" name="demo_confirm" value="yes">
              <button class="loco-provider-confirm" type="submit">Confirm test payment</button>
              <button class="loco-provider-cancel" type="button" id="topup-provider-cancel">Cancel and return to merchant</button>
              <p class="loco-provider-footer-note">Test checkout hosted by LOCO / Stoker Studios</p>
            </div>
          </div>
        </section>
        <p class="loco-sandbox-label" style="margin:18px 0">No real provider is contacted, no real money is charged, and no card details are stored.</p>
        <button type="submit" class="loco-brand-gloss-btn" style="border-radius:0">CONFIRM TOP-UP</button>
      </form>
    </section>
    <aside class="loco-sandbox-sidebar">
      <h3>LOCO PAYMENT METHODS</h3><p>Card, GCash, and PayPal are available for testing. No real charges are made.</p>
      <p><span class="loco-pay-demo-badge">VISA</span> <span class="loco-pay-demo-badge">Mastercard</span></p>
      <p><span class="loco-pay-demo-badge">GCash</span> <span class="loco-pay-demo-badge">PayPal</span></p>
      <h3 style="margin-top:25px">TRANSACTION HISTORY</h3><p>Completed wallet top-ups are recorded in your transaction history.</p>
      <a href="transaction_history.php" style="color:#fff">View transaction history →</a>
      <p style="margin-top:25px"><a href="products.php">← Return to LOCO Store</a></p>
    </aside>
  </div>
  <?php endif; ?>
</main>
<script>
(function(){
 const sel=document.getElementById('topup-method');if(!sel)return;
 const card=document.getElementById('topup-card'),provider=document.getElementById('topup-provider');
 function update(){
  const m=sel.value,cardMode=(m==='sandbox_visa'||m==='sandbox_mastercard');
  card.hidden=!cardMode;
  const mastercard=m==='sandbox_mastercard';
  document.getElementById('topup-card-title').textContent=mastercard?'MASTERCARD INFORMATION':'VISA CARD INFORMATION';
  document.getElementById('topup-card-instructions').textContent=mastercard
   ?'Use Mastercard test numbers only: 5555 5555 5555 4444 (approved) or 5105 1051 0510 5100 (declined). Never enter real card details.'
   :'Use Visa test numbers only: 4242 4242 4242 4242 (approved) or 4000 0000 0000 0002 (declined). Never enter real card details.';
  document.getElementById('card_number').placeholder=mastercard?'5555 5555 5555 4444':'4242 4242 4242 4242';
  provider.style.display=cardMode?'none':'block';
  provider.classList.toggle('paypal',m==='sandbox_paypal');
  document.getElementById('topup-provider-brand').textContent=m==='sandbox_paypal'?'PayPal':'GCash';
  document.getElementById('topup-provider-card').classList.toggle('paypal',m==='sandbox_paypal');
  document.querySelector('.loco-sandbox-page').classList.toggle('loco-provider-mode',!cardMode);
  document.body.classList.toggle('loco-provider-screen',!cardMode);
  card.querySelectorAll('input:not([type="hidden"])').forEach(f=>{
   f.required=cardMode;
   f.setCustomValidity('');
  });

 }
 const amountSelect=document.getElementById('amount'),demoAmount=document.getElementById('topup-demo-amount');
 function updateAmount(){if(demoAmount)demoAmount.textContent=amountSelect.value?'PHP '+Number(amountSelect.value).toLocaleString('en-PH',{minimumFractionDigits:2}):'Select an amount';}
 amountSelect.addEventListener('change',updateAmount);updateAmount();
 document.getElementById('topup-provider-cancel').addEventListener('click',function(){sel.value='sandbox_visa';update();sel.focus();});
 const cardNumber=document.getElementById('card_number');
 const cardExpiry=document.getElementById('card_expiry');
 const cardCvv=document.getElementById('card_cvv');
 const cardResult=document.getElementById('demo_card_result');
 cardNumber.addEventListener('input',()=>cardNumber.setCustomValidity(''));
 cardExpiry.addEventListener('input',()=>cardExpiry.setCustomValidity(''));
 cardCvv.addEventListener('input',()=>cardCvv.setCustomValidity(''));
 document.getElementById('wallet-topup-form').addEventListener('submit',function(event){
  if(sel.value!=='sandbox_visa'&&sel.value!=='sandbox_mastercard')return;
  // Never transmit card-style entries. Only send the selected test outcome.
  const digits=cardNumber.value.replace(/\D/g,'');
  const isMastercard=sel.value==='sandbox_mastercard';
  const approved=isMastercard?'5555555555554444':'4242424242424242';
  const declined=isMastercard?'5105105105105100':'4000000000000002';
  if(digits!==approved && digits!==declined){
   cardNumber.setCustomValidity('Use only one of the two displayed test card numbers.');
   cardNumber.reportValidity();event.preventDefault();return;
  }
  const now=new Date();
  const currentMonth=now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0');
  if(!/^\d{4}-(0[1-9]|1[0-2])$/.test(cardExpiry.value) || cardExpiry.value<currentMonth){
   cardExpiry.setCustomValidity('Choose the current month or a future month for this test card.');
   cardExpiry.reportValidity();event.preventDefault();return;
  }
  if(!/^\d{3,4}$/.test(cardCvv.value)){
   cardCvv.setCustomValidity('Enter a 3- or 4-digit test security code.');
   cardCvv.reportValidity();event.preventDefault();return;
  }
  cardResult.value=digits===approved?'approved':'declined';
 });
 sel.addEventListener('change',update);update();
})();
</script>
</body></html>