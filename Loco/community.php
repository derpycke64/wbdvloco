<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

$currentUser = (string)$_SESSION['username'];
if (($_SESSION['loco_catalog_version'] ?? '') !== 'original-games-v2') {
    foreach (['loco_inventory','loco_next_listing_id','loco_listings','loco_wishlist','loco_trades','loco_trade_history','loco_discussions','loco_reports','loco_next_trade_id','loco_next_post_id','loco_next_inventory_id'] as $sessionKey) unset($_SESSION[$sessionKey]);
    $_SESSION['loco_catalog_version'] = 'original-games-v2';
}
if (!isset($_SESSION['loco_profile'])) $_SESSION['loco_profile'] = ['bio' => '', 'favorite_games' => ''];
if (!isset($_SESSION['loco_inventory'])) {
    $_SESSION['loco_inventory'] = [
        ['id' => 1, 'name' => 'Nebula Scout Suit', 'game' => 'Nebula Drift', 'type' => 'Cosmetic', 'quantity' => 1],
        ['id' => 2, 'name' => 'Crimson Rail Token', 'game' => 'Crimson Freight', 'type' => 'Collectible', 'quantity' => 2],
        ['id' => 3, 'name' => 'Luma Lantern Charm', 'game' => 'Lanterns of Luma', 'type' => 'Collectible', 'quantity' => 1],
    ];
}
if (!isset($_SESSION['loco_next_listing_id'])) $_SESSION['loco_next_listing_id'] = 19;
if (!isset($_SESSION['loco_listings'])) {
    $_SESSION['loco_listings'] = [
        1 => ['id'=>1, 'title'=>'Nebula Drift: Starfarer Suit', 'game'=>'Nebula Drift', 'type'=>'Cosmetic', 'condition'=>'Excellent', 'description'=>'An exploration suit with a pearlescent visor finish.', 'price'=>2490.00, 'seller'=>'OrbitMason', 'rating'=>'4.9', 'created'=>time()-3600],
        2 => ['id'=>2, 'title'=>'Crimson Freight: Ember Rail Token', 'game'=>'Crimson Freight', 'type'=>'Collectible', 'condition'=>'Excellent', 'description'=>'A rare conductor token from the Emberline route.', 'price'=>420.00, 'seller'=>'RedSignal', 'rating'=>'4.8', 'created'=>time()-3200],
        3 => ['id'=>3, 'title'=>'Moonroot Hollow: Mooncap Satchel', 'game'=>'Moonroot Hollow', 'type'=>'Accessory', 'condition'=>'Very good', 'description'=>'A stitched satchel patterned with moonlit mushrooms.', 'price'=>315.00, 'seller'=>'MossKeeper', 'rating'=>'4.9', 'created'=>time()-2900],
        4 => ['id'=>4, 'title'=>'Abyss Protocol: Pressure Diver Rig', 'game'=>'Abyss Protocol', 'type'=>'Equipment skin', 'condition'=>'Excellent', 'description'=>'A deep-sea rig cosmetic with luminous gauge details.', 'price'=>1290.00, 'seller'=>'DeepCurrent', 'rating'=>'4.7', 'created'=>time()-2600],
        5 => ['id'=>5, 'title'=>'Stormbanner Tactics: Silver Hawk Standard', 'game'=>'Stormbanner Tactics', 'type'=>'Banner', 'condition'=>'Good', 'description'=>'A commander banner with silver-threaded edges.', 'price'=>575.00, 'seller'=>'BannerSmith', 'rating'=>'4.8', 'created'=>time()-2300],
        6 => ['id'=>6, 'title'=>'Neon Velocity: Pulseframe Bike Skin', 'game'=>'Neon Velocity', 'type'=>'Vehicle skin', 'condition'=>'Excellent', 'description'=>'A magenta-cyan body kit with animated light accents.', 'price'=>1399.00, 'seller'=>'NightApex', 'rating'=>'4.9', 'created'=>time()-2000],
        7 => ['id'=>7, 'title'=>'Clockwork Reverie: Brass Key Set', 'game'=>'Clockwork Reverie', 'type'=>'Puzzle collectible', 'condition'=>'Very good', 'description'=>'A set of ornate keys for collectors of mechanical curiosities.', 'price'=>245.00, 'seller'=>'GearGarden', 'rating'=>'4.6', 'created'=>time()-1800],
        8 => ['id'=>8, 'title'=>'Ashen Crown: Cinderblade Wrap', 'game'=>'Ashen Crown', 'type'=>'Weapon skin', 'condition'=>'Excellent', 'description'=>'A dark steel finish with glowing ember veins.', 'price'=>850.00, 'seller'=>'Crownless', 'rating'=>'4.9', 'created'=>time()-1600],
        9 => ['id'=>9, 'title'=>'Skydrift Homestead: Cloudseed Planter', 'game'=>'Skydrift Homestead', 'type'=>'Decorative item', 'condition'=>'Good', 'description'=>'A floating planter designed for sky-island gardens.', 'price'=>199.00, 'seller'=>'CloudTiller', 'rating'=>'4.7', 'created'=>time()-1400],
        10 => ['id'=>10, 'title'=>'The Last Bell: Seaglass Locket', 'game'=>'The Last Bell', 'type'=>'Collectible', 'condition'=>'Excellent', 'description'=>'A tiny locket holding a fragment of sea-polished glass.', 'price'=>360.00, 'seller'=>'QuietHour', 'rating'=>'4.8', 'created'=>time()-1200],
        11 => ['id'=>11, 'title'=>'Emberline Chronicles: Phoenix Sigil', 'game'=>'Emberline Chronicles', 'type'=>'Artifact', 'condition'=>'Excellent', 'description'=>'An old-world sigil with a warm, ember-like glow.', 'price'=>1490.00, 'seller'=>'AshArchivist', 'rating'=>'4.9', 'created'=>time()-1000],
        12 => ['id'=>12, 'title'=>'Starfall Cartographer: Celestial Compass', 'game'=>'Starfall Cartographer', 'type'=>'Tool skin', 'condition'=>'Very good', 'description'=>'A polished compass casing inspired by mapped star routes.', 'price'=>699.00, 'seller'=>'MapmakerMira', 'rating'=>'4.8', 'created'=>time()-900],
        13 => ['id'=>13, 'title'=>'Riftbound Arena: Rift Core Badge', 'game'=>'Riftbound Arena', 'type'=>'Badge', 'condition'=>'Excellent', 'description'=>'A limited arena badge with a prismatic rift motif.', 'price'=>0.00, 'seller'=>'PhaseRunner', 'rating'=>'4.6', 'created'=>time()-800],
        14 => ['id'=>14, 'title'=>'Lanterns of Luma: Glowpet Lantern', 'game'=>'Lanterns of Luma', 'type'=>'Companion accessory', 'condition'=>'Good', 'description'=>'A soft-glowing lantern charm shaped like a tiny spirit.', 'price'=>325.00, 'seller'=>'LumaLight', 'rating'=>'4.9', 'created'=>time()-700],
        15 => ['id'=>15, 'title'=>'Iron Orchard: Clockwork Apple', 'game'=>'Iron Orchard', 'type'=>'Collectible', 'condition'=>'Very good', 'description'=>'A small brass fruit from the orchard engineering set.', 'price'=>410.00, 'seller'=>'IronBloom', 'rating'=>'4.7', 'created'=>time()-600],
        16 => ['id'=>16, 'title'=>'Echoes Below: Sonar Charm', 'game'=>'Echoes Below', 'type'=>'Accessory', 'condition'=>'Excellent', 'description'=>'A sonar-themed charm for deep exploration loadouts.', 'price'=>590.00, 'seller'=>'BlueTrench', 'rating'=>'4.8', 'created'=>time()-500],
        17 => ['id'=>17, 'title'=>'Glass Harbor: Tideglass Vase', 'game'=>'Glass Harbor', 'type'=>'Decorative item', 'condition'=>'Good', 'description'=>'A translucent vase with a blue-green tide pattern.', 'price'=>275.00, 'seller'=>'HarborGlass', 'rating'=>'4.7', 'created'=>time()-400],
        18 => ['id'=>18, 'title'=>'Wild Circuit: Volt Runner Gloves', 'game'=>'Wild Circuit', 'type'=>'Gear skin', 'condition'=>'Excellent', 'description'=>'Racing gloves with electric linework and reinforced knuckles.', 'price'=>799.00, 'seller'=>'CircuitFox', 'rating'=>'4.9', 'created'=>time()-300]
    ];
}
foreach (['loco_wishlist','loco_trades','loco_trade_history','loco_discussions','loco_reports'] as $key) {
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) $_SESSION[$key] = [];
}
if (!isset($_SESSION['loco_next_trade_id'])) $_SESSION['loco_next_trade_id'] = 1;
if (!isset($_SESSION['loco_next_post_id'])) $_SESSION['loco_next_post_id'] = 1;
if (!isset($_SESSION['loco_next_inventory_id'])) $_SESSION['loco_next_inventory_id'] = 4;
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
                $title = postText('title', 90); $game = postText('game', 60); $type = postText('type', 45);
                $condition = postText('condition', 30); $description = postText('description', 400);
                $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
                if ($title === '' || $game === '' || $type === '' || $description === '' || $price === false || $price <= 0 || $price > 10000000) {
                    $error = 'Enter a title, game, type, description, and a valid price greater than zero.';
                } else {
                    $id = $_SESSION['loco_next_listing_id']++;
                    $_SESSION['loco_listings'][$id] = ['id'=>$id,'title'=>$title,'game'=>$game,'type'=>$type,'condition'=>$condition ?: 'Good','description'=>$description,'price'=>(float)$price,'seller'=>$currentUser,'rating'=>'New seller','created'=>time()];
                    $notice = 'Your listing was created.';
                }
                break;
            case 'edit_listing':
                $id = (int)($_POST['listing_id'] ?? 0); $listing = $_SESSION['loco_listings'][$id] ?? null;
                $title = postText('title', 90); $game = postText('game', 60); $type = postText('type', 45); $condition = postText('condition', 30); $description = postText('description', 400); $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
                if (!$listing || $listing['seller'] !== $currentUser) $error = 'You can only edit your own listings.';
                elseif ($title === '' || $game === '' || $type === '' || $description === '' || $price === false || $price <= 0) $error = 'Complete all listing fields and enter a valid price.';
                else { $_SESSION['loco_listings'][$id] = array_merge($listing, ['title'=>$title,'game'=>$game,'type'=>$type,'condition'=>$condition ?: 'Good','description'=>$description,'price'=>(float)$price]); $notice = 'Listing updated.'; }
                break;
            case 'delete_listing':
                $id = (int)($_POST['listing_id'] ?? 0); $listing = $_SESSION['loco_listings'][$id] ?? null;
                if (!$listing || $listing['seller'] !== $currentUser) $error = 'You can only remove your own listings.';
                else { unset($_SESSION['loco_listings'][$id]); $_SESSION['loco_wishlist'] = array_values(array_diff($_SESSION['loco_wishlist'], [$id])); $notice = 'Listing removed.'; }
                break;
            case 'toggle_wishlist':
                $id = (int)($_POST['listing_id'] ?? 0);
                if (!isset($_SESSION['loco_listings'][$id])) $error = 'That listing is no longer available.';
                elseif (in_array($id, $_SESSION['loco_wishlist'], true)) { $_SESSION['loco_wishlist'] = array_values(array_diff($_SESSION['loco_wishlist'], [$id])); $notice = 'Removed from saved listings.'; }
                else { $_SESSION['loco_wishlist'][] = $id; $notice = 'Listing saved to your wishlist.'; }
                break;
            case 'request_trade':
                $id = (int)($_POST['listing_id'] ?? 0); $listing = $_SESSION['loco_listings'][$id] ?? null; $offerId = (int)($_POST['offer_inventory_id'] ?? 0);
                if (!$listing) $error = 'That listing is no longer available.';
                elseif ($listing['seller'] === $currentUser) $error = 'You cannot send a trade request for your own listing.';
                else {
                    $offer = null;
                    if ($offerId > 0) foreach ($_SESSION['loco_inventory'] as $inv) if ((int)$inv['id'] === $offerId) { $offer = $inv; break; }
                    $tradeId = $_SESSION['loco_next_trade_id']++;
                    $_SESSION['loco_trades'][$tradeId] = ['id'=>$tradeId,'listing_id'=>$id,'listing_title'=>$listing['title'],'seller'=>$listing['seller'],'requester'=>$currentUser,'offer_item'=>$offer ? $offer['name'] : 'No item selected (purchase inquiry)','status'=>'Pending','created'=>time()];
                    $_SESSION['loco_trade_history'][] = ['id'=>$tradeId,'listing_title'=>$listing['title'],'seller'=>$listing['seller'],'requester'=>$currentUser,'offer_item'=>$offer ? $offer['name'] : 'No item selected (purchase inquiry)','status'=>'Pending','updated'=>time()];
                    $notice = 'Trade request created. In this session-based demo, the request is visible only in this browser session.';
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
                $name = postText('item_name', 80); $game = postText('item_game', 60); $type = postText('item_type', 40); $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);
                if ($name === '' || $game === '' || $type === '' || $quantity === false || $quantity < 1 || $quantity > 999) $error = 'Enter valid inventory details.';
                else { $_SESSION['loco_inventory'][] = ['id'=>$_SESSION['loco_next_inventory_id']++,'name'=>$name,'game'=>$game,'type'=>$type,'quantity'=>$quantity]; $notice = 'Demo inventory item added.'; }
                break;
            case 'remove_inventory':
                $id = (int)($_POST['inventory_id'] ?? 0); $found = false;
                foreach ($_SESSION['loco_inventory'] as $index=>$item) if ((int)$item['id'] === $id) { unset($_SESSION['loco_inventory'][$index]); $found = true; }
                $_SESSION['loco_inventory'] = array_values($_SESSION['loco_inventory']); $notice = $found ? 'Inventory item removed.' : ''; if (!$found) $error = 'Inventory item not found.';
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
                $id = (int)($_POST['listing_id'] ?? 0); $reason = postText('reason', 250); $listing = $_SESSION['loco_listings'][$id] ?? null;
                if (!$listing || $reason === '') $error = 'Choose a listing and provide a reason for the report.';
                else { $_SESSION['loco_reports'][] = ['listing_id'=>$id,'title'=>$listing['title'],'reporter'=>$currentUser,'reason'=>$reason,'created'=>time()]; $notice = 'Report recorded for this session. No moderator notification is connected.'; }
                break;
            default: $error = 'Unknown action.';
        }
    }
}

