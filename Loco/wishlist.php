<?php
session_start();
require_once 'db.php';
if (empty($_SESSION['user_id'])) { header('Location: Login.php'); exit; }
function wishlistEsc($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$userId=(int)$_SESSION['user_id'];
if(empty($_SESSION['loco_wishlist_csrf']))$_SESSION['loco_wishlist_csrf']=bin2hex(random_bytes(24));
$csrf=$_SESSION['loco_wishlist_csrf'];
$tab=($_GET['tab']??'games')==='market'?'market':'games';
$error='';$notice=(string)($_SESSION['loco_wishlist_notice']??'');unset($_SESSION['loco_wishlist_notice']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!hash_equals($csrf,(string)($_POST['csrf']??'')))$error='The form expired. Refresh and try again.';
    else {
        $action=(string)($_POST['action']??'');
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
        if(!$id||$id<1)$error='Invalid saved item.';
        else {
            try{
                if($action==='toggle_game'){
                    $tab='games';
                    $q=$pdo->prepare('SELECT id FROM games WHERE id=:id');
                    $q->execute([':id'=>$id]);
                    if(!$q->fetchColumn())throw new RuntimeException('Game not found.');
                    $q=$pdo->prepare('SELECT id FROM game_wishlists WHERE user_id=:uid AND game_id=:gid LIMIT 1');
                    $q->execute([':uid'=>$userId,':gid'=>$id]);
                    if($q->fetchColumn()){
                        $q=$pdo->prepare('DELETE FROM game_wishlists WHERE user_id=:uid AND game_id=:gid');
                        $q->execute([':uid'=>$userId,':gid'=>$id]);
                        $message='Game removed from your wishlist.';
                    }else{
                        $q=$pdo->prepare('INSERT INTO game_wishlists(user_id,game_id) VALUES(:uid,:gid)');
                        $q->execute([':uid'=>$userId,':gid'=>$id]);
                        $message='Game added to your wishlist.';
                    }
                }elseif($action==='remove_market'){
                    $tab='market';
                    $q=$pdo->prepare('DELETE FROM marketplace_wishlists WHERE user_id=:uid AND listing_id=:lid');
                    $q->execute([':uid'=>$userId,':lid'=>$id]);
                    $message='Marketplace item removed from your wishlist.';
                }else throw new RuntimeException('Unknown wishlist action.');
                $_SESSION['loco_wishlist_notice']=$message;
                header('Location: wishlist.php?tab='.$tab);
                exit;
            }catch(PDOException $e){
                error_log('LOCO wishlist update: '.$e->getMessage());
                $error='Could not update saved items. Import database/game_wishlist.sql for game favorites.';
            }catch(RuntimeException $e){$error=$e->getMessage();}
        }
    }
}
$games=[];$market=[];$gamesError='';
try{
    $q=$pdo->prepare("SELECT g.id,g.title,g.description,g.image_url,g.price,
        (SELECT COUNT(*) FROM library l WHERE l.user_id=:owneduid AND l.game_id=g.id) AS owned
        FROM game_wishlists gw JOIN games g ON g.id=gw.game_id
        WHERE gw.user_id=:uid ORDER BY gw.created_at DESC,gw.id DESC");
    $q->execute([':owneduid'=>$userId,':uid'=>$userId]);
    $games=$q->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){error_log('LOCO game wishlist: '.$e->getMessage());$gamesError='Games Wishlist needs database/game_wishlist.sql imported into loco_db.';}
try{
    $q=$pdo->prepare("SELECT mw.listing_id,ml.status,ml.price,gi.item_name,gi.image_url,
             g.title AS game_title,u.username AS seller
        FROM marketplace_wishlists mw
        LEFT JOIN marketplace_listings ml ON ml.id=mw.listing_id
        LEFT JOIN game_items gi ON gi.id=ml.item_id
        LEFT JOIN games g ON g.id=gi.game_id
        LEFT JOIN users u ON u.id=ml.seller_id
        WHERE mw.user_id=:uid ORDER BY mw.id DESC");
    $q->execute([':uid'=>$userId]);
    $market=$q->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){error_log('LOCO marketplace wishlist: '.$e->getMessage());if(!$error)$error='Could not load marketplace saved items.';}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOCO | Wishlist</title><link rel="stylesheet" href="style.css"></head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="loco-community-wrap loco-wishlist-page">
  <div class="loco-community-heading">
    <div><p class="loco-eyebrow">LOCO STORE · YOUR COLLECTION</p><h1 class="loco-logo-text">Wishlist</h1><p>Your saved games and Community Marketplace items, organized in one place.</p></div>
  </div>
  <?php if($notice): ?><p class="loco-notice success" role="status"><?=wishlistEsc($notice)?></p><?php endif; ?>
  <?php if($error): ?><p class="loco-notice error" role="alert"><?=wishlistEsc($error)?></p><?php endif; ?>
  <section class="loco-community-panel">
    <div class="loco-wishlist-tabs" role="navigation" aria-label="Wishlist categories">
      <a href="wishlist.php?tab=games" class="loco-wishlist-tab <?=$tab==='games'?'active':''?>" <?=$tab==='games'?'aria-current="page"':''?>>Games (<?=count($games)?>)</a>
      <a href="wishlist.php?tab=market" class="loco-wishlist-tab <?=$tab==='market'?'active':''?>" <?=$tab==='market'?'aria-current="page"':''?>>Community Marketplace (<?=count($market)?>)</a>
    </div>
    <?php if($tab==='games'): ?>
      <h2 class="loco-sandbox-heading">Game Wishlist</h2>
      <?php if($gamesError): ?><p class="loco-notice error"><?=wishlistEsc($gamesError)?></p><?php endif; ?>
      <?php if(!$games&&!$gamesError): ?><p class="loco-empty-state">You haven't added any games yet. Visit the <a href="Products.php">Store</a> and open a game to add it.</p><?php endif; ?>
      <?php foreach($games as $game): ?>
      <article class="loco-wishlist-entry">
        <a href="game_details.php?id=<?=(int)$game['id']?>"><img src="<?=wishlistEsc($game['image_url']?:'images/gameplay_01.jpg')?>" alt="<?=wishlistEsc($game['title'])?>"></a>
        <div><h3><?=wishlistEsc($game['title'])?></h3><p><?=wishlistEsc(substr((string)($game['description']??''),0,150))?></p></div>
        <div class="loco-wishlist-actions">
          <span class="loco-wishlist-price"><?=(float)$game['price']>0?'₱'.number_format((float)$game['price'],2):'FREE'?></span>
          <a class="loco-wishlist-action" href="game_details.php?id=<?=(int)$game['id']?>"><?=$game['owned']?'IN LIBRARY':'VIEW / BUY'?></a>
          <form method="post" action="wishlist.php?tab=games"><input type="hidden" name="csrf" value="<?=wishlistEsc($csrf)?>"><input type="hidden" name="action" value="toggle_game"><input type="hidden" name="id" value="<?=(int)$game['id']?>"><button type="submit" class="loco-wishlist-remove">Remove</button></form>
        </div>
      </article>
      <?php endforeach; ?>
    <?php else: ?>
      <h2 class="loco-sandbox-heading">Saved Community Listings</h2>
      <?php if(!$market): ?><p class="loco-empty-state">No marketplace items saved yet. Visit <a href="community.php?tab=market">Marketplace</a> and select the heart on a listing.</p><?php endif; ?>
      <?php foreach($market as $item): ?>
      <?php $available=($item['status']??'')==='active'; ?>
      <article class="loco-wishlist-entry">
        <a href="community.php?tab=market"><img src="<?=wishlistEsc($item['image_url']?:'images/logo.png')?>" alt="<?=wishlistEsc($item['item_name']??'Marketplace collectible')?>"></a>
        <div><h3><?=wishlistEsc($item['item_name']??'Unavailable listing')?></h3>
          <p><?=wishlistEsc($item['game_title']??'Marketplace')?> · Seller: <?=wishlistEsc($item['seller']??'Unavailable')?></p>
          <?php if(!$available): ?><p style="color:#ff9f9f;font-weight:700">No longer available</p><?php endif; ?>
        </div>
        <div class="loco-wishlist-actions">
          <span class="loco-wishlist-price"><?=$available?'₱'.number_format((float)$item['price'],2):'UNAVAILABLE'?></span>
          <?php if($available): ?><a class="loco-wishlist-action" href="community.php?tab=market">VIEW LISTING / TRADE</a><?php endif; ?>
          <form method="post" action="wishlist.php?tab=market"><input type="hidden" name="csrf" value="<?=wishlistEsc($csrf)?>"><input type="hidden" name="action" value="remove_market"><input type="hidden" name="id" value="<?=(int)$item['listing_id']?>"><button class="loco-wishlist-remove" type="submit">Remove</button></form>
        </div>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>
</body></html>
