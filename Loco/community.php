<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

$currentUser = (string)$_SESSION['username'];
if (($_SESSION['loco_catalog_version'] ?? '') !== 'original-games-v2') {
    foreach (['loco_next_listing_id','loco_listings','loco_wishlist','loco_trades','loco_trade_history','loco_discussions','loco_reports','loco_next_trade_id','loco_next_post_id'] as $sessionKey) unset($_SESSION[$sessionKey]);
    $_SESSION['loco_catalog_version'] = 'original-games-v2';
}
if (!isset($_SESSION['loco_profile'])) $_SESSION['loco_profile'] = ['bio' => '', 'favorite_games' => ''];
// Inventory and marketplace ownership are stored in MySQL. Trade requests, discussions,
// and reports remain demo-only until their dedicated implementation steps.
if (!isset($_SESSION['loco_trades']) || !is_array($_SESSION['loco_trades'])) $_SESSION['loco_trades'] = [];
if (!isset($_SESSION['loco_trade_history']) || !is_array($_SESSION['loco_trade_history'])) $_SESSION['loco_trade_history'] = [];
if (!isset($_SESSION['loco_discussions']) || !is_array($_SESSION['loco_discussions'])) $_SESSION['loco_discussions'] = [];
if (!isset($_SESSION['loco_reports']) || !is_array($_SESSION['loco_reports'])) $_SESSION['loco_reports'] = [];
foreach (['loco_wishlist','loco_trades','loco_trade_history','loco_discussions','loco_reports'] as $key) {
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) $_SESSION[$key] = [];
}
if (!isset($_SESSION['loco_next_trade_id'])) $_SESSION['loco_next_trade_id'] = 1;
if (!isset($_SESSION['loco_next_post_id'])) $_SESSION['loco_next_post_id'] = 1;
if (empty($_SESSION['loco_csrf'])) $_SESSION['loco_csrf'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['loco_csrf'];
$notice = '';
$error = '';
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function redirectCommunity() { header('Location: community.php'); exit(); }
function postText($key, $max = 500) { $value = trim((string)($_POST[$key] ?? '')); return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Your session token expired. Refresh the page and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        switch ($action) {
            case 'save_profile':
                $_SESSION['loco_profile'] = ['bio' => postText('bio', 300), 'favorite_games' => postText('favorite_games', 180)];
                $notice = 'Profile saved for this session.';
                break;
            case 'create_listing':
                $title=postText('title',150); $game=postText('game',150); $type=postText('type',50);
                $condition=postText('condition',50); $description=postText('description',1000);
                $price=filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
                if($title===''||$game===''||$type===''||$description===''||$price===false||$price<=0||$price>10000000){$error='Enter all listing details and a valid price greater than zero.';break;}
                try {
                    $gs=$pdo->prepare('SELECT id FROM games WHERE title=:title LIMIT 1'); $gs->execute([':title'=>$game]); $g=$gs->fetch();
                    if(!$g){$error='Choose a game already listed in the LOCO store.';break;}
                    $pdo->beginTransaction();
                    $is=$pdo->prepare('INSERT INTO game_items (game_id,item_name,description,rarity,image_url) VALUES (:gid,:name,:description,:rarity,NULL)');
                    $is->execute([':gid'=>(int)$g['id'],':name'=>$title,':description'=>$description,':rarity'=>($condition?:$type)]);
                    $itemId=(int)$pdo->lastInsertId();
                    $inv=$pdo->prepare('INSERT INTO inventories (user_id,item_id) VALUES (:uid,:iid)');
                    $inv->execute([':uid'=>(int)$_SESSION['user_id'],':iid'=>$itemId]);
                    $inventoryId=(int)$pdo->lastInsertId();
                    $ls=$pdo->prepare("INSERT INTO marketplace_listings (seller_id,item_id,inventory_id,price,status) VALUES (:uid,:iid,:inventory_id,:price,'active')");
                    $ls->execute([':uid'=>(int)$_SESSION['user_id'],':iid'=>$itemId,':inventory_id'=>$inventoryId,':price'=>(float)$price]);
                    $pdo->commit();
                    $notice='Listing published and the item was added to your database inventory.';
                } catch(PDOException $e){
                    if($pdo->inTransaction())$pdo->rollBack();
                    error_log('LOCO marketplace listing creation failed: '.$e->getMessage());
                    $error='Unable to save listing. Ensure the marketplace and community inventory migrations have been imported.';
                }
                break;
            case 'edit_listing':
                $id=(int)($_POST['listing_id']??0); $title=postText('title',150); $game=postText('game',150);
                $type=postText('type',50); $condition=postText('condition',50); $description=postText('description',1000);
                $price=filter_var($_POST['price']??'',FILTER_VALIDATE_FLOAT);
                if($id<=0||$title===''||$game===''||$type===''||$description===''||$price===false||$price<0){$error='Complete every field and enter a valid price.';break;}
                try {
                    $chk=$pdo->prepare("SELECT item_id FROM marketplace_listings WHERE id=:id AND seller_id=:uid AND status='active' LIMIT 1");
                    $chk->execute([':id'=>$id,':uid'=>(int)$_SESSION['user_id']]); $own=$chk->fetch();
                    $gs=$pdo->prepare('SELECT id FROM games WHERE title=:title LIMIT 1');$gs->execute([':title'=>$game]);$g=$gs->fetch();
                    if(!$own){$error='You can only edit your own active listings.';break;}
                    if(!$g){$error='Choose a game already listed in the LOCO store.';break;}
                    $up=$pdo->prepare('UPDATE game_items SET game_id=:gid,item_name=:name,description=:description,rarity=:rarity WHERE id=:iid');
                    $up->execute([':gid'=>(int)$g['id'],':name'=>$title,':description'=>$description,':rarity'=>($condition?:$type),':iid'=>(int)$own['item_id']]);
                    $up=$pdo->prepare('UPDATE marketplace_listings SET price=:price WHERE id=:id AND seller_id=:uid');
                    $up->execute([':price'=>(float)$price,':id'=>$id,':uid'=>(int)$_SESSION['user_id']]);$notice='Listing updated in the database.';
                }catch(PDOException $e){$error='Unable to update this listing.';}
                break;
            case 'delete_listing':
                $id=(int)($_POST['listing_id']??0);
                $del=$pdo->prepare("UPDATE marketplace_listings SET status='removed' WHERE id=:id AND seller_id=:uid AND status='active'");
                $del->execute([':id'=>$id,':uid'=>(int)$_SESSION['user_id']]);
                if($del->rowCount())$notice='Listing removed from the marketplace.';else $error='You can only remove your own active listing.';
                break;
            case 'buy_listing':
                $listingId=(int)($_POST['listing_id']??0);
                $buyerId=(int)($_SESSION['user_id']??0);
                try {
                    $pdo->beginTransaction();
                    $q=$pdo->prepare("SELECT id,seller_id,item_id,inventory_id,price FROM marketplace_listings WHERE id=:id AND status='active' FOR UPDATE");
                    $q->execute([':id'=>$listingId]); $listing=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$listing || !$listing['inventory_id'] || (float)$listing['price']<=0) throw new RuntimeException('This item is not available for purchase.');
                    if((int)$listing['seller_id']===$buyerId) throw new RuntimeException('You cannot buy your own listing.');
                    $q=$pdo->prepare('SELECT id,user_id,item_id FROM inventories WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>(int)$listing['inventory_id']]); $owned=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$owned || (int)$owned['user_id']!==(int)$listing['seller_id'] || (int)$owned['item_id']!==(int)$listing['item_id']) throw new RuntimeException('Seller no longer owns this item.');
                    $q=$pdo->prepare('SELECT id,wallet_balance FROM users WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>$buyerId]); $buyer=$q->fetch(PDO::FETCH_ASSOC);
                    $price=(float)$listing['price'];
                    if(!$buyer || (float)$buyer['wallet_balance']<$price) throw new RuntimeException('Insufficient LOCO wallet balance.');
                    $q=$pdo->prepare('SELECT id FROM users WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>(int)$listing['seller_id']]);
                    if(!$q->fetch()) throw new RuntimeException('Seller account not found.');
                    $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance-:price WHERE id=:id AND wallet_balance>=:minimum');
                    $q->execute([':price'=>$price,':id'=>$buyerId,':minimum'=>$price]);
                    if($q->rowCount()!==1) throw new RuntimeException('Insufficient LOCO wallet balance.');
                    $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance+:price WHERE id=:id');
                    $q->execute([':price'=>$price,':id'=>(int)$listing['seller_id']]);
                    $q=$pdo->prepare('UPDATE inventories SET user_id=:buyer WHERE id=:id AND user_id=:seller');
                    $q->execute([':buyer'=>$buyerId,':id'=>(int)$listing['inventory_id'],':seller'=>(int)$listing['seller_id']]);
                    if($q->rowCount()!==1) throw new RuntimeException('Item ownership changed. Please try again.');
                    $q=$pdo->prepare("UPDATE marketplace_listings SET status='sold' WHERE id=:id AND status='active'");
                    $q->execute([':id'=>$listingId]);
                    if($q->rowCount()!==1) throw new RuntimeException('Listing is no longer available.');
                    $pdo->commit();
                    $notice='Purchase complete. Item added to your inventory and payment sent to the seller.';
                } catch (Throwable $e) {
                    if($pdo->inTransaction()) $pdo->rollBack();
                    if($e instanceof PDOException){error_log('LOCO marketplace purchase failed: '.$e->getMessage());$error='Purchase could not be completed. Check the database setup.';}
                    else $error=$e->getMessage();
                }
                break;
            case 'toggle_wishlist':
                $id=(int)($_POST['listing_id']??0);
                try{
                    $chk=$pdo->prepare("SELECT id FROM marketplace_listings WHERE id=:id AND status='active' LIMIT 1");$chk->execute([':id'=>$id]);
                    if(!$chk->fetch()){$error='That listing is no longer available.';break;}
                    $chk=$pdo->prepare('SELECT id FROM marketplace_wishlists WHERE user_id=:uid AND listing_id=:lid LIMIT 1');$chk->execute([':uid'=>(int)$_SESSION['user_id'],':lid'=>$id]);
                    if($chk->fetch()){$del=$pdo->prepare('DELETE FROM marketplace_wishlists WHERE user_id=:uid AND listing_id=:lid');$del->execute([':uid'=>(int)$_SESSION['user_id'],':lid'=>$id]);$notice='Removed from wishlist.';}
                    else{$ins=$pdo->prepare('INSERT INTO marketplace_wishlists(user_id,listing_id) VALUES(:uid,:lid)');$ins->execute([':uid'=>(int)$_SESSION['user_id'],':lid'=>$id]);$notice='Listing saved to your database wishlist.';}
                }catch(PDOException $e){$error='Import database/marketplace_database.sql to enable wishlist storage.';}
                break;
            case 'request_trade':
                $id=(int)($_POST['listing_id']??0); $offerId=(int)($_POST['offer_inventory_id']??0);
                $q=$pdo->prepare("SELECT ml.id,ml.seller_id,gi.item_name AS title,u.username AS seller FROM marketplace_listings ml JOIN game_items gi ON gi.id=ml.item_id JOIN users u ON u.id=ml.seller_id WHERE ml.id=:id AND ml.status='active' LIMIT 1");
                $q->execute([':id'=>$id]); $listing=$q->fetch();
                if(!$listing)$error='That listing is no longer available.';
                elseif((int)$listing['seller_id']===(int)$_SESSION['user_id'])$error='You cannot send a trade request for your own listing.';
                else{
                    $offer=null;
                    if($offerId>0){
                        $owned=$pdo->prepare("SELECT gi.item_name AS name,g.title AS game FROM inventories i JOIN game_items gi ON gi.id=i.item_id JOIN games g ON g.id=gi.game_id WHERE i.id=:id AND i.user_id=:uid AND NOT EXISTS (SELECT 1 FROM marketplace_listings ml WHERE ml.inventory_id=i.id AND ml.status='active') LIMIT 1");
                        $owned->execute([':id'=>$offerId,':uid'=>(int)$_SESSION['user_id']]);
                        $offer=$owned->fetch()?:null;
                        if(!$offer){$error='That offer item is not in your available inventory.';break;}
                    }
                    $tradeId=$_SESSION['loco_next_trade_id']++;
                    $offerName=$offer?$offer['name'].' ('.$offer['game'].')':'No item selected (purchase inquiry)';
                    $_SESSION['loco_trades'][$tradeId]=['id'=>$tradeId,'listing_id'=>$id,'listing_title'=>$listing['title'],'seller'=>$listing['seller'],'requester'=>$currentUser,'offer_item'=>$offerName,'offer_inventory_id'=>$offerId ?: null,'status'=>'Pending','created'=>time()];
                    $_SESSION['loco_trade_history'][]=['id'=>$tradeId,'listing_title'=>$listing['title'],'seller'=>$listing['seller'],'requester'=>$currentUser,'offer_item'=>$offerName,'status'=>'Pending','updated'=>time()];
                    $notice='Demo trade request created using your database-owned inventory. Requests and transfers are still session-based.';
                }
                break;
            case 'update_trade':
                $id = (int)($_POST['trade_id'] ?? 0); $status = (string)($_POST['status'] ?? ''); $trade = $_SESSION['loco_trades'][$id] ?? null;
                if (!$trade || !in_array($status, ['Accepted','Rejected','Cancelled'], true)) $error = 'Trade request could not be updated.';
                elseif (($status === 'Cancelled' && $trade['requester'] !== $currentUser) || (in_array($status, ['Accepted','Rejected'], true) && $trade['seller'] !== $currentUser)) $error = 'You are not allowed to update this request.';
                elseif ($trade['status'] !== 'Pending') $error = 'This trade request has already been resolved.';
                else { $_SESSION['loco_trades'][$id]['status'] = $status; foreach ($_SESSION['loco_trade_history'] as &$hist) if ((int)$hist['id'] === $id) { $hist['status'] = $status; $hist['updated'] = time(); } unset($hist); $notice = 'Trade request marked '.$status.'. No real item or money was transferred.'; }
                break;
            case 'add_inventory':
                $name=postText('item_name',150); $game=postText('item_game',150); $type=postText('item_type',50); $quantity=filter_var($_POST['quantity']??1,FILTER_VALIDATE_INT);
                if($name===''||$game===''||$type===''||$quantity===false||$quantity<1||$quantity>999){$error='Enter valid inventory details.';break;}
                try{
                    $gs=$pdo->prepare('SELECT id FROM games WHERE title=:title LIMIT 1');$gs->execute([':title'=>$game]);$g=$gs->fetch();
                    if(!$g){$error='Choose a game already listed in the LOCO store.';break;}
                    $pdo->beginTransaction();
                    $is=$pdo->prepare('INSERT INTO game_items (game_id,item_name,description,rarity,image_url) VALUES (:gid,:name,:description,:rarity,NULL)');
                    $is->execute([':gid'=>(int)$g['id'],':name'=>$name,':description'=>'User-added inventory item',':rarity'=>$type]);
                    $itemId=(int)$pdo->lastInsertId();
                    $inv=$pdo->prepare('INSERT INTO inventories (user_id,item_id) VALUES (:uid,:iid)');
                    for($n=0;$n<$quantity;$n++)$inv->execute([':uid'=>(int)$_SESSION['user_id'],':iid'=>$itemId]);
                    $pdo->commit();
                    $notice='Inventory item saved to your account.';
                }catch(PDOException $e){
                    if($pdo->inTransaction())$pdo->rollBack();
                    error_log('LOCO inventory creation failed: '.$e->getMessage());
                    $error='Unable to save inventory item. Check that the database schema is up to date.';
                }
                break;
            case 'remove_inventory':
                $id=(int)($_POST['inventory_id']??0);
                $del=$pdo->prepare("DELETE FROM inventories WHERE id=:id AND user_id=:uid AND NOT EXISTS (SELECT 1 FROM marketplace_listings WHERE inventory_id=:id_check AND status='active')");
                $del->execute([':id'=>$id,':uid'=>(int)$_SESSION['user_id'],':id_check'=>$id]);
                if($del->rowCount())$notice='Inventory item removed from your account.';
                else{
                    $listed=$pdo->prepare("SELECT id FROM marketplace_listings WHERE inventory_id=:id AND status='active' LIMIT 1");$listed->execute([':id'=>$id]);
                    $error=$listed->fetch()?'Remove the active marketplace listing before deleting this inventory item.':'Inventory item not found in your account.';
                }
                break;
            case 'create_post':
                $title = postText('post_title', 100); $body = postText('post_body', 1200); $tag = postText('post_tag', 40);
                if ($title === '' || $body === '') $error = 'Enter a discussion title and message.';
                else { $id = $_SESSION['loco_next_post_id']++; $_SESSION['loco_discussions'][$id] = ['id'=>$id,'title'=>$title,'body'=>$body,'tag'=>$tag ?: 'General','author'=>$currentUser,'created'=>time(),'replies'=>[]]; $notice = 'Discussion posted.'; }
                break;
            case 'reply_post':
                $id = (int)($_POST['post_id'] ?? 0); $reply = postText('reply', 500);
                if (!isset($_SESSION['loco_discussions'][$id]) || $reply === '') $error = 'Enter a reply to an existing discussion.';
                else { $_SESSION['loco_discussions'][$id]['replies'][] = ['author'=>$currentUser,'body'=>$reply,'created'=>time()]; $notice = 'Reply added.'; }
                break;
            case 'report_listing':
                $id=(int)($_POST['listing_id']??0); $reason=postText('reason',250);
                $q=$pdo->prepare("SELECT gi.item_name AS title FROM marketplace_listings ml JOIN game_items gi ON gi.id=ml.item_id WHERE ml.id=:id AND ml.status='active' LIMIT 1");
                $q->execute([':id'=>$id]); $listing=$q->fetch();
                if(!$listing||$reason==='')$error='Choose an active listing and provide a reason.';
                else{$notice='Report recorded for this session. Database-backed moderator reports are not implemented yet.';}
                break;
            default: $error = 'Unknown action.';
        }
    }
}

$tab=(string)($_GET['tab']??'market');
$allowedTabs=['market','sell','saved','trades','inventory','discussions','profile'];
if(!in_array($tab,$allowedTabs,true))$tab='market';
$editId=(int)($_GET['edit']??0);$editListing=null;
$inventoryItems=[];$tradeInventoryItems=[];
try{
    $q=$pdo->query("SELECT ml.id,ml.seller_id,ml.item_id,ml.inventory_id,ml.price,UNIX_TIMESTAMP(ml.created_at) AS created,gi.item_name AS title,gi.description,gi.rarity,gi.image_url,g.title AS game,u.username AS seller FROM marketplace_listings ml JOIN game_items gi ON gi.id=ml.item_id JOIN games g ON g.id=gi.game_id JOIN users u ON u.id=ml.seller_id WHERE ml.status='active' ORDER BY ml.created_at DESC,ml.id DESC");
    $listings=$q->fetchAll();
    foreach($listings as &$item){$item['type']=$item['rarity']??'Collectible';$item['condition']=$item['rarity']??'Good';$item['rating']='New seller';}unset($item);
    $myListings=array_values(array_filter($listings,fn($item)=>(int)$item['seller_id']===(int)$_SESSION['user_id']));
    $q=$pdo->prepare('SELECT listing_id FROM marketplace_wishlists WHERE user_id=:uid');$q->execute([':uid'=>(int)$_SESSION['user_id']]);$savedListingIds=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
    $gameOptions=$pdo->query('SELECT title FROM games ORDER BY title')->fetchAll(PDO::FETCH_COLUMN);
    $inventoryStmt=$pdo->prepare("SELECT i.id AS inventory_id,i.item_id,gi.item_name AS title,gi.description,gi.rarity,g.title AS game,gi.image_url,(SELECT ml.id FROM marketplace_listings ml WHERE ml.inventory_id=i.id AND ml.status='active' LIMIT 1) AS active_listing_id FROM inventories i JOIN game_items gi ON gi.id=i.item_id JOIN games g ON g.id=gi.game_id WHERE i.user_id=:uid ORDER BY i.acquired_at DESC,i.id DESC");
    $inventoryStmt->execute([':uid'=>(int)$_SESSION['user_id']]);$inventoryItems=$inventoryStmt->fetchAll();
    $tradeInventoryItems=array_values(array_filter($inventoryItems,fn($item)=>empty($item['active_listing_id'])));
    if($editId>0){$q=$pdo->prepare("SELECT ml.id,ml.seller_id,ml.item_id,ml.inventory_id,ml.price,gi.item_name AS title,gi.description,gi.rarity,g.title AS game FROM marketplace_listings ml JOIN game_items gi ON gi.id=ml.item_id JOIN games g ON g.id=gi.game_id WHERE ml.id=:id AND ml.seller_id=:uid AND ml.status='active' LIMIT 1");$q->execute([':id'=>$editId,':uid'=>(int)$_SESSION['user_id']]);$editListing=$q->fetch()?:null;}
}catch(PDOException $e){$listings=$myListings=$savedListingIds=$gameOptions=$inventoryItems=$tradeInventoryItems=[];$error=$error?:'Marketplace database is not ready. Import the marketplace and community inventory migrations.';}
$myTrades=array_filter($_SESSION['loco_trades'],fn($trade)=>$trade['requester']===$currentUser||$trade['seller']===$currentUser);
$myHistory=array_reverse($_SESSION['loco_trade_history']);
$myPosts=array_reverse($_SESSION['loco_discussions'],true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Community & Marketplace</title><link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>

<div class="loco-community-wrap">
    <div class="loco-community-heading">
        <div>
            <p class="loco-eyebrow">LOCO PLAYER NETWORK</p>
            <h1 class="loco-logo-text">Community & Trading Hub</h1>
            <p>Trade demo items, manage listings, save finds, and talk with other players.</p>
        </div>
        <div class="loco-community-user">
            <span class="loco-avatar">🎮</span>
            <div><strong><?=h($currentUser)?></strong>
                <small><?=count($myListings)?> listing(s) · <?=count($inventoryItems)?> inventory item(s)</small>
            </div>
        </div>
    </div>
</div>
    <?php if ($notice): ?><div class="loco-notice success" role="status">✓ <?=h($notice)?></div><?php endif; ?>
    <?php if ($error): ?><div class="loco-notice error" role="alert">! <?=h($error)?></div><?php endif; ?>

    <nav class="loco-community-tabs" aria-label="Community sections">
        <?php foreach (['market'=>'Marketplace','sell'=>'My Listings','saved'=>'Wishlist','trades'=>'Trade Center','inventory'=>'Inventory','discussions'=>'Discussions','profile'=>'My Profile'] as $key=>$label): ?><a class="<?= $tab===$key?'active':'' ?>" href="community.php?tab=<?=h($key)?>"><?=h($label)?></a><?php endforeach; ?>
    </nav>

    <?php if ($tab === 'market'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Marketplace</h2><p>Compare item details, asking prices, and sellers before sending a demo request.</p></div><a class="loco-brand-gloss-btn" href="community.php?tab=sell">＋ Create listing</a></div>
        <div class="loco-market-tools">
            <label class="loco-market-search-wrap">
                <span class="sr-only">Search listings</span>
                <input id="market-search" class="loco-input" type="search" placeholder="Search item, game, type, or seller…">
            </label>
            <select id="market-game" class="loco-input" aria-label="Filter by game">
                <option value="all">All games</option>
                <?php foreach (array_unique(array_column($listings,'game')) as $game): ?>
                    <option value="<?=h(strtolower($game))?>"><?=h($game)?></option><?php endforeach; ?>
                </select>
                <select id="market-sort" class="loco-input" aria-label="Sort listings">
                    <option value="newest">Newest first</option>
                    <option value="price-low">Price: low to high</option>
                    <option value="price-high">Price: high to low</option>
                    <option value="title">Title: A–Z</option>
                </select>
        </div>
        <p id="market-results-count" class="loco-filter-status" aria-live="polite"></p>
        <div class="loco-listing-grid" id="listing-grid">
        <?php if (!$listings): ?><div class="loco-empty-state">No listings yet. Be the first to <a href="community.php?tab=sell">create one</a>.</div><?php endif; ?>
        <?php foreach ($listings as $item): $saved = in_array((int)$item['id'], $savedListingIds, true); ?>
            <article class="loco-listing-card" data-title="<?=h(strtolower($item['title']))?>" data-game="<?=h(strtolower($item['game']))?>" data-type="<?=h(strtolower($item['type']))?>" data-seller="<?=h(strtolower($item['seller']))?>" data-price="<?=h($item['price'])?>" data-created="<?=h($item['created'])?>">
                <div class="loco-listing-card-top"><span class="loco-market-tag"><?=h($item['game'])?></span><form method="post" class="loco-inline-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_wishlist"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-icon-button" title="<?=$saved?'Remove from wishlist':'Save listing'?>" aria-label="<?=$saved?'Remove from wishlist':'Save listing'?>"><?=$saved?'♥':'♡'?></button></form></div>
                <img class="loco-market-item-image" src="<?=h($item['image_url'] ?: 'images/market-item-default.svg')?>" alt="<?=h($item['title'])?> item artwork" loading="lazy"><h3><?=h($item['title'])?></h3><p class="loco-listing-description"><?=h($item['description'])?></p>
                <div class="loco-listing-meta">
                    <span><?=h($item['type'])?></span>
                    <span><?=h($item['condition'])?></span>
                </div>
                <div class="loco-listing-seller"><span class="loco-avatar small">👤</span><div><strong><?=h($item['seller'])?></strong><small><?=$item['rating']==='New seller'?'New seller':'★ '.h($item['rating']).' seller rating'?></small></div></div>
                <div class="loco-listing-bottom"><strong class="loco-listing-price">₱<?=number_format((float)$item['price'],2)?></strong><?php if ((int)$item['seller_id'] === (int)$_SESSION['user_id']): ?><a class="loco-small-link" href="community.php?tab=sell&edit=<?=$item['id']?>">Edit listing</a><?php else: ?><div><form method="post" class="loco-inline-form" onsubmit="return confirm('Buy this item for ₱<?=number_format((float)$item['price'],2)?> using your LOCO wallet?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="buy_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-button-small" type="submit">Buy Now</button></form> <button class="loco-button-small" type="button" onclick="openTrade(<?=$item['id']?>, <?=json_encode($item['title'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)">Request trade</button></div><?php endif; ?></div>
                <details class="loco-report-details"><summary>Report listing</summary><form method="post" class="loco-report-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="report_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><input name="reason" maxlength="250" placeholder="Why are you reporting this?" required><button class="loco-small-link" type="submit">Submit report</button></form></details>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
    <?php elseif ($tab === 'sell'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2><?=$editListing?'Edit listing':'Create a listing'?></h2><p>Only listings owned by your account can be edited or removed.</p></div></div>
        <form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="<?=$editListing?'edit_listing':'create_listing'?>"><?php if($editListing): ?><input type="hidden" name="listing_id" value="<?=$editListing['id']?>"><?php endif; ?>
            <label>Item title<input name="title" maxlength="90" required value="<?=h($editListing['title']??'')?>" placeholder="e.g. Crimson Blade Skin"></label>
            <label>Game<select name="game" required><option value="">Choose a game</option><?php foreach($gameOptions as $gameOption): ?><option value="<?=h($gameOption)?>" <?=($editListing['game']??'')===$gameOption?'selected':''?>><?=h($gameOption)?></option><?php endforeach; ?></select></label>
            <label>Item type<input name="type" maxlength="45" required value="<?=h($editListing['type']??'')?>" placeholder="Skin, collectible, equipment…"></label>
            <label>Condition<select name="condition"><option <?=($editListing['condition']??'')==='New'?'selected':''?>>New</option><option <?=($editListing['condition']??'')==='Excellent'?'selected':''?>>Excellent</option><option <?=($editListing['condition']??'')==='Very good'?'selected':''?>>Very good</option><option <?=($editListing['condition']??'')==='Good'?'selected':''?>>Good</option><option <?=($editListing['condition']??'')==='Used'?'selected':''?>>Used</option></select></label>
            <label>Asking price (PHP)<input name="price" type="number" min="0.01" max="10000000" step="0.01" required value="<?=h($editListing['price']??'')?>" placeholder="500.00"></label>
            <label class="loco-full-width">Description<textarea name="description" maxlength="400" rows="4" required placeholder="Describe the item and what you are offering."><?=h($editListing['description']??'')?></textarea></label>
            <div class="loco-form-actions"><button class="loco-brand-gloss-btn" type="submit"><?=$editListing?'Save changes':'Publish listing'?></button><?php if($editListing): ?><a class="loco-secondary-btn" href="community.php?tab=sell">Cancel edit</a><?php endif; ?></div>
        </form>
        <h3 class="loco-subheading">Your listings</h3><div class="loco-owned-listings"><?php if(!$myListings): ?><p class="loco-muted">You haven't created any listings yet.</p><?php endif; ?><?php foreach($myListings as $item): ?><div class="loco-owned-row"><div><strong><?=h($item['title'])?></strong><small><?=h($item['game'])?> · ₱<?=number_format($item['price'],2)?></small></div><div class="loco-row-actions"><a class="loco-small-link" href="community.php?tab=sell&edit=<?=$item['id']?>">Edit</a><form method="post" onsubmit="return confirm('Remove this listing?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-danger-link">Remove</button></form></div></div><?php endforeach; ?></div>
    </section>
    <?php elseif ($tab === 'saved'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Wishlist & saved listings</h2><p>Saved listings are linked to your account in the database.</p></div></div><div class="loco-owned-listings">
    <?php $savedItems=array_values(array_filter($listings,fn($item)=>in_array((int)$item['id'],$savedListingIds,true))); ?>
    <?php if(!$savedItems): ?><p class="loco-empty-state">Your wishlist is empty. Save a listing from the Marketplace using the heart icon.</p><?php endif; ?>
    <?php foreach($savedItems as $item): ?><div class="loco-owned-row"><div><strong><?=h($item['title'])?></strong><small><?=h($item['game'])?> · <?=h($item['seller'])?> · ₱<?=number_format((float)$item['price'],2)?></small></div><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_wishlist"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-danger-link">Remove saved</button></form></div><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'trades'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Trade center & history</h2><p>Review requests sent by you or addressed to your username. Actions are simulated; no items or money move.</p></div></div><div class="loco-trade-list"><?php if(!$myTrades): ?><p class="loco-empty-state">No trade requests yet. Visit the Marketplace to request a trade.</p><?php endif; ?><?php foreach(array_reverse($myTrades, true) as $trade): ?><article class="loco-trade-card"><div><span class="loco-status-pill <?=strtolower($trade['status'])?>"><?=h($trade['status'])?></span><h3><?=h($trade['listing_title'])?></h3><p>From <?=h($trade['requester'])?> to <?=h($trade['seller'])?></p><small>Offer: <?=h($trade['offer_item'])?> · <?=date('M j, Y g:i A',$trade['created'])?></small></div><div class="loco-row-actions"><?php if($trade['status']==='Pending' && $trade['seller']===$currentUser): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Accepted"><button class="loco-button-small">Accept</button></form><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Rejected"><button class="loco-danger-link">Reject</button></form><?php elseif($trade['status']==='Pending' && $trade['requester']===$currentUser): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Cancelled"><button class="loco-danger-link">Cancel request</button></form><?php endif; ?></div></article><?php endforeach; ?></div>
        <h3 class="loco-subheading">Trade history</h3><div class="loco-owned-listings"><?php if(!$myHistory): ?><p class="loco-muted">Resolved and pending trade activity will appear here.</p><?php endif; ?><?php foreach($myHistory as $history): if($history['requester']!==$currentUser && $history['seller']!==$currentUser) continue; ?><div class="loco-owned-row"><div><strong><?=h($history['listing_title'])?></strong><small><?=h($history['requester'])?> → <?=h($history['seller'])?> · <?=h($history['offer_item'])?></small></div><span class="loco-status-pill <?=strtolower($history['status'])?>"><?=h($history['status'])?></span></div><?php endforeach; ?></div>
    </section>
    <?php elseif ($tab === 'inventory'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>My inventory</h2><p>Items here are saved to your account in the database. Items with an active listing stay in your inventory but cannot be removed until the listing is removed.</p></div></div><form method="post" class="loco-form-grid compact"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="add_inventory"><label>Item name<input name="item_name" maxlength="150" required></label><label>Game<select name="item_game" required><option value="">Choose a game</option><?php foreach($gameOptions as $gameOption): ?><option value="<?=h($gameOption)?>"><?=h($gameOption)?></option><?php endforeach; ?></select></label><label>Type<input name="item_type" maxlength="50" required placeholder="Skin, collectible…"></label><label>Quantity<input name="quantity" type="number" min="1" max="999" value="1" required></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Add to inventory</button></div></form><div class="loco-inventory-grid"><?php if(!$inventoryItems): ?><p class="loco-empty-state">Your inventory is empty. Add an item above or publish a listing to create an owned item.</p><?php endif; ?><?php foreach($inventoryItems as $item): ?><article class="loco-inventory-card"><span class="loco-listing-symbol">✦</span><h3><?=h($item['title'])?></h3><p><?=h($item['game'])?></p><span class="loco-market-tag"><?=h($item['rarity']??'Item')?></span><?php if(!empty($item['active_listing_id'])): ?><p class="loco-session-note">Listed on marketplace</p><a class="loco-small-link" href="community.php?tab=sell">Manage listing</a><?php else: ?><form method="post" onsubmit="return confirm('Remove this inventory item?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="remove_inventory"><input type="hidden" name="inventory_id" value="<?=$item['inventory_id']?>"><button class="loco-danger-link">Remove item</button></form><?php endif; ?></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'discussions'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Community discussions</h2><p>Ask questions, share tips, or start a topic with other players using this session.</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create_post"><label>Discussion title<input name="post_title" maxlength="100" required placeholder="What do you want to discuss?"></label><label>Category<select name="post_tag"><option>General</option><option>Trading</option><option>Game help</option><option>Showcase</option><option>Feedback</option></select></label><label class="loco-full-width">Message<textarea name="post_body" maxlength="1200" rows="4" required></textarea></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Post discussion</button></div></form><div class="loco-discussion-list"><?php if(!$myPosts): ?><p class="loco-empty-state">No discussions yet. Start the first conversation above.</p><?php endif; ?><?php foreach($myPosts as $post): ?><article class="loco-discussion-card"><div class="loco-discussion-meta"><span class="loco-market-tag"><?=h($post['tag'])?></span><small>By <?=h($post['author'])?> · <?=date('M j, Y g:i A',$post['created'])?></small></div><h3><?=h($post['title'])?></h3><p><?=nl2br(h($post['body']))?></p><details><summary>Replies (<?=count($post['replies'])?>)</summary><?php foreach($post['replies'] as $reply): ?><div class="loco-reply"><strong><?=h($reply['author'])?></strong><p><?=nl2br(h($reply['body']))?></p></div><?php endforeach; ?><form method="post" class="loco-reply-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="reply_post"><input type="hidden" name="post_id" value="<?=$post['id']?>"><input name="reply" maxlength="500" placeholder="Write a reply…" required><button class="loco-button-small">Reply</button></form></details></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'profile'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Gamer profile</h2><p>Your profile information is saved in the current PHP session.</p></div></div><div class="loco-profile-summary"><span class="loco-avatar large">🎮</span><div><h3><?=h($currentUser)?></h3><p><?=count($myListings)?> listings · <?=count($inventoryItems)?> inventory item instances · <?=count($myTrades)?> trade requests · <?=count($_SESSION['loco_discussions'])?> discussion posts in this session</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_profile"><label class="loco-full-width">About me<textarea name="bio" maxlength="300" rows="4" placeholder="Tell the community about yourself…"><?=h($_SESSION['loco_profile']['bio'])?></textarea></label><label class="loco-full-width">Favorite games<input name="favorite_games" maxlength="180" value="<?=h($_SESSION['loco_profile']['favorite_games'])?>" placeholder="e.g. RPGs, strategy, co-op games"></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Save profile</button></div></form><div class="loco-session-note">Session-based demo: data is not shared between separate browsers or devices and may disappear when the session expires.</div></section>
    <?php endif; ?>

    <section class="loco-workshop-keep"><div class="loco-section-heading"><div><h2>Creations Hub</h2><p>Workshop preview retained from the original LOCO page. These buttons are demo interactions and do not download files.</p></div></div><div class="loco-workshop-grid"><article class="loco-workshop-card"><img src="images/game_cover_03.jpg" class="loco-workshop-thumb" alt="Moonroot Hollow original cover art"><h3>Moonroot Hollow: Mooncap Grove Expansion</h3><button type="button" class="loco-workshop-subscribe" data-workshop-id="moonroot-grove">＋ Subscribe</button></article><article class="loco-workshop-card"><img src="images/game_cover_02.jpg" class="loco-workshop-thumb" alt="Crimson Freight original cover art"><h3>Crimson Freight: Midnight Locomotive Skin</h3><button type="button" class="loco-workshop-subscribe" data-workshop-id="crimson-midnight-loco">＋ Subscribe</button></article></div></section>
</div>
<footer class="loco-footer-bar">
        <div class="loco-footer-bar">
        <div class="loco-sub-footer-inner">
            <div class="loco-footer-brand-col">
                <div class="loco-footer-logos">
                    <span class="loco-footer-logo-text">LOCO</span>
                    <span class="loco-footer-logo-sub">Stoker Studios</span>
                </div>
                <p class="loco-copyright-text">© 2026 Stoker Studios. All rights reserved. All trademarks are property of their respective owners in the PH and other countries.</p>
                <div class="loco-social-row">
                    <a href="#youtube" class="loco-social-link">YT</a>
                    <a href="#twitch" class="loco-social-link">TW</a>
                    <a href="#facebook" class="loco-social-link">FB</a>
                    <a href="#x" class="loco-social-link">X</a>
                </div>
            </div>
            <div class="loco-footer-links-grid">
                <div class="loco-links-col">
                    <h4>LOCO</h4>
                    <a href="#">About Loco</a>
                    <a href="#">System Status</a>
                    <a href="#">Track Routes</a>
                    <a href="#">Dev Blog</a>
                </div>
                <div class="loco-links-col">
                    <h4>SERVICES</h4>
                    <a href="#">API Portal</a>
                    <a href="#">Careers</a>
                    <a href="#">Hardware Specs</a>
                    <a href="#">Enterprise</a>
                </div>
                <div class="loco-links-col">
                    <h4>LEGAL</h4>
                    <a href="#">Privacy</a>
                    <a href="#">Accessibility</a>
                    <a href="#">Notices & Policies</a>
                    <a href="#">Cookies</a>
                </div>
                <div class="loco-links-col">
                    <h4>SUPPORT</h4>
                    <a href="#">Get Support</a>
                    <a href="#">My Account</a>
                    <a href="#">Documentation</a>
                    <a href="#">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
    </footer>

<div class="loco-modal" id="trade-modal" aria-hidden="true"><div class="loco-modal-card" role="dialog" aria-modal="true" aria-labelledby="trade-modal-title"><button type="button" class="loco-modal-close" onclick="closeTrade()" aria-label="Close">×</button><p class="loco-eyebrow">TRADE REQUEST</p><h2 id="trade-modal-title">Request a trade</h2><p id="trade-listing-name"></p><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="request_trade"><input type="hidden" name="listing_id" id="trade-listing-id"><label>Offer an inventory item (optional)<select name="offer_inventory_id"><option value="0">No item — purchase inquiry only</option><?php foreach($tradeInventoryItems as $item): ?><option value="<?=$item['inventory_id']?>"><?=h($item['title'])?> (<?=h($item['game'])?>)</option><?php endforeach; ?></select></label><p class="loco-session-note">Trade requests are still a demo. This offer is checked against your database-owned inventory, but no item or money is transferred yet.</p><button class="loco-brand-gloss-btn" type="submit">Send request</button></form></div></div>
<script>
    (function(){
    const search=document.getElementById('market-search'), 
    game=document.getElementById('market-game'), 
    sort=document.getElementById('market-sort'), 
    grid=document.getElementById('listing-grid'), 
    count=document.getElementById('market-results-count');

    if(search && game && sort && grid){
        const cards=Array.from(grid.querySelectorAll('.loco-listing-card'));
        function update(){
            const q=search.value.trim().toLowerCase(),g=game.value;cards.forEach(c=>{c.hidden=!(c.dataset.title+' '+c.dataset.game+' '+c.dataset.type+' '+c.dataset.seller).includes(q)||(g!=='all'&&c.dataset.game!==g)});
            const shown=cards.filter(c=>!c.hidden);shown.sort((a,b)=>{
                if(sort.value==='price-low')
                    return +a.dataset.price- +b.dataset.price;
                if(sort.value==='price-high')
                    return +b.dataset.price- +a.dataset.price;
                if(sort.value==='title')
                    return a.dataset.title.localeCompare(b.dataset.title);
                return +b.dataset.created- +a.dataset.created});
                shown.forEach(c=>grid.appendChild(c));count.textContent=shown.length+(shown.length===1?' listing found':' listings found');
            }
            search.addEventListener('input',update);game.addEventListener('change',update);
            sort.addEventListener('change',update);update();
        }
    document.querySelectorAll('.loco-workshop-subscribe').forEach(button=>{
        const key='loco-workshop-'+button.dataset.workshopId;
        const paint=()=>{
            const yes=sessionStorage.getItem(key)==='true';button.textContent=yes?'✓ Subscribed':'＋ Subscribe';button.classList.toggle('is-subscribed',yes);
            button.setAttribute('aria-pressed',yes?'true':'false')};paint();
            button.addEventListener('click',()=>{sessionStorage.setItem(key,sessionStorage.getItem(key)==='true'?'false':'true');paint()})});
    })();

    function openTrade(id,title){
        document.getElementById('trade-listing-id').value=id;document.getElementById('trade-listing-name').textContent=title;
        const modal=document.getElementById('trade-modal');
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden','false')
    }
    function closeTrade(){
        const modal=document.getElementById('trade-modal');
        modal.classList.remove('is-open')
        ;modal.setAttribute('aria-hidden','true')
    }
    document.getElementById('trade-modal').addEventListener('click',function(e){if(e.target===this)closeTrade()});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeTrade()});
</script>
</body></html>
