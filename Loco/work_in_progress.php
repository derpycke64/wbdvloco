<?php
session_start();
require_once 'db.php';
$label=trim((string)($_GET['feature']??'This feature'));
$label=preg_replace('/[^\p{L}\p{N}\s&.\-\/]/u','',$label);
$label=substr((string)$label,0,70);
if($label==='')$label='This feature';
function wipEsc($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <title>LOCO | Work in Progress</title>
 <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">
 <?php include 'nav.php'; ?>
 <main class="loco-community-wrap" style="min-height:55vh">
  <section class="loco-community-panel loco-wip-panel">
   <p class="loco-eyebrow">LOCO / STOKER STUDIOS</p>
   <h1>WORK IN PROGRESS</h1>
   <p class="loco-wip-feature"><?=wipEsc($label)?></p>
   <p>This area of LOCO is still being developed. Thank you for checking it out — more features are coming soon.</p>
   <div class="loco-wip-links">
    <a href="Products.php" class="loco-wishlist-action">BACK TO STORE</a>
    <?php if(!empty($_SESSION['user_id'])): ?>
     <a href="community.php?tab=market" class="loco-wishlist-action">COMMUNITY</a>
    <?php endif; ?>
    <button type="button" onclick="history.length>1?history.back():location.assign('Products.php')" class="loco-wishlist-remove">GO BACK</button>
   </div>
  </section>
 </main>
</body>
</html>