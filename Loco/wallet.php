<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
if (empty($_SESSION['wallet_csrf'])) {
    $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
}
$message = '';
$error = '';
$allowedAmounts = ['100.00', '250.00', '500.00', '1000.00'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    $amount = (string)($_POST['amount'] ?? '');
    $method = (string)($_POST['payment_method'] ?? 'sandbox');

    if (!hash_equals($_SESSION['wallet_csrf'], $csrf)) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } elseif (!in_array($amount, $allowedAmounts, true)) {
        $error = 'Choose one of the available demo top-up amounts.';
    } elseif (!in_array($method, ['sandbox_card', 'sandbox_wallet'], true)) {
        $error = 'Choose a supported simulated payment method.';
    } else {
        $value = (float)$amount;
        $reference = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));
        try {
            $pdo->beginTransaction();
            $balanceStmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + :amount WHERE id = :user_id');
            $balanceStmt->execute([':amount' => $value, ':user_id' => $userId]);
            if ($balanceStmt->rowCount() !== 1) {
                throw new RuntimeException('Could not update the wallet for this account.');
            }
            $txStmt = $pdo->prepare(
                "INSERT INTO transaction_history
                    (user_id, transaction_type, payment_method, amount, status, reference_code, notes)
                 VALUES
                    (:user_id, 'wallet_topup', :method, :amount, 'completed', :reference, 'Simulated demo wallet top-up; no real payment was processed')"
            );
            $txStmt->execute([
                ':user_id' => $userId,
                ':method' => $method,
                ':amount' => $value,
                ':reference' => $reference
            ]);
            $pdo->commit();
            $message = 'Demo top-up completed. Reference: ' . $reference . '. No real money was charged.';
            $_SESSION['wallet_csrf'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Wallet top-up failed. Make sure the transaction_history migration has been imported, then try again.';
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
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LOCO - Demo Wallet</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="store-wrapper" style="max-width:760px;margin:40px auto;display:block">
  <section class="loco-panel" style="max-width:100%;padding:28px">
    <p style="color:#aaa;letter-spacing:.12em;font-size:.75rem">LOCO WALLET</p>
    <h1>Wallet balance</h1>
    <p style="font-size:2rem;font-weight:700">₱<?= $walletBalance === null ? '—' : number_format((float)$walletBalance, 2) ?></p>
    <p style="color:#aaa">Demo mode only. Top-ups add test credit to your account; no real payment provider is connected and no real money is charged.</p>
    <?php if ($message): ?><p role="status" style="padding:12px;background:#173522;border-radius:4px"><?=h($message)?></p><?php endif; ?>
    <?php if ($error): ?><p role="alert" style="padding:12px;background:#422222;border-radius:4px"><?=h($error)?></p><?php endif; ?>
    <form method="post" style="display:grid;gap:16px;margin-top:24px">
      <input type="hidden" name="csrf_token" value="<?=h($_SESSION['wallet_csrf'])?>">
      <label for="amount">Demo top-up amount</label>
      <select id="amount" name="amount" required style="padding:12px">
        <option value="">Select an amount</option>
        <?php foreach ($allowedAmounts as $option): ?>
          <option value="<?=h($option)?>">₱<?=number_format((float)$option, 2)?></option>
        <?php endforeach; ?>
      </select>
      <label for="payment_method">Simulated method</label>
      <select id="payment_method" name="payment_method" required style="padding:12px">
        <option value="sandbox_card">Sandbox card (simulated)</option>
        <option value="sandbox_wallet">Demo wallet credit</option>
      </select>
      <button type="submit" class="loco-brand-gloss-btn" style="padding:12px">Add demo balance</button>
    </form>
    <p style="margin-top:22px"><a href="transaction_history.php">View transaction history →</a></p>
  </section>
</main>
</body>
</html>
