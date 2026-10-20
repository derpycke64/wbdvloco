<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$userId = (int) $_SESSION['user_id'];
$transactions = [];
$error = '';

try {
    $stmt = $pdo->prepare(
        'SELECT id, transaction_type, payment_method, amount, status, reference_code, items_json, notes, created_at
         FROM transaction_history WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 100'
    );
    $stmt->execute([':user_id' => $userId]);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Transaction history is not set up yet. Import database/transaction_history.sql into loco_db, then refresh this page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LOCO - Transaction History</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="store-wrapper" style="max-width:1000px;margin:40px auto;display:block">
  <section class="loco-panel" style="max-width:100%;padding:28px">
    <h1>Transaction History</h1>
    <p style="color:#b0b3b8">Your recent LOCO wallet and purchase activity.</p>
    <?php if ($error !== ''): ?>
      <p role="status" style="padding:14px;background:#3a2d16;border-radius:4px"><?php echo h($error); ?></p>
    <?php elseif (!$transactions): ?>
      <p style="color:#b0b3b8">No transactions yet. Completed transactions will appear here.</p>
    <?php else: ?>
      <div style="overflow-x:auto">
      <table style="width:100%;border-collapse:collapse;text-align:left">
        <thead><tr>
          <th style="padding:12px;border-bottom:1px solid #444">Date</th>
          <th style="padding:12px;border-bottom:1px solid #444">Reference</th>
          <th style="padding:12px;border-bottom:1px solid #444">Type</th>
          <th style="padding:12px;border-bottom:1px solid #444">Method</th>
          <th style="padding:12px;border-bottom:1px solid #444">Amount</th>
          <th style="padding:12px;border-bottom:1px solid #444">Status</th>
        </tr></thead>
        <tbody>
        <?php foreach ($transactions as $tx): ?>
          <tr>
            <td style="padding:12px;border-bottom:1px solid #333"><?php echo h($tx['created_at']); ?></td>
            <td style="padding:12px;border-bottom:1px solid #333"><?php echo h($tx['reference_code']); ?></td>
            <td style="padding:12px;border-bottom:1px solid #333"><?php echo h(ucwords(str_replace('_', ' ', $tx['transaction_type']))); ?></td>
            <td style="padding:12px;border-bottom:1px solid #333"><?php echo h(ucwords(str_replace('_', ' ', $tx['payment_method']))); ?></td>
            <td style="padding:12px;border-bottom:1px solid #333">₱<?php echo number_format((float)$tx['amount'], 2); ?></td>
            <td style="padding:12px;border-bottom:1px solid #333"><?php echo h(ucfirst($tx['status'])); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