$tab = (string)($_GET['tab'] ?? 'market');
$allowedTabs = ['market','sell','saved','trades','inventory','discussions','profile'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'market';
$editId = (int)($_GET['edit'] ?? 0); $editListing = $_SESSION['loco_listings'][$editId] ?? null;
if ($editListing && $editListing['seller'] !== $currentUser) $editListing = null;
$listings = array_reverse($_SESSION['loco_listings'], true);
$myListings = array_filter($listings, fn($item) => $item['seller'] === $currentUser);
$myTrades = array_filter($_SESSION['loco_trades'], fn($trade) => $trade['requester'] === $currentUser || $trade['seller'] === $currentUser);
$myHistory = array_reverse($_SESSION['loco_trade_history']);
$myPosts = array_reverse($_SESSION['loco_discussions'], true);
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
                <small><?=count($myListings)?> listing(s) · <?=count($_SESSION['loco_inventory'])?> inventory item(s)</small>
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
                <?php foreach (array_unique(array_column($_SESSION['loco_listings'],'game')) as $game): ?>
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
        <?php foreach ($listings as $item): $saved = in_array((int)$item['id'], $_SESSION['loco_wishlist'], true); ?>
            <article class="loco-listing-card" data-title="<?=h(strtolower($item['title']))?>" data-game="<?=h(strtolower($item['game']))?>" data-type="<?=h(strtolower($item['type']))?>" data-seller="<?=h(strtolower($item['seller']))?>" data-price="<?=h($item['price'])?>" data-created="<?=h($item['created'])?>">
                <div class="loco-listing-card-top"><span class="loco-market-tag"><?=h($item['game'])?></span><form method="post" class="loco-inline-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_wishlist"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-icon-button" title="<?=$saved?'Remove from wishlist':'Save listing'?>" aria-label="<?=$saved?'Remove from wishlist':'Save listing'?>"><?=$saved?'♥':'♡'?></button></form></div>
                <?php $marketImageIndex = 0; foreach (['Nebula Drift','Crimson Freight','Moonroot Hollow','Abyss Protocol','Stormbanner Tactics','Neon Velocity','Clockwork Reverie','Ashen Crown','Skydrift Homestead','The Last Bell','Emberline Chronicles','Starfall Cartographer','Riftbound Arena','Lanterns of Luma','Iron Orchard','Echoes Below','Glass Harbor','Wild Circuit'] as $imageIndex => $imageGame) { if ($item['game'] === $imageGame) { $marketImageIndex = $imageIndex + 1; break; } } ?><img class="loco-market-item-image" src="<?= $marketImageIndex ? 'images/market_item_'.str_pad((string)$marketImageIndex, 2, '0', STR_PAD_LEFT).'.svg' : 'images/market-item-default.svg' ?>" alt="<?=h($item['title'])?> item artwork" loading="lazy"><h3><?=h($item['title'])?></h3><p class="loco-listing-description"><?=h($item['description'])?></p>
                <div class="loco-listing-meta">
                    <span><?=h($item['type'])?></span>
                    <span><?=h($item['condition'])?></span>
                </div>
                <div class="loco-listing-seller"><span class="loco-avatar small">👤</span><div><strong><?=h($item['seller'])?></strong><small><?=$item['rating']==='New seller'?'New seller':'★ '.h($item['rating']).' seller rating'?></small></div></div>
                <div class="loco-listing-bottom"><strong class="loco-listing-price">₱<?=number_format((float)$item['price'],2)?></strong><?php if ($item['seller'] === $currentUser): ?><a class="loco-small-link" href="community.php?tab=sell&edit=<?=$item['id']?>">Edit listing</a><?php else: ?><button class="loco-button-small" type="button" onclick="openTrade(<?=$item['id']?>, <?=json_encode($item['title'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)">Request trade</button><?php endif; ?></div>
                <details class="loco-report-details"><summary>Report listing</summary><form method="post" class="loco-report-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="report_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><input name="reason" maxlength="250" placeholder="Why are you reporting this?" required><button class="loco-small-link" type="submit">Submit report</button></form></details>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
    <?php elseif ($tab === 'sell'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2><?=$editListing?'Edit listing':'Create a listing'?></h2><p>Only listings created by your current session username can be edited or removed.</p></div></div>
        <form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="<?=$editListing?'edit_listing':'create_listing'?>"><?php if($editListing): ?><input type="hidden" name="listing_id" value="<?=$editListing['id']?>"><?php endif; ?>
            <label>Item title<input name="title" maxlength="90" required value="<?=h($editListing['title']??'')?>" placeholder="e.g. Crimson Blade Skin"></label>
            <label>Game<input name="game" maxlength="60" required value="<?=h($editListing['game']??'')?>" placeholder="e.g. Nebula Drift"></label>
            <label>Item type<input name="type" maxlength="45" required value="<?=h($editListing['type']??'')?>" placeholder="Skin, collectible, equipment…"></label>
            <label>Condition<select name="condition"><option <?=($editListing['condition']??'')==='New'?'selected':''?>>New</option><option <?=($editListing['condition']??'')==='Excellent'?'selected':''?>>Excellent</option><option <?=($editListing['condition']??'')==='Very good'?'selected':''?>>Very good</option><option <?=($editListing['condition']??'')==='Good'?'selected':''?>>Good</option><option <?=($editListing['condition']??'')==='Used'?'selected':''?>>Used</option></select></label>
            <label>Asking price (PHP)<input name="price" type="number" min="0.01" max="10000000" step="0.01" required value="<?=h($editListing['price']??'')?>" placeholder="500.00"></label>
            <label class="loco-full-width">Description<textarea name="description" maxlength="400" rows="4" required placeholder="Describe the item and what you are offering."><?=h($editListing['description']??'')?></textarea></label>
            <div class="loco-form-actions"><button class="loco-brand-gloss-btn" type="submit"><?=$editListing?'Save changes':'Publish listing'?></button><?php if($editListing): ?><a class="loco-secondary-btn" href="community.php?tab=sell">Cancel edit</a><?php endif; ?></div>
        </form>
        <h3 class="loco-subheading">Your listings</h3><div class="loco-owned-listings"><?php if(!$myListings): ?><p class="loco-muted">You haven't created any listings yet.</p><?php endif; ?><?php foreach($myListings as $item): ?><div class="loco-owned-row"><div><strong><?=h($item['title'])?></strong><small><?=h($item['game'])?> · ₱<?=number_format($item['price'],2)?></small></div><div class="loco-row-actions"><a class="loco-small-link" href="community.php?tab=sell&edit=<?=$item['id']?>">Edit</a><form method="post" onsubmit="return confirm('Remove this listing?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete_listing"><input type="hidden" name="listing_id" value="<?=$item['id']?>"><button class="loco-danger-link">Remove</button></form></div></div><?php endforeach; ?></div>
    </section>
    <?php elseif ($tab === 'saved'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Wishlist & saved listings</h2><p>Items you have saved during this PHP session.</p></div></div><div class="loco-owned-listings"><?php $savedItems = array_filter($_SESSION['loco_wishlist'], fn($id)=>isset($_SESSION['loco_listings'][$id])); if(!$savedItems): ?><p class="loco-empty-state">Your wishlist is empty. Save a listing from the Marketplace using the heart icon.</p><?php endif; ?><?php foreach($savedItems as $id): $item=$_SESSION['loco_listings'][$id]; ?><div class="loco-owned-row"><div><strong><?=h($item['title'])?></strong><small><?=h($item['game'])?> · <?=h($item['seller'])?> · ₱<?=number_format($item['price'],2)?></small></div><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_wishlist"><input type="hidden" name="listing_id" value="<?=$id?>"><button class="loco-danger-link">Remove saved</button></form></div><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'trades'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Trade center & history</h2><p>Review requests sent by you or addressed to your username. Actions are simulated; no items or money move.</p></div></div><div class="loco-trade-list"><?php if(!$myTrades): ?><p class="loco-empty-state">No trade requests yet. Visit the Marketplace to request a trade.</p><?php endif; ?><?php foreach(array_reverse($myTrades, true) as $trade): ?><article class="loco-trade-card"><div><span class="loco-status-pill <?=strtolower($trade['status'])?>"><?=h($trade['status'])?></span><h3><?=h($trade['listing_title'])?></h3><p>From <?=h($trade['requester'])?> to <?=h($trade['seller'])?></p><small>Offer: <?=h($trade['offer_item'])?> · <?=date('M j, Y g:i A',$trade['created'])?></small></div><div class="loco-row-actions"><?php if($trade['status']==='Pending' && $trade['seller']===$currentUser): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Accepted"><button class="loco-button-small">Accept</button></form><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Rejected"><button class="loco-danger-link">Reject</button></form><?php elseif($trade['status']==='Pending' && $trade['requester']===$currentUser): ?><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update_trade"><input type="hidden" name="trade_id" value="<?=$trade['id']?>"><input type="hidden" name="status" value="Cancelled"><button class="loco-danger-link">Cancel request</button></form><?php endif; ?></div></article><?php endforeach; ?></div>
        <h3 class="loco-subheading">Trade history</h3><div class="loco-owned-listings"><?php if(!$myHistory): ?><p class="loco-muted">Resolved and pending trade activity will appear here.</p><?php endif; ?><?php foreach($myHistory as $history): if($history['requester']!==$currentUser && $history['seller']!==$currentUser) continue; ?><div class="loco-owned-row"><div><strong><?=h($history['listing_title'])?></strong><small><?=h($history['requester'])?> → <?=h($history['seller'])?> · <?=h($history['offer_item'])?></small></div><span class="loco-status-pill <?=strtolower($history['status'])?>"><?=h($history['status'])?></span></div><?php endforeach; ?></div>
    </section>
    <?php elseif ($tab === 'inventory'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>My demo inventory</h2><p>Add items to use as example trade offers. This is not connected to any actual game inventory.</p></div></div><form method="post" class="loco-form-grid compact"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="add_inventory"><label>Item name<input name="item_name" maxlength="80" required></label><label>Game<input name="item_game" maxlength="60" required></label><label>Type<input name="item_type" maxlength="40" required placeholder="Skin, collectible…"></label><label>Quantity<input name="quantity" type="number" min="1" max="999" value="1" required></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Add inventory item</button></div></form><div class="loco-inventory-grid"><?php if(!$_SESSION['loco_inventory']): ?><p class="loco-empty-state">Your demo inventory is empty.</p><?php endif; ?><?php foreach($_SESSION['loco_inventory'] as $item): ?><article class="loco-inventory-card"><span class="loco-listing-symbol">✦</span><h3><?=h($item['name'])?></h3><p><?=h($item['game'])?></p><span class="loco-market-tag"><?=h($item['type'])?> · Qty <?=h($item['quantity'])?></span><form method="post" onsubmit="return confirm('Remove this inventory item?')"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="remove_inventory"><input type="hidden" name="inventory_id" value="<?=$item['id']?>"><button class="loco-danger-link">Remove</button></form></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'discussions'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Community discussions</h2><p>Ask questions, share tips, or start a topic with other players using this session.</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create_post"><label>Discussion title<input name="post_title" maxlength="100" required placeholder="What do you want to discuss?"></label><label>Category<select name="post_tag"><option>General</option><option>Trading</option><option>Game help</option><option>Showcase</option><option>Feedback</option></select></label><label class="loco-full-width">Message<textarea name="post_body" maxlength="1200" rows="4" required></textarea></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Post discussion</button></div></form><div class="loco-discussion-list"><?php if(!$myPosts): ?><p class="loco-empty-state">No discussions yet. Start the first conversation above.</p><?php endif; ?><?php foreach($myPosts as $post): ?><article class="loco-discussion-card"><div class="loco-discussion-meta"><span class="loco-market-tag"><?=h($post['tag'])?></span><small>By <?=h($post['author'])?> · <?=date('M j, Y g:i A',$post['created'])?></small></div><h3><?=h($post['title'])?></h3><p><?=nl2br(h($post['body']))?></p><details><summary>Replies (<?=count($post['replies'])?>)</summary><?php foreach($post['replies'] as $reply): ?><div class="loco-reply"><strong><?=h($reply['author'])?></strong><p><?=nl2br(h($reply['body']))?></p></div><?php endforeach; ?><form method="post" class="loco-reply-form"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="reply_post"><input type="hidden" name="post_id" value="<?=$post['id']?>"><input name="reply" maxlength="500" placeholder="Write a reply…" required><button class="loco-button-small">Reply</button></form></details></article><?php endforeach; ?></div></section>
    <?php elseif ($tab === 'profile'): ?>
    <section class="loco-community-panel"><div class="loco-section-heading"><div><h2>Gamer profile</h2><p>Your profile information is saved in the current PHP session.</p></div></div><div class="loco-profile-summary"><span class="loco-avatar large">🎮</span><div><h3><?=h($currentUser)?></h3><p><?=count($myListings)?> listings · <?=count($_SESSION['loco_inventory'])?> inventory items · <?=count($myTrades)?> trade requests · <?=count($_SESSION['loco_discussions'])?> discussion posts in this session</p></div></div><form method="post" class="loco-form-grid"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_profile"><label class="loco-full-width">About me<textarea name="bio" maxlength="300" rows="4" placeholder="Tell the community about yourself…"><?=h($_SESSION['loco_profile']['bio'])?></textarea></label><label class="loco-full-width">Favorite games<input name="favorite_games" maxlength="180" value="<?=h($_SESSION['loco_profile']['favorite_games'])?>" placeholder="e.g. RPGs, strategy, co-op games"></label><div class="loco-form-actions"><button class="loco-brand-gloss-btn">Save profile</button></div></form><div class="loco-session-note">Session-based demo: data is not shared between separate browsers or devices and may disappear when the session expires.</div></section>
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

<div class="loco-modal" id="trade-modal" aria-hidden="true"><div class="loco-modal-card" role="dialog" aria-modal="true" aria-labelledby="trade-modal-title"><button type="button" class="loco-modal-close" onclick="closeTrade()" aria-label="Close">×</button><p class="loco-eyebrow">TRADE REQUEST</p><h2 id="trade-modal-title">Request a trade</h2><p id="trade-listing-name"></p><form method="post"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="request_trade"><input type="hidden" name="listing_id" id="trade-listing-id"><label>Offer an inventory item (optional)<select name="offer_inventory_id"><option value="0">No item — purchase inquiry only</option><?php foreach($_SESSION['loco_inventory'] as $item): ?><option value="<?=$item['id']?>"><?=h($item['name'])?> (<?=h($item['game'])?>)</option><?php endforeach; ?></select></label><p class="loco-session-note">Demo only: this records a request in your current session and does not transfer items or money.</p><button class="loco-brand-gloss-btn" type="submit">Send request</button></form></div></div>
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
