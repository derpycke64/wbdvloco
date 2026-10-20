<?php
session_start();
require_once 'db.php';

// 1. Get the game ID from the URL parameter link
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($game_id <= 0) {
    die("Error: No game specified.");
}

try {
    // 2. Fetch the specific game data row from the database catalog table
    $sql = "SELECT * FROM games WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $game_id]);
    $game = $stmt->fetch();

    // If the ID doesn't exist in phpMyAdmin, display a clean fallback error
    if (!$game) {
        die("Error: Game profile data not found.");
    }
} catch (\PDOException $e) {
    die("Database lookup error: " . $e->getMessage());
}
if(!empty($_SESSION['user_id']) && empty($_SESSION['loco_wishlist_csrf']))$_SESSION['loco_wishlist_csrf']=bin2hex(random_bytes(24));
$gameWishlisted=false;
if(!empty($_SESSION['user_id']) && !empty($game)){
    try{
        $wishlistCheck=$pdo->prepare('SELECT 1 FROM game_wishlists WHERE user_id=:uid AND game_id=:gid LIMIT 1');
        $wishlistCheck->execute([':uid'=>(int)$_SESSION['user_id'],':gid'=>(int)$game['id']]);
        $gameWishlisted=(bool)$wishlistCheck->fetchColumn();
    }catch(PDOException $e){ /* Migration may not be installed yet. */ }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - <?php echo htmlspecialchars($game['title']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">

    <!-- Include your global nav layout header component -->
    <?php include_once 'nav.php'; ?>

    <div class="store-wrapper" style="margin-top: 30px; display: block;">
        <div class="loco-panel" style="max-width: 950px; margin: 0 auto; display: flex; gap: 30px; background: #1e1e1e; border: 1px solid #333;">
            
            <!-- Left Side: Large Game Cover Artwork -->
            <div style="flex: 1.2;">
                <img src="<?php echo htmlspecialchars($game['image_url']); ?>" alt="Game Art" style="width: 100%; border-radius: 4px; border: 1px solid #444; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            </div>
            
            <!-- Right Side: Buying details, info grid modules, descriptions -->
            <div style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <p class="loco-eyebrow" style="margin-bottom: 5px;">PUBLISHER: <?php echo htmlspecialchars(strtoupper($game['publisher'])); ?></p>
                    <h1 class="loco-logo-text" style="font-size: 32px; margin: 0 0 15px 0;"><?php echo htmlspecialchars($game['title']); ?></h1>
                    
                    <h4 class="loco-label-retro" style="font-size: 11px; margin-bottom: 10px;">ABOUT THIS GAME</h4>
                    <p style="color: #ccc; line-height: 1.6; font-size: 14px; margin-bottom: 25px;"><?php echo nl2br(htmlspecialchars($game['description'])); ?></p>
                </div>
                
                <!-- Purchase Module Panel Box -->
                <div style="background: #121212; padding: 20px; border-radius: 4px; border: 1px solid #2a2a2a; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="color: #888; font-size: 11px; display: block;">Buy <?php echo htmlspecialchars($game['title']); ?></span>
                        <strong style="color: #fff; font-size: 22px;">₱<?php echo number_format($game['price'], 2); ?></strong>
                    </div>
                    
                    <?php
                    // Quick separate query block check inside your game_details script
                    $owned_stmt = $pdo->prepare("SELECT id FROM library WHERE user_id = :uid AND game_id = :gid LIMIT 1");
                    $owned_stmt->execute([':uid' => $_SESSION['user_id'] ?? 0, ':gid' => $game['id']]);
                    
                    if ($owned_stmt->fetch()) {
                        echo '<span class="game-buy-btn" style="background: #444; color: #888; cursor: not-allowed; padding: 12px 25px;">OWNED</span>';
                    } else {
                        echo '<a href="buy_game.php?game='.urlencode($game['title']).'" class="game-buy-btn" style="padding: 12px 25px; font-weight: bold; text-decoration: none; text-align: center;">BUY NOW</a>';
                    }
                    ?>
                </div>
                <?php if(!empty($_SESSION['user_id'])): ?>
                <form method="post" action="wishlist.php?tab=games" style="margin-top:12px">
                  <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['loco_wishlist_csrf'],ENT_QUOTES,'UTF-8')?>">
                  <input type="hidden" name="action" value="toggle_game">
                  <input type="hidden" name="id" value="<?=(int)$game['id']?>">
                  <button type="submit" class="loco-wishlist-remove" style="padding:10px 14px"><?= $gameWishlisted?'♥ Remove from Wishlist':'♡ Add to Wishlist' ?></button>
                </form>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>