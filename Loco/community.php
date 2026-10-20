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
                    if(!$listing || !$listing['inventory_id'] || (float)$listing['price']<0) throw new RuntimeException('This item is not available for purchase.');
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
                    if($price>0){
                        $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance-:price WHERE id=:id AND wallet_balance>=:minimum');
                        $q->execute([':price'=>$price,':id'=>$buyerId,':minimum'=>$price]);
                        if($q->rowCount()!==1) throw new RuntimeException('Insufficient LOCO wallet balance.');
                        $q=$pdo->prepare('UPDATE users SET wallet_balance=wallet_balance+:price WHERE id=:id');
                        $q->execute([':price'=>$price,':id'=>(int)$listing['seller_id']]);
                    }
                    $q=$pdo->prepare('UPDATE inventories SET user_id=:buyer WHERE id=:id AND user_id=:seller');
                    $q->execute([':buyer'=>$buyerId,':id'=>(int)$listing['inventory_id'],':seller'=>(int)$listing['seller_id']]);
                    if($q->rowCount()!==1) throw new RuntimeException('Item ownership changed. Please try again.');
                    $q=$pdo->prepare("UPDATE marketplace_listings SET status='sold' WHERE id=:id AND status='active'");
                    $q->execute([':id'=>$listingId]);
                    if($q->rowCount()!==1) throw new RuntimeException('Listing is no longer available.');
                    $pdo->commit();
                    $notice=$price>0 ? 'Purchase complete. Item added to your inventory and payment sent to the seller.' : 'Free item claimed and added to your inventory.';
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
                $listingId=(int)($_POST['listing_id']??0);
                $offerId=(int)($_POST['offer_inventory_id']??0);
                if($offerId<=0){$error='Select an inventory item to offer for this trade.';break;}
                try {
                    $pdo->beginTransaction();
                    $q=$pdo->prepare("SELECT id,seller_id,inventory_id,item_id FROM marketplace_listings WHERE id=:id AND status='active' FOR UPDATE");
                    $q->execute([':id'=>$listingId]);$listing=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$listing || !$listing['inventory_id'])throw new RuntimeException('This listing is no longer available for trading.');
                    $uid=(int)$_SESSION['user_id'];
                    if((int)$listing['seller_id']===$uid)throw new RuntimeException('You cannot trade with yourself.');
                    $q=$pdo->prepare('SELECT id,user_id,item_id FROM inventories WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>(int)$listing['inventory_id']]);$sellerItem=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$sellerItem || (int)$sellerItem['user_id']!==(int)$listing['seller_id'] || (int)$sellerItem['item_id']!==(int)$listing['item_id'])throw new RuntimeException('Seller no longer owns the listed item.');
                    $q=$pdo->prepare('SELECT id,user_id FROM inventories WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>$offerId]);$offer=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$offer || (int)$offer['user_id']!==$uid)throw new RuntimeException('That item is no longer in your inventory.');
                    $q=$pdo->prepare("SELECT id FROM marketplace_listings WHERE inventory_id=:iid AND status='active' LIMIT 1");
                    $q->execute([':iid'=>$offerId]);
                    if($q->fetch())throw new RuntimeException('Remove your item from the marketplace before offering it.');
                    $q=$pdo->prepare("SELECT id FROM trade_requests WHERE offered_inventory_id=:iid AND status='pending' LIMIT 1");
                    $q->execute([':iid'=>$offerId]);
                    if($q->fetch())throw new RuntimeException('That item is already offered in a pending trade.');
                    $q=$pdo->prepare("INSERT INTO trade_requests (listing_id,requester_id,seller_id,offered_inventory_id,message,status) VALUES (:lid,:requester,:seller,:offer,NULL,'pending')");
                    $q->execute([':lid'=>$listingId,':requester'=>$uid,':seller'=>(int)$listing['seller_id'],':offer'=>$offerId]);
                    $tradeId=(int)$pdo->lastInsertId();
                    $q=$pdo->prepare("INSERT INTO trade_request_history (trade_request_id,changed_by,old_status,new_status,note) VALUES (:trade,:actor,NULL,'pending','Trade offer submitted')");
                    $q->execute([':trade'=>$tradeId,':actor'=>$uid]);
                    $pdo->commit();$notice='Trade request sent. The seller can accept or reject it in Trade Center.';
                }catch(Throwable $e){
                    if($pdo->inTransaction())$pdo->rollBack();
                    if($e instanceof PDOException){error_log('LOCO trade request failed: '.$e->getMessage());$error='Unable to save trade request. Import database/loco_db_upgrade.sql if needed.';}
                    else $error=$e->getMessage();
                }
                break;
            case 'update_trade':
                $tradeId=(int)($_POST['trade_id']??0);
                $decision=(string)($_POST['status']??'');
                $uid=(int)$_SESSION['user_id'];
                if(!in_array($decision,['accepted','rejected','cancelled'],true)){$error='Invalid trade action.';break;}
                try{
                    $pdo->beginTransaction();
                    $q=$pdo->prepare("SELECT * FROM trade_requests WHERE id=:id FOR UPDATE");
                    $q->execute([':id'=>$tradeId]);$trade=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$trade || $trade['status']!=='pending')throw new RuntimeException('This trade request is no longer pending.');
                    if($decision==='cancelled' ? (int)$trade['requester_id']!==$uid : (int)$trade['seller_id']!==$uid)throw new RuntimeException('You cannot update this request.');
                    $newStatus=$decision;
                    if($decision==='accepted'){
                        $q=$pdo->prepare("SELECT id,seller_id,item_id,inventory_id FROM marketplace_listings WHERE id=:id AND status='active' FOR UPDATE");
                        $q->execute([':id'=>(int)$trade['listing_id']]);$listing=$q->fetch(PDO::FETCH_ASSOC);
                        if(!$listing || !$listing['inventory_id'] || (int)$listing['seller_id']!==(int)$trade['seller_id'])throw new RuntimeException('Listed item is no longer available.');
                        $sellerInventory=(int)$listing['inventory_id'];
                        $offeredInventory=(int)$trade['offered_inventory_id'];
                        if($sellerInventory<1 || $offeredInventory<1 || $sellerInventory===$offeredInventory)throw new RuntimeException('The trade items are invalid.');
                        $ids=[$sellerInventory,$offeredInventory];sort($ids,SORT_NUMERIC);
                        $q=$pdo->prepare('SELECT id,user_id,item_id FROM inventories WHERE id IN (:first,:second) ORDER BY id FOR UPDATE');
                        $q->execute([':first'=>$ids[0],':second'=>$ids[1]]);
                        $owned=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row)$owned[(int)$row['id']]=$row;
                        if(!isset($owned[$sellerInventory],$owned[$offeredInventory])
                            || (int)$owned[$sellerInventory]['user_id']!==(int)$trade['seller_id']
                            || (int)$owned[$sellerInventory]['item_id']!==(int)$listing['item_id']
                            || (int)$owned[$offeredInventory]['user_id']!==(int)$trade['requester_id'])
                            throw new RuntimeException('An offered item changed owners. The trade cannot proceed.');
                        $q=$pdo->prepare("SELECT id FROM marketplace_listings WHERE inventory_id=:id AND status='active' LIMIT 1");
                        $q->execute([':id'=>$offeredInventory]);
                        if($q->fetch())throw new RuntimeException('The offered item is now listed for sale.');
                        $q=$pdo->prepare("UPDATE inventories SET user_id=:uid WHERE id=:id AND user_id=:previous");
                        $q->execute([':uid'=>(int)$trade['requester_id'],':id'=>$sellerInventory,':previous'=>(int)$trade['seller_id']]);
                        if($q->rowCount()!==1)throw new RuntimeException('Listed item ownership could not be transferred.');
                        $q->execute([':uid'=>(int)$trade['seller_id'],':id'=>$offeredInventory,':previous'=>(int)$trade['requester_id']]);
                        if($q->rowCount()!==1)throw new RuntimeException('Offered item ownership could not be transferred.');
                        $q=$pdo->prepare("UPDATE marketplace_listings SET status='traded' WHERE id=:id AND status='active'");
                        $q->execute([':id'=>(int)$trade['listing_id']]);
                        if($q->rowCount()!==1)throw new RuntimeException('Listing is no longer active.');
                        $newStatus='completed';
                    }
                    $q=$pdo->prepare('UPDATE trade_requests SET status=:status WHERE id=:id AND status=\'pending\'');
                    $q->execute([':status'=>$newStatus,':id'=>$tradeId]);
                    if($q->rowCount()!==1)throw new RuntimeException('Trade status was already updated.');
                    $q=$pdo->prepare("INSERT INTO trade_request_history (trade_request_id,changed_by,old_status,new_status,note) VALUES (:id,:actor,'pending',:status,:note)");
                    $q->execute([':id'=>$tradeId,':actor'=>$uid,':status'=>$newStatus,':note'=>$newStatus==='completed'?'Both inventory items exchanged':'Trade request '.$newStatus]);
                    $pdo->commit();
                    $notice=$newStatus==='completed'?'Trade completed! Both items have been exchanged.':'Trade request '.$newStatus.'.';
                }catch(Throwable $e){
                    if($pdo->inTransaction())$pdo->rollBack();
                    if($e instanceof PDOException){error_log('LOCO trade update failed: '.$e->getMessage());$error='Unable to update the trade. Check the trade database tables.';}
                    else $error=$e->getMessage();
                }
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
            case 'publish_workshop':
                $title=postText('workshop_title',150);
                $description=postText('workshop_description',2000);
                $gameId=(int)($_POST['workshop_game_id']??0);
                $archive=$_FILES['workshop_archive']??null;
                $cover=$_FILES['workshop_cover']??null;
                if($title===''||$description===''||$gameId<1){$error='Enter a title, game, and description.';break;}
                if(!$archive || (int)$archive['error']!==UPLOAD_ERR_OK){$error='Upload a ZIP archive (maximum 10 MB).';break;}
                if((int)$archive['size']<1 || (int)$archive['size']>10*1024*1024 || strtolower(pathinfo((string)$archive['name'],PATHINFO_EXTENSION))!=='zip'){$error='Workshop files must be ZIP archives of 10 MB or less.';break;}
                $uploadDir=__DIR__.'/uploads/workshop';
                $createdFiles=[];
                try {
                    $q=$pdo->prepare('SELECT id FROM games WHERE id=:id LIMIT 1');
                    $q->execute([':id'=>$gameId]);
                    if(!$q->fetch())throw new RuntimeException('Select a valid LOCO game.');
                    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($archive['tmp_name']);
                    if(!in_array($mime,['application/zip','application/x-zip-compressed','application/octet-stream'],true))throw new RuntimeException('The uploaded file is not a valid ZIP archive.');
                    $header=file_get_contents($archive['tmp_name'],false,null,0,4);
                    if(!in_array($header,["PK\\x03\\x04","PK\\x05\\x06","PK\\x07\\x08"],true))throw new RuntimeException('The uploaded file is not a ZIP archive.');
                    if(!is_dir($uploadDir) && !mkdir($uploadDir,0750,true) && !is_dir($uploadDir))throw new RuntimeException('Unable to create the Workshop upload folder.');
                    $archiveName=bin2hex(random_bytes(16)).'.zip';
                    if(!move_uploaded_file($archive['tmp_name'],$uploadDir.'/'.$archiveName))throw new RuntimeException('Unable to save the ZIP archive.');
                    $createdFiles[]=$archiveName;
                    $coverName=null;
                    if($cover && (int)$cover['error']!==UPLOAD_ERR_NO_FILE){
                        if((int)$cover['error']!==UPLOAD_ERR_OK || (int)$cover['size']<1 || (int)$cover['size']>2*1024*1024)throw new RuntimeException('Cover image must be 2 MB or less.');
                        $info=@getimagesize($cover['tmp_name']);
                        $allowed=[IMAGETYPE_JPEG=>'jpg',IMAGETYPE_PNG=>'png',IMAGETYPE_WEBP=>'webp'];
                        if(!$info || !isset($allowed[$info[2]]) || $info[0]>4000 || $info[1]>4000)throw new RuntimeException('Use a JPG, PNG, or WebP cover image up to 4000 pixels.');
                        $coverName=bin2hex(random_bytes(16)).'.'.$allowed[$info[2]];
                        if(!move_uploaded_file($cover['tmp_name'],$uploadDir.'/'.$coverName))throw new RuntimeException('Unable to save the cover image.');
                        $createdFiles[]=$coverName;
                    }
                    $original=basename((string)$archive['name']);
                    $original=function_exists('mb_substr')?mb_substr($original,0,180,'UTF-8'):substr($original,0,180);
                    $q=$pdo->prepare('INSERT INTO workshop_creations (user_id,game_id,title,description,archive_filename,original_filename,cover_filename) VALUES (:uid,:gid,:title,:description,:archive,:original,:cover)');
                    $q->execute([':uid'=>(int)$_SESSION['user_id'],':gid'=>$gameId,':title'=>$title,':description'=>$description,':archive'=>$archiveName,':original'=>$original,':cover'=>$coverName]);
                    $notice='Your Workshop creation has been published.';
                } catch(Throwable $e){
                    foreach($createdFiles as $file)@unlink($uploadDir.'/'.$file);
                    if($e instanceof PDOException){error_log('Workshop publish failed: '.$e->getMessage());$error='Workshop database is unavailable. Import database/community_workshop.sql.';}
                    else $error=$e->getMessage();
                }
                break;
            case 'toggle_workshop_subscription':
                $id=(int)($_POST['creation_id']??0);
                try{
                    $q=$pdo->prepare('SELECT user_id FROM workshop_creations WHERE id=:id LIMIT 1');
                    $q->execute([':id'=>$id]);$owner=$q->fetchColumn();
                    if($owner===false){$error='Creation not found.';break;}
                    if((int)$owner===(int)$_SESSION['user_id']){$error='You already own this creation.';break;}
                    $q=$pdo->prepare('SELECT 1 FROM workshop_subscriptions WHERE user_id=:uid AND creation_id=:id LIMIT 1');
                    $q->execute([':uid'=>(int)$_SESSION['user_id'],':id'=>$id]);
                    if($q->fetchColumn()){
                        $q=$pdo->prepare('DELETE FROM workshop_subscriptions WHERE user_id=:uid AND creation_id=:id');
                        $q->execute([':uid'=>(int)$_SESSION['user_id'],':id'=>$id]);
                        $notice='Unsubscribed from the creation.';
                    }else{
                        $q=$pdo->prepare('INSERT INTO workshop_subscriptions (user_id,creation_id) VALUES (:uid,:id)');
                        $q->execute([':uid'=>(int)$_SESSION['user_id'],':id'=>$id]);
                        $notice='Subscribed! You can now download the creation.';
                    }
                }catch(PDOException $e){error_log('Workshop subscription failed: '.$e->getMessage());$error='Unable to update subscription. Check the Workshop database tables.';}
                break;
            case 'delete_workshop':
                $id=(int)($_POST['creation_id']??0);
                try{
                    $q=$pdo->prepare('SELECT archive_filename,cover_filename FROM workshop_creations WHERE id=:id AND user_id=:uid LIMIT 1');
                    $q->execute([':id'=>$id,':uid'=>(int)$_SESSION['user_id']]);
                    $own=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$own){$error='You can only delete your own creations.';break;}
                    $q=$pdo->prepare('DELETE FROM workshop_creations WHERE id=:id AND user_id=:uid');
                    $q->execute([':id'=>$id,':uid'=>(int)$_SESSION['user_id']]);
                    if($q->rowCount()===1){
                        foreach(['archive_filename','cover_filename'] as $field){
                            $name=$own[$field]??'';
                            if(preg_match('/^[a-f0-9]{32}\\.(?:zip|jpg|png|webp)$/D',$name))@unlink(__DIR__.'/uploads/workshop/'.$name);
                        }
                        $notice='Workshop creation deleted.';
                    }
                }catch(PDOException $e){error_log('Workshop delete failed: '.$e->getMessage());$error='Unable to delete creation.';}
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
$allowedTabs=['market','sell','saved','trades','inventory','discussions','profile','workshop'];
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
$myTrades=[];$myHistory=[];
try{
    $q=$pdo->prepare("SELECT tr.id,tr.listing_id,tr.requester_id,tr.seller_id,tr.offered_inventory_id,tr.status,tr.created_at,gi.item_name AS listing_title,offer_item.item_name AS offer_item,requester.username AS requester,seller.username AS seller FROM trade_requests tr JOIN marketplace_listings ml ON ml.id=tr.listing_id JOIN game_items gi ON gi.id=ml.item_id LEFT JOIN inventories offer_inv ON offer_inv.id=tr.offered_inventory_id LEFT JOIN game_items offer_item ON offer_item.id=offer_inv.item_id JOIN users requester ON requester.id=tr.requester_id JOIN users seller ON seller.id=tr.seller_id WHERE tr.requester_id=:requester OR tr.seller_id=:seller ORDER BY tr.created_at DESC,tr.id DESC");
    $q->execute([':requester'=>(int)$_SESSION['user_id'],':seller'=>(int)$_SESSION['user_id']]);
    $myTrades=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare("SELECT th.id,th.trade_request_id,th.old_status,th.new_status,th.note,th.changed_at,gi.item_name AS listing_title FROM trade_request_history th JOIN trade_requests tr ON tr.id=th.trade_request_id JOIN marketplace_listings ml ON ml.id=tr.listing_id JOIN game_items gi ON gi.id=ml.item_id WHERE tr.requester_id=:requester OR tr.seller_id=:seller ORDER BY th.changed_at DESC,th.id DESC LIMIT 100");
    $q->execute([':requester'=>(int)$_SESSION['user_id'],':seller'=>(int)$_SESSION['user_id']]);$myHistory=$q->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){error_log('LOCO trade data unavailable: '.$e->getMessage());if(($_GET['tab']??'')==='trades')$error=$error?:'Trade database is not ready. Import database/loco_db_upgrade.sql.';}
