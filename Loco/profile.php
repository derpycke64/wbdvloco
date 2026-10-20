<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

$currentUser = (string)$_SESSION['username'];
$current_user_id = $_SESSION['user_id'];

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

try {
    // 1. Fetch user data and personal profile information (including avatar PFP)
    $profile_sql = "SELECT u.username, u.email, u.wallet_balance, p.display_name, p.avatar_url, p.bio, p.favorite_games 
                    FROM users u 
                    LEFT JOIN user_profiles p ON u.id = p.user_id 
                    WHERE u.id = :user_id LIMIT 1";
    $profile_stmt = $pdo->prepare($profile_sql);
    $profile_stmt->execute([':user_id' => $current_user_id]);
    $user_profile = $profile_stmt->fetch();

    if (!$user_profile) {
        die("Profile configuration error.");
    }

    // 2. Fetch all games this user has purchased from the database library
    $library_sql = "SELECT g.title, g.image_url, g.price, l.purchase_date 
                    FROM library l
                    INNER JOIN games g ON l.game_id = g.id 
                    WHERE l.user_id = :user_id 
                    ORDER BY l.purchase_date DESC";
    $library_stmt = $pdo->prepare($library_sql);
    $library_stmt->execute([':user_id' => $current_user_id]);
    $purchased_games = $library_stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database error loading profile: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Gamer Profile</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">


    <?php include_once 'nav.php'; ?>

    
    <div class="loco-community-wrap">
        
        <!-- Header Panel with Avatar and Stats -->
        <div class="loco-community-heading">
            <div>
                <p class="loco-eyebrow">LOCO GAMER NETWORK</p>
                <h1 class="loco-logo-text"><?=h($user_profile['display_name'] ?: $currentUser)?></h1>
                <p>Your LOCO player profile, activity, and owned games.</p>
            </div>
            <div class="loco-community-user">
                <!-- Displays the user profile's database PFP string directly inside your visual element -->
                <img src="profile_avatar.php?id=<?=(int)$current_user_id?>" class="loco-avatar" alt="Avatar" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                <div>
                    <strong><?=h($currentUser)?></strong>
                    <?php if((float)$user_profile['wallet_balance']>0): ?><small>Wallet Balance: <span style="color:#72d57a;font-weight:bold">₱<?=number_format((float)$user_profile['wallet_balance'],2)?></span></small><?php endif; ?>
                    <a href="transaction_history.php" style="display:inline-block;margin-top:8px;color:#d8d8d8;font-size:12px">View transaction history →</a>
                </div>
            </div>
        </div>

        <div class="loco-profile-classic-layout">
          <div class="loco-profile-main">
        <!-- Biography Box using Panel Architecture styles -->
        <section class="loco-community-panel" style="margin-bottom: 30px;">
            <div class="loco-section-heading">
                <div>
                    <h2>About Player</h2>
                    <p>Your public biography and favorite games, saved to your account.</p>
                </div>
            </div>
            <div class="loco-form-grid">
                <div class="loco-full-width" style="color: #ccc; line-height: 1.6; background: #121212; padding: 15px; border-radius: 4px; border: 1px solid #2a2a2a;">
                    <?= !empty($user_profile['bio']) ? nl2br(h($user_profile['bio'])) : "No biography yet." ?>
                </div>
            </div>
        </section>

         <!-- Games Catalog Grid Section using standard game-grid structure -->
        <section id="library" class="loco-community-panel">
            <div class="loco-section-heading">
                <div>
                    <h2>Games Library (<?=count($purchased_games)?>)</h2>
                    <p>Games purchased through the LOCO Store appear here in your library.</p>
                </div>
            </div>

            <?php if(empty($purchased_games)): ?>
                <div class="loco-empty-state">
                    You haven't bought any games yet. Visit the <a href="Products.php">LOCO Store</a> to purchase your first catalog item.
                </div>
            <?php else: ?>
                <div class="game-grid">
                    <?php foreach($purchased_games as $game): ?>
                        <div class="game-card">
                            <img src="<?=h($game['image_url'] ?? 'images/gameplay_01.jpg')?>" class="game-thumb" alt="Game Cover">
                            <div class="game-details">
                                <h4 class="game-title" style="margin-bottom: 5px;"><?=h($game['title'])?></h4>
                                <p class="game-desc" style="font-size: 11px; color: #888;">Acquired on <?=date('M d, Y', strtotime($game['purchase_date']))?></p>
                            </div>
                            <div class="game-purchase-bar">
                                <span class="game-buy-btn" style="text-align:center;background:#343a42;border-color:#49535d;width:100%">IN LIBRARY</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
          </div>
          <aside class="loco-profile-side">
            <section class="loco-community-panel">
              <div class="loco-section-heading"><h2>PLAYER DETAILS</h2></div>
              <div class="loco-profile-side-avatar"><img src="profile_avatar.php?id=<?=(int)$current_user_id?>" alt="Player avatar"></div>
              <strong style="display:block;color:#fff;margin-top:10px"><?=h($user_profile['display_name']?:$currentUser)?></strong>
              <small style="color:#c1bec4">@<?=h($currentUser)?></small>
              <div class="loco-profile-statline"><span>Owned games</span><strong><?=count($purchased_games)?></strong></div>
              <div class="loco-profile-statline"><span>Platform</span><strong>LOCO</strong></div>
            </section>
            <section class="loco-community-panel">
              <div class="loco-section-heading"><h2>FAVORITE GAMES</h2></div>
              <p style="font-size:.82rem;color:#e0dce1;line-height:1.55"><?=h($user_profile['favorite_games']?:'None selected yet.')?></p>
            </section>
            <section class="loco-community-panel">
              <div class="loco-section-heading"><h2>ACCOUNT LINKS</h2></div>
              <div class="loco-profile-side-links">
                <a href="community.php?tab=profile">Edit Profile & Photo</a>
                <a href="community.php?tab=inventory">My Inventory</a>
                <a href="wishlist.php">My Wishlist</a>
                <a href="transaction_history.php">Purchase History</a>
              </div>
            </section>
          </aside>
        </div>
    </div>

    <!-- Footer Bar -->
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
                    <a href="work_in_progress.php?feature=YouTube" class="loco-social-link">YT</a>
                    <a href="work_in_progress.php?feature=Twitch" class="loco-social-link">TW</a>
                    <a href="work_in_progress.php?feature=Facebook" class="loco-social-link">FB</a>
                    <a href="work_in_progress.php?feature=X%20Social" class="loco-social-link">X</a>
                </div>
            </div>
            <div class="loco-footer-links-grid">
                <div class="loco-links-col">
                    <h4>LOCO</h4>
                    <a href="work_in_progress.php?feature=About%20Loco">About Loco</a>
                    <a href="work_in_progress.php?feature=System%20Status">System Status</a>
                    <a href="work_in_progress.php?feature=Track%20Routes">Track Routes</a>
                    <a href="work_in_progress.php?feature=Dev%20Blog">Dev Blog</a>
                </div>
                <div class="loco-links-col">
                    <h4>SERVICES</h4>
                    <a href="work_in_progress.php?feature=API%20Portal">API Portal</a>
                    <a href="work_in_progress.php?feature=Careers">Careers</a>
                    <a href="work_in_progress.php?feature=Hardware%20Specs">Hardware Specs</a>
                    <a href="work_in_progress.php?feature=Enterprise">Enterprise</a>
                </div>
                <div class="loco-links-col">
                    <h4>LEGAL</h4>
                    <a href="work_in_progress.php?feature=Privacy">Privacy</a>
                    <a href="work_in_progress.php?feature=Accessibility">Accessibility</a>
                    <a href="work_in_progress.php?feature=Notices%20%26%20Policies">Notices & Policies</a>
                    <a href="work_in_progress.php?feature=Cookies">Cookies</a>
                </div>
                <div class="loco-links-col">
                    <h4>SUPPORT</h4>
                    <a href="work_in_progress.php?feature=Get%20Support">Get Support</a>
                    <a href="work_in_progress.php?feature=My%20Account">My Account</a>
                    <a href="work_in_progress.php?feature=Documentation">Documentation</a>
                    <a href="work_in_progress.php?feature=Contact%20Us">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
    </footer>
</body>
</html>