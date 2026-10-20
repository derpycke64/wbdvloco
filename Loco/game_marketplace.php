<?php
session_start();
require_once 'db.php';
require_once 'game_license.php';
if(empty($_SESSION['user_id']) || empty($_SESSION['username'])){header('Location: Login.php');exit;}
$userId=(int)$_SESSION['user_id'];
if(empty($_SESSION['loco_game_resale_csrf']))$_SESSION['loco_game_resale_csrf']=bin2hex(random_bytes(24));
$csrf=$_SESSION['loco_game_resale_csrf'];
function gmEsc($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$notice=(string)($_SESSION['loco_game_market_notice']??'');unset($_SESSION['loco_game_market_notice']);
$error='';$databaseReady=true;

if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($csrf,(string)($_POST['csrf']??'')))$error='Form token expired. Refresh the page and retry.';
  else{
    $action=(string)($_POST['action']??'');
    $id=(int)($_POST['id']??0);
    $priceInput=(string)($_POST['price']??'');
    $price=filter_var($priceInput,FILTER_VALIDATE_FLOAT);
    try{
      if(in_array($action,['sell','change_price'],true) && ($price===false || !is_finite((float)$price) || $price<0.01 || $price>1000000)){
        throw new RuntimeException('Please choose a game resale price between ₱0.01 and ₱1,000,000.00.');
      }
      $pdo->beginTransaction();
      if($action==='sell'){
        $q=$pdo->prepare('SELECT l.id,l.user_id,l.game_id,g.title FROM library l JOIN games g ON g.id=l.game_id WHERE l.id=:id FOR UPDATE');
        $q->execute([':id'=>$id]);$owned=$q->fetch(PDO::FETCH_ASSOC);
        if(!$owned || (int)$owned['user_id']!==$userId)throw new RuntimeException('This game is not in your Library.');
        $q=$pdo->prepare("SELECT id FROM game_resale_listings WHERE library_id=:lid AND status='active' LIMIT 1 FOR UPDATE");
        $q->execute([':lid'=>$id]);if($q->fetch())throw new RuntimeException('That game is already listed for sale.');
        $q=$pdo->prepare("SELECT id FROM game_resale_listings WHERE library_id=:lid AND seller_id=:uid AND status='removed' LIMIT 1 FOR UPDATE");
        $q->execute([':lid'=>$id,':uid'=>$userId]);
        if($q->fetch())throw new RuntimeException('This game was removed by moderation and cannot be relisted by the same seller.');
        // An owned license must exist before it can be resold; never ask users for real game keys.
        if(!locoEnsureGameLicense($pdo,$id,$userId))throw new RuntimeException('Unable to create your LOCO demo license key.');
        $q=$pdo->prepare("INSERT INTO game_resale_listings(library_id,seller_id,game_id,price,status) VALUES(:lid,:uid,:gid,:price,'active')");
        $q->execute([':lid'=>$id,':uid'=>$userId,':gid'=>(int)$owned['game_id'],':price'=>number_format((float)$price,2,'.','')]);
        $message='Your owned game is now listed for resale in the Community Marketplace.';
      }elseif($action==='cancel'){
        $q=$pdo->prepare("UPDATE game_resale_listings SET status='cancelled' WHERE id=:id AND seller_id=:uid AND status='active'");
        $q->execute([':id'=>$id,':uid'=>$userId]);
        if($q->rowCount()!==1)throw new RuntimeException('The listing is unavailable or is not yours.');
        $message='Game listing cancelled. Your game remains in your Library.';
      }elseif($action==='change_price'){
        $q=$pdo->prepare("UPDATE game_resale_listings SET price=:price WHERE id=:id AND seller_id=:uid AND status='active'");
        $q->execute([':price'=>number_format((float)$price,2,'.',''),':id'=>$id,':uid'=>$userId]);
        if($q->rowCount()!==1){
          $check=$pdo->prepare("SELECT id FROM game_resale_listings WHERE id=:id AND seller_id=:uid AND status='active' LIMIT 1");
          $check->execute([':id'=>$id,':uid'=>$userId]);
          if(!$check->fetch())throw new RuntimeException('The listing is unavailable or not yours.');
        }
        $message='Asking price updated.';
      }elseif($action==='buy'){
        $q=$pdo->prepare("SELECT r.id,r.library_id,r.seller_id,r.game_id,r.price,r.status,g.title
           FROM game_resale_listings r JOIN games g ON g.id=r.game_id WHERE r.id=:id FOR UPDATE");
        $q->execute([':id'=>$id]);$listing=$q->fetch(PDO::FETCH_ASSOC);
        if(!$listing||$listing['status']!=='active')throw new RuntimeException('That game listing is no longer available.');
        $sellerId=(int)$listing['seller_id'];
        if($sellerId===$userId)throw new RuntimeException('You cannot purchase your own game.');
        $amount=(float)$listing['price'];
        if($amount<0.01||$amount>1000000)throw new RuntimeException('The asking price is invalid.');
        $q=$pdo->prepare('SELECT id,user_id,game_id,license_key FROM library WHERE id=:id FOR UPDATE');
        $q->execute([':id'=>(int)$listing['library_id']]);$owned=$q->fetch(PDO::FETCH_ASSOC);
        if(!$owned||(int)$owned['user_id']!==$sellerId||(int)$owned['game_id']!==(int)$listing['game_id'])
          throw new RuntimeException('The seller no longer owns this game.');
        $q=$pdo->prepare('SELECT id FROM library WHERE user_id=:uid AND game_id=:gid LIMIT 1 FOR UPDATE');
        $q->execute([':uid'=>$userId,':gid'=>(int)$listing['game_id']]);
        if($q->fetch())throw new RuntimeException('You already own this game in your Library.');
        // Lock payer and payee in a deterministic order to avoid competing balance updates.
        $q=$pdo->prepare('SELECT id,wallet_balance FROM users WHERE id IN (:buyer,:seller) ORDER BY id FOR UPDATE');
        $q->execute([':buyer'=>$userId,':seller'=>$sellerId]);
        $accounts=[];
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $account)$accounts[(int)$account['id']]=$account;
        if(!isset($accounts[$userId],$accounts[$sellerId]))throw new RuntimeException('An account is no longer available.');
        if((float)$accounts[$userId]['wallet_balance']+0.000001<$amount)
          throw new RuntimeException('Insufficient LOCO wallet funds. Add funds before purchasing.');
        if(empty($owned['license_key']) && !locoEnsureGameLicense($pdo,(int)$owned['id'],$sellerId))
          throw new RuntimeException('The game license could not be prepared.');
        $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance-:amount WHERE id=:uid AND wallet_balance>=:minimum');
        $q->execute([':amount'=>$amount,':uid'=>$userId,':minimum'=>$amount]);
        if($q->rowCount()!==1)throw new RuntimeException('Wallet balance changed. Retry the purchase.');
        $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance+:amount WHERE id=:uid');
        $q->execute([':amount'=>$amount,':uid'=>$sellerId]);
        if($q->rowCount()!==1)throw new RuntimeException('Unable to credit the seller.');
        $q=$pdo->prepare('UPDATE library SET user_id=:buyer,purchase_date=CURRENT_TIMESTAMP WHERE id=:lid AND user_id=:seller');
        $q->execute([':buyer'=>$userId,':lid'=>(int)$listing['library_id'],':seller'=>$sellerId]);
        if($q->rowCount()!==1)throw new RuntimeException('Game ownership changed. Please retry.');
        $q=$pdo->prepare("UPDATE game_resale_listings SET status='sold',buyer_id=:buyer,sold_at=CURRENT_TIMESTAMP WHERE id=:id AND status='active'");
        $q->execute([':buyer'=>$userId,':id'=>$id]);
        if($q->rowCount()!==1)throw new RuntimeException('Listing was already purchased.');
        $ref='RESALE-'.strtoupper(bin2hex(random_bytes(7)));
        $q=$pdo->prepare("INSERT INTO transaction_history
         (user_id,transaction_type,payment_method,amount,status,reference_code,items_json,notes)
         VALUES(:uid,:type,'wallet',:amount,'completed',:ref,:items,:notes)");
        $items=json_encode([$listing['title']],JSON_UNESCAPED_UNICODE);
        $q->execute([':uid'=>$userId,':type'=>'marketplace_purchase',':amount'=>$amount,':ref'=>$ref.'-B',
          ':items'=>$items,':notes'=>'LOCO used-game resale: license transferred from seller']);
        $q->execute([':uid'=>$sellerId,':type'=>'marketplace_sale',':amount'=>$amount,':ref'=>$ref.'-S',
          ':items'=>$items,':notes'=>'LOCO used-game sale: license transferred to buyer']);
        $message='Game purchased! The game and LOCO demo license are now in your Library; your wallet was charged and the seller was paid.';
      }else throw new RuntimeException('Unsupported game marketplace action.');
      $pdo->commit();
      $_SESSION['loco_game_market_notice']=$message;
      header('Location: game_marketplace.php'.($action==='sell'||$action==='cancel'||$action==='change_price'?'?filter=mine':''));
      exit;
    }catch(Throwable $e){
      if($pdo->inTransaction())$pdo->rollBack();
      if($e instanceof PDOException){
        error_log('LOCO game resale database error: '.$e->getMessage());
        $error='Unable to update game resale records. Import database/game_resale.sql and check your transaction_history setup.';
        $databaseReady=false;
      }elseif($e instanceof RuntimeException)$error=$e->getMessage();
      else{$error='Game resale operation failed. Please retry.';error_log('LOCO game resale: '.$e->getMessage());}
    }
  }
}
$active=[];$ownedGames=[];$history=[];$myListings=[];
try{
  $q=$pdo->query("SELECT r.id,r.library_id,r.seller_id,r.price,r.created_at,g.title,g.description,g.image_url,
    g.price AS store_price,u.username AS seller FROM game_resale_listings r
    JOIN library l ON l.id=r.library_id AND l.user_id=r.seller_id
    JOIN games g ON g.id=r.game_id JOIN users u ON u.id=r.seller_id
    WHERE r.status='active' ORDER BY r.created_at DESC,r.id DESC LIMIT 100");
  $active=$q->fetchAll(PDO::FETCH_ASSOC);
  foreach($active as $row)if((int)$row['seller_id']===$userId)$myListings[]=$row;
  $q=$pdo->prepare("SELECT l.id AS library_id,g.title,g.price,g.image_url FROM library l
    JOIN games g ON g.id=l.game_id
    LEFT JOIN game_resale_listings r ON r.active_library_id=l.id
    WHERE l.user_id=:uid AND r.id IS NULL ORDER BY l.purchase_date DESC,l.id DESC");
  $q->execute([':uid'=>$userId]);$ownedGames=$q->fetchAll(PDO::FETCH_ASSOC);
  $q=$pdo->prepare("SELECT r.id,r.seller_id,r.buyer_id,r.price,r.status,r.created_at,r.sold_at,g.title
    FROM game_resale_listings r JOIN games g ON g.id=r.game_id
    WHERE r.seller_id=:seller OR r.buyer_id=:buyer ORDER BY r.id DESC LIMIT 40");
  $q->execute([':seller'=>$userId,':buyer'=>$userId]);$history=$q->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){
  $databaseReady=false;
  error_log('LOCO game resale listing load: '.$e->getMessage());
  $error=$error?:'Game resale requires database/game_resale.sql to be imported into loco_db.';
}
$filter=(($_GET['filter']??'')==='mine')?'mine':'all';
$prefill=(int)($_GET['sell']??0);
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>LOCO | Community Game Marketplace</title><link rel="stylesheet" href="style.css"></head>
<body class="loco-store-body"><?php include_once 'nav.php'; ?>
<main class="loco-community-wrap">
  <div class="loco-community-heading">
    <div><p class="loco-eyebrow">COMMUNITY / LOCO LICENSE MARKET</p>
      <h1 class="loco-logo-text">Game Marketplace</h1>
      <p>Resell LOCO-owned games to other players using the wallet. Demo license ownership transfers with the game.</p>
    </div>
    <a class="loco-button-small" href="community.php?tab=market">Collectible Marketplace →</a>
  </div>
  <?php if($notice): ?><div class="loco-notice success" role="status"><?=gmEsc($notice)?></div><?php endif; ?>
  <?php if($error): ?><div class="loco-notice error" role="alert"><?=gmEsc($error)?></div><?php endif; ?>
  <div class="loco-game-market-nav">
    <a href="game_marketplace.php" class="<?=$filter==='all'?'active':''?>">BROWSE USED GAMES</a>
    <a href="game_marketplace.php?filter=mine" class="<?=$filter==='mine'?'active':''?>">SELL / MY LISTINGS</a>
    <a href="profile.php#library">MY LIBRARY</a>
  </div>
  <?php if($filter==='all'): ?>
  <section class="loco-community-panel">
    <div class="loco-section-heading"><div><h2>Games offered by LOCO players</h2><p>Buying a game moves its LOCO license into your Library. Keys are never shown publicly.</p></div></div>
    <?php if(!$active): ?><p class="loco-empty-state">No used games are currently listed. <a href="game_marketplace.php?filter=mine">Sell a game from your Library</a>.</p><?php endif; ?>
    <div class="loco-game-resale-grid">
      <?php foreach($active as $game): ?><article class="loco-game-resale-card">
        <img src="<?=gmEsc($game['image_url']?:'images/gameplay_01.jpg')?>" alt="<?=gmEsc($game['title'])?>">
        <div class="loco-game-resale-info">
          <h3><?=gmEsc($game['title'])?></h3>
          <p><?=gmEsc(substr((string)($game['description']??''),0,180))?></p>
          <small>Seller: <?=gmEsc($game['seller'])?> · LOCO demo license</small>
        </div>
        <div class="loco-game-resale-buy">
          <strong>₱<?=number_format((float)$game['price'],2)?></strong>
          <small>Store: ₱<?=number_format((float)$game['store_price'],2)?></small>
          <?php if((int)$game['seller_id']===$userId): ?>
            <a class="loco-wishlist-action" href="game_marketplace.php?filter=mine">MANAGE</a>
          <?php else: ?>
            <form method="post" onsubmit="return confirm('Buy <?=gmEsc($game['title'])?> for ₱<?=number_format((float)$game['price'],2)?>? LOCO wallet funds will be transferred to the seller.')">
              <input type="hidden" name="csrf" value="<?=gmEsc($csrf)?>">
              <input type="hidden" name="action" value="buy">
              <input type="hidden" name="id" value="<?=(int)$game['id']?>">
              <button class="loco-wishlist-action" type="submit">BUY GAME</button>
            </form>
          <?php endif; ?>
        </div>
      </article><?php endforeach; ?>
    </div>
  </section>
  <?php else: ?>
  <section class="loco-community-panel" id="sell-owned-game">
    <div class="loco-section-heading"><div><h2>Sell an owned game</h2><p>Choose a game that you own. On sale, ownership and the demo key move to the buyer, and funds move to your wallet.</p></div></div>
    <?php if(!$ownedGames): ?><p class="loco-empty-state">You have no unlisted games available for resale. Check <a href="profile.php#library">your Library</a>.</p><?php endif; ?>
    <form method="post" class="loco-form-grid">
      <input type="hidden" name="csrf" value="<?=gmEsc($csrf)?>">
      <input type="hidden" name="action" value="sell">
      <label>Game from your Library
        <select name="id" required <?=$databaseReady?'':'disabled'?>>
          <option value="">Select an owned game</option>
          <?php foreach($ownedGames as $game): ?>
            <option value="<?=(int)$game['library_id']?>" <?=$prefill===(int)$game['library_id']?'selected':''?>><?=gmEsc($game['title'])?> (Store: ₱<?=number_format((float)$game['price'],2)?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Asking price (PHP)
        <input type="number" name="price" min="0.01" max="1000000" step="0.01" placeholder="499.00" required>
      </label>
      <div class="loco-form-actions"><button class="loco-brand-gloss-btn" type="submit" <?=$databaseReady&&$ownedGames?'':'disabled'?>>LIST GAME FOR SALE</button></div>
    </form>
  </section>
  <section class="loco-community-panel">
    <div class="loco-section-heading"><div><h2>My active game listings</h2></div></div>
    <?php if(!$myListings): ?><p class="loco-muted">You have no active game listings.</p><?php endif; ?>
    <?php foreach($myListings as $row): ?>
      <div class="loco-owned-row loco-game-owned-resale">
        <div><strong><?=gmEsc($row['title'])?></strong><small>Listed for ₱<?=number_format((float)$row['price'],2)?> on <?=gmEsc($row['created_at'])?></small></div>
        <div class="loco-row-actions">
          <form method="post" class="loco-inline-form" style="display:flex;align-items:center;gap:7px">
            <input type="hidden" name="csrf" value="<?=gmEsc($csrf)?>"><input type="hidden" name="action" value="change_price">
            <input type="hidden" name="id" value="<?=(int)$row['id']?>">
            <label class="sr-only" for="game-resale-price-<?=(int)$row['id']?>">Change price</label>
            <input class="loco-game-resale-price-input" id="game-resale-price-<?=(int)$row['id']?>" name="price" type="number" min="0.01" max="1000000" step="0.01" required value="<?=gmEsc($row['price'])?>">
            <button type="submit" class="loco-button-small">SAVE PRICE</button>
          </form>
          <form method="post" onsubmit="return confirm('Cancel the listing? Your game stays in your Library.')">
            <input type="hidden" name="csrf" value="<?=gmEsc($csrf)?>"><input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" value="<?=(int)$row['id']?>">
            <button type="submit" class="loco-danger-link">Cancel listing</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </section>
  <section class="loco-community-panel">
    <div class="loco-section-heading"><div><h2>Game resale history</h2><p>Historical listings are saved even after a sale or cancellation.</p></div></div>
    <?php if(!$history): ?><p class="loco-muted">No previous used-game listings or purchases.</p><?php endif; ?>
    <?php foreach($history as $row): ?>
      <div class="loco-owned-row"><div><strong><?=gmEsc($row['title'])?></strong>
        <small><?=gmEsc((int)$row['seller_id']===$userId?'You listed this game':'You bought this game')?> · ₱<?=number_format((float)$row['price'],2)?> · <?=gmEsc($row['created_at'])?></small></div>
        <span class="loco-status-pill <?=gmEsc($row['status'])?>"><?=gmEsc(ucfirst($row['status']))?></span></div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>
  <p class="loco-muted" style="margin-top:18px">Game keys shown by LOCO are simulated internal license IDs, not Steam keys. Selling a game transfers its LOCO entitlement only.</p>
</main></body></html>