$workshopCreations=[];$workshopGames=[];$myWorkshopSubscriptions=[];
try{
    $workshopGames=$pdo->query('SELECT id,title FROM games ORDER BY title')->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare("SELECT wc.id,wc.user_id,wc.game_id,wc.title,wc.description,wc.cover_filename,wc.created_at,g.title AS game,u.username AS creator,
        (SELECT COUNT(*) FROM workshop_subscriptions ws WHERE ws.creation_id=wc.id) AS subscribers,
        EXISTS(SELECT 1 FROM workshop_subscriptions ws WHERE ws.creation_id=wc.id AND ws.user_id=:uid) AS subscribed
        FROM workshop_creations wc JOIN users u ON u.id=wc.user_id JOIN games g ON g.id=wc.game_id
        ORDER BY wc.created_at DESC,wc.id DESC");
    $q->execute([':uid'=>(int)$_SESSION['user_id']]);$workshopCreations=$q->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){error_log('Workshop load failed: '.$e->getMessage());if(($_GET['tab']??'')==='workshop')$error=$error?:'Workshop database is not ready. Import database/community_workshop.sql.';}
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
            <p>Trade inventory items, manage listings, save finds, and talk with other players.</p>
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
        <?php foreach (['market'=>'Marketplace','sell'=>'My Listings','saved'=>'Wishlist','trades'=>'Trade Center','inventory'=>'Inventory','workshop'=>'Workshop','discussions'=>'Discussions','profile'=>'My Profile'] as $key=>$label): ?><a class="<?= $tab===$key?'active':'' ?>" href="community.php?tab=<?=h($key)?>"><?=h($label)?></a><?php endforeach; ?>
    </nav>

    <?php if ($tab === 'market'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Marketplace</h2><p>Browse items, buy with your LOCO wallet, or offer an item in trade.</p></div><a class="loco-brand-gloss-btn" href="community.php?tab=sell">＋ Create listing</a></div>
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
                <div class="loco-listing-bottom"><strong class="loco-listing-price">₱<?=number_format((float)$item['price'],2)?></strong><?php if ((int)$item['seller_id'] === (int)$_SESSION['user_id']): ?><a class="loco-small-link" href="community.php?tab=sell&edit=<?=$item['id']?>">Edit listing</a><?php else: ?><div><form method="post" class="loco-inline-form" onsubmit="return confirm('<?=((float)$item['price']===0.0)?'Claim this free item?':'Buy this item for ₱'.number_format((float)$item['price'],2).' using your LOCO wallet?'?>')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="buy_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-button-small" type="submit"><?=((float)$item['price']===0.0)?'Claim Free':'Buy Now'?></button></form> <details class="loco-trade-inline"><summary class="loco-button-small">Request Trade</summary><form method="post" class="loco-trade-inline-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="request_trade"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><label>Offer your item<select name="offer_inventory_id" required><option value="">Select an item</option><?php foreach($tradeInventoryItems as $offerItem): ?><option value="<?=$offerItem['inventory_id']?>"><?=h($offerItem['title'])?> (<?=h($offerItem['game'])?>)</option><?php endforeach; ?></select></label><button class="loco-button-small" type="submit">Send Trade Request</button></form></details></div><?php endif; ?></div>
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
    <section class="loco-community-panel">
        <div class="loco-section-heading"><div><h2>Trade center & history</h2><p>Send real inventory-for-inventory offers. Accepted trades exchange items permanently in the database; no wallet funds move.</p></div></div>
        <div class="loco-trade-list">
        <?php if(!$myTrades): ?><p class="loco-empty-state">No trade requests yet. Visit the Marketplace and offer an inventory item.</p><?php endif; ?>
        <?php foreach($myTrades as $trade): ?>
        <article class="loco-trade-card"><div><span class="loco-status-pill <?=h($trade['status'])?>"><?=h(ucfirst($trade['status']))?></span>
        <h3><?=h($trade['listing_title'])?></h3><p>From <?=h($trade['requester'])?> to <?=h($trade['seller'])?></p>
        <small>Offered: <?=h($trade['offer_item']??'Item removed')?> · <?=h($trade['created_at'])?></small></div>
        <div class="loco-row-actions">
        <?php if($trade['status']==='pending' && (int)$trade['seller_id']===(int)$_SESSION['user_id']): ?>
          <form method="post" onsubmit="return confirm('Accept and permanently swap both inventory items?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="accepted"><button class="loco-button-small">Accept & swap</button></form>
          <form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="rejected"><button class="loco-danger-link">Reject</button></form>
        <?php elseif($trade['status']==='pending' && (int)$trade['requester_id']===(int)$_SESSION['user_id']): ?>
          <form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="cancelled"><button class="loco-danger-link">Cancel request</button></form>
        <?php endif; ?></div></article>
        <?php endforeach; ?></div>
        <h3 class="loco-subheading">Trade history</h3>
        <div class="loco-owned-listings"><?php if(!$myHistory): ?><p class="loco-muted">Trade activity will appear here.</p><?php endif; ?>
        <?php foreach($myHistory as $history): ?><div class="loco-owned-row"><div><strong><?=h($history['listing_title'])?></strong><small><?=h($history['note']??'')?> · <?=h($history['changed_at'])?></small></div><span class="loco-status-pill <?=h($history['new_status'])?>"><?=h(ucfirst($history['new_status']))?></span></div><?php endforeach; ?></div>
    </section>
    <?php elseif ($tab === 'inventory'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>My inventory</h2><p>Items here are saved to your account in the database. Items with an active listing stay in your inventory but cannot be removed until the listing is removed.</p></div></div><form method="post" class="loco-form-grid compact"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="add_inventory"><label>Item name<input name="item_name" maxlength="150" required></label><label>Game<select name="item_game" required><option value="">Choose a game</option><?php foreach($gameOptions as $gameOption): ?><option value="<?=h($gameOption)?>"><?=h($gameOption)?></option><?php endforeach; ?></select></label><label>Type<input name="item_type" maxlength="50" required placeholder="Skin, collectible…"></label><label>Quantity<input name="quantity" type="number" min="1" max="999" value="1" required></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Add to inventory</button></div></form><div class="loco-inventory-grid"><?php if(!$inventoryItems): ?><p class="loco-empty-state">Your inventory is empty. Add an item above or publish a listing to create an owned item.</p><?php endif; ?><?php foreach($inventoryItems as $item): ?><article class="loco-inventory-card"><span class="loco-listing-symbol">✦</span><h3><?=h($item['title'])?></h3><p><?=h($item['game'])?></p><span class="loco-market-tag"><?=h($item['rarity']??'Item')?></span><?php if(!empty($item['active_listing_id'])): ?><p class="loco-session-note">Listed on marketplace</p><a class="loco-small-link" href="community.php?tab=sell">Manage listing</a><?php else: ?><form method="post" onsubmit="return confirm('Remove this inventory item?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="remove_inventory"><input type="hidden" name="inventory_id" value="<?=$item['inventory_id']?>"><button class="loco-danger-link">Remove item</button></form><?php endif; ?></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'discussions'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Community discussions</h2><p>Ask questions, share tips, or start a topic with other players using this session.</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create_post"><label>Discussion title<input name="post_title" maxlength="100" required placeholder="What do you want to discuss?"></label><label>Category<select name="post_tag"><option>General</option><option>Trading</option><option>Game help</option><option>Showcase</option><option>Feedback</option></select></label><label class="loco-full-width">Message<textarea name="post_body" maxlength="1200" rows="4" required></textarea></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Post discussion</button></div></form><div class="loco-discussion-list"><?php if(!$myPosts): ?><p class="loco-empty-state">No discussions yet. Start the first conversation above.</p><?php endif; ?><?php foreach($myPosts as $post): ?><article class="loco-discussion-card"><div class="loco-discussion-meta"><span class="loco-market-tag"><?=h($post['tag'])?></span><small>By <?=h($post['author'])?> · <?=date('M j, Y g:i A',$post['created'])?></small></div><h3><?=h($post['title'])?></h3><p><?=nl2br(h($post['body']))?></p><details><summary>Replies (<?=count($post['replies'])?>)</summary><?php foreach($post['replies'] as $reply): ?><div class="loco-reply"><strong><?=h($reply['author'])?></strong><p><?=nl2br(h($reply['body']))?></p></div><?php endforeach; ?><form method="post" class="loco-reply-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="reply_post"><input type="hidden" name="post_id" value="<?=$post['id']?>"><input name="reply" maxlength="500" placeholder="Write a reply…" required><button class="loco-button-small">Reply</button></form></details></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'profile'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Gamer profile</h2><p>Your profile information is saved in the current PHP session.</p></div></div><div class="loco-profile-summary"><span class="loco-avatar large">🎮</span><div><h3><?=h($currentUser)?></h3><p><?=count($myListings)?> listings · <?=count($inventoryItems)?> inventory item instances · <?=count($myTrades)?> trade requests · <?=count($_SESSION['loco_discussions'])?> discussion posts in this session</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_profile"><label class="loco-full-width">About me<textarea name="bio" maxlength="300" rows="4" placeholder="Tell the community about yourself…"><?=h($_SESSION['loco_profile']['bio'])?></textarea></label><label class="loco-full-width">Favorite games<input name="favorite_games" maxlength="180" value="<?=h($_SESSION['loco_profile']['favorite_games'])?>" placeholder="e.g. RPGs, strategy, co-op games"></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Save profile</button></div></form><div class="loco-session-note">Session-based demo: data is not shared between separate browsers or devices and may disappear when the session expires.</div></section>
    <?php endif; ?>

    <?php if($tab==='workshop'): ?>
    <section class="loco-community-panel" id="workshop"><div class="loco-section-heading"><div><h2>LOCO Workshop</h2><p>Publish game creations, subscribe to other creators, and download ZIP archives. Files are community-uploaded; only open files you trust.</p></div></div>
    <details class="loco-workshop-publish"><summary class="loco-brand-gloss-btn">＋ Publish a creation</summary>
        <form method="post" enctype="multipart/form-data" class="loco-form-grid" style="margin-top:18px">
          <input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="publish_workshop">
          <label>Creation title<input name="workshop_title" required maxlength="150" placeholder="Name your creation"></label>
          <label>Game<select name="workshop_game_id" required><option value="">Choose game</option><?php foreach($workshopGames as $game): ?><option value="<?=$game['id']?>"><?=h($game['title'])?></option><?php endforeach; ?></select></label>
          <label class="loco-full-width">Description<textarea name="workshop_description" required maxlength="2000" rows="3" placeholder="Explain what your creation adds and how to use it"></textarea></label>
          <label>Creation ZIP (up to 10 MB)<input type="file" name="workshop_archive" accept=".zip,application/zip" required></label>
          <label>Cover image (optional, JPG/PNG/WebP, up to 2 MB)<input type="file" name="workshop_cover" accept="image/jpeg,image/png,image/webp"></label>
          <div class="loco-form-actions"><button class="loco-brand-gloss-btn" type="submit">Publish to Workshop</button></div>
        </form>
    </details>
    <h3 class="loco-subheading">Browse creations</h3>
    <div class="loco-workshop-grid">
    <?php if(!$workshopCreations): ?><p class="loco-empty-state">No creations published yet. Be the first to share one.</p><?php endif; ?>
    <?php foreach($workshopCreations as $creation): $isOwner=(int)$creation['user_id']===(int)$_SESSION['user_id']; ?>
      <article class="loco-workshop-card">
        <?php if($creation['cover_filename']): ?><img class="loco-workshop-thumb" src="workshop_cover.php?id=<?=$creation['id']?>" alt="<?=h($creation['title'])?> cover" loading="lazy"><?php else: ?><div class="loco-workshop-placeholder">✦ LOCO WORKSHOP</div><?php endif; ?>
        <h3><?=h($creation['title'])?></h3><p><?=h($creation['description'])?></p>
        <p class="loco-muted"><?=h($creation['game'])?> · By <?=h($creation['creator'])?> · <?=h($creation['created_at'])?></p>
        <p class="loco-muted"><?=number_format((int)$creation['subscribers'])?> subscriber(s)</p>
        <div class="loco-workshop-actions">
        <?php if($isOwner): ?>
          <a class="loco-button-small" href="workshop_download.php?id=<?=$creation['id']?>">Download your ZIP</a>
          <form method="post" onsubmit="return confirm('Delete this creation and its subscriptions?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete_workshop"><input type="hidden" name="creation_id" value="<?=$creation['id']?>"><button type="submit" class="loco-danger-link">Delete</button></form>
        <?php else: ?>
          <form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_workshop_subscription"><input type="hidden" name="creation_id" value="<?=$creation['id']?>"><button type="submit" class="loco-button-small"><?=$creation['subscribed']?'✓ Unsubscribe':'＋ Subscribe'?></button></form>
          <?php if($creation['subscribed']): ?><a class="loco-button-small" href="workshop_download.php?id=<?=$creation['id']?>">Download ZIP</a><?php endif; ?>
        <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
    </section>
    <?php endif; ?>

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

<div class="loco-modal" id="trade-modal" aria-hidden="true"><div class="loco-modal-card" role="dialog" aria-modal="true" aria-labelledby="trade-modal-title"><button type="button" class="loco-modal-close" onclick="closeTrade()" aria-label="Close">×</button><p class="loco-eyebrow">TRADE REQUEST</p><h2 id="trade-modal-title">Request a trade</h2><p id="trade-listing-name"></p><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="request_trade"><input type="hidden" name="listing_id" id="trade-listing-id"><label>Offer an inventory item<select name="offer_inventory_id" required><option value="">Choose an item to exchange</option><?php foreach($tradeInventoryItems as $item): ?><option value="<?=$item['inventory_id']?>"><?=h($item['title'])?> (<?=h($item['game'])?>)</option><?php endforeach; ?></select></label><p class="loco-session-note">The seller can accept your offer to exchange both inventory items. No wallet funds are involved.</p><button class="loco-brand-gloss-btn" type="submit">Send request</button></form></div></div>
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
