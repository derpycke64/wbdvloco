<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Quick interactive trace handling marketplace item interactions without SQL
$trade_success = isset($_GET['action']) && $_GET['action'] === 'trade';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Community & Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">

    <header class="loco-global-nav">
        <div class="loco-nav-inner">
            <div class="loco-nav-left">
                <a href="products.php" class="loco-nav-logo">
                    <img src="images/logo.png" class="loco-logo-img" alt="Logo">
                    LOCO
                </a>
                <nav class="loco-nav-links">
                    <a href="products.php" class="loco-nav-link">STORE</a>
                    <a href="community.php" class="loco-nav-link active">COMMUNITY</a>
                    <a href="about.php" class="loco-nav-link">ABOUT</a>
                    <a href="support.php" class="loco-nav-link">SUPPORT</a>
                </nav>
            </div>
            <div class="loco-profile-badge">
                <span>🎮 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="loco-logout-link">Logout</a>
            </div>
        </div>
    </header>

    <div class="store-wrapper" style="margin-top: 80px; align-items: flex-start;">
        
        <div class="loco-store-main-col" style="flex: 6;">
            <div class="loco-header-outside" style="margin: 0; max-width: 100%;">
                <h1 class="loco-logo-text" style="font-size: 1.6rem; padding-bottom: 10px;">Peer-to-Peer Digital Marketplace</h1>
            </div>
            
            <div class="loco-panel" style="max-width: 100%; padding: 20px; text-align: left;">
                <p style="color: #b0b3b8; font-size: 0.85rem; margin-top: 0; margin-bottom: 15px; line-height: 1.4;">
                    Buy, sell, and trade cosmetic software item drops directly with other active LOCO players across public engine network streams.
                </p>

                <?php if ($trade_success): ?>
                    <p class="loco-alert-success" style="text-align: center; margin-bottom: 15px; padding: 8px;">✓ Trade Offer successfully initialized! Awaiting user confirmation badge logs.</p>
                <?php endif; ?>

                <div class="loco-market-row">
                    <div class="loco-market-item-details">
                        <img src="images/logo.png" class="loco-item-avatar" alt="item">
                        <div>
                            <h5 style="color: #ffffff; margin: 0 0 3px 0; font-size: 0.9rem;">Counter-Strike 2: Crimson Locomotive Knife</h5>
                            <span style="color: #7a7e85; font-size: 0.75rem;">Seller: Player_Stoker04</span>
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; flex-direction: column; gap: 4px;">
                        <span style="color: #4caf50; font-weight: bold; font-size: 0.95rem;">₱4,200.00</span>
                        <a href="community.php?action=trade" class="loco-brand-gloss-btn" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 2px; text-decoration: none;">Buy Item</a>
                    </div>
                </div>

                <div class="loco-market-row">
                    <div class="loco-market-item-details">
                        <img src="images/logo.png" class="loco-item-avatar" alt="item">
                        <div>
                            <h5 style="color: #ffffff; margin: 0 0 3px 0; font-size: 0.9rem;">DOTA 2: Golden Boiler Valve Pauldrons</h5>
                            <span style="color: #7a7e85; font-size: 0.75rem;">Seller: Kiryu_Kazuma</span>
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; flex-direction: column; gap: 4px;">
                        <span style="color: #4caf50; font-weight: bold; font-size: 0.95rem;">₱1,150.00</span>
                        <a href="community.php?action=trade" class="loco-brand-gloss-btn" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 2px; text-decoration: none;">Buy Item</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="loco-store-sidebar-col" style="flex: 4;">
            <div class="loco-header-outside" style="margin: 0; max-width: 100%;">
                <h1 class="loco-logo-text" style="font-size: 1.6rem; padding-bottom: 10px;">Creations Hub</h1>
            </div>

            <div class="sidebar-panel" style="padding: 15px;">
                <h3 class="loco-label-retro" style="background-color: #e60000; margin: -15px -15px 15px -15px; padding: 8px 12px; color: white; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">Workshop Files</h3>
                <p style="color: #7a7e85; font-size: 0.75rem; line-height: 1.4; margin: 0;">
                    Browse and install custom maps, modifications, and content built by the community.
                </p>

                <div class="loco-workshop-grid">
          
                    <div class="loco-workshop-card">
                        <img src="images/hades.jpg" class="loco-workshop-thumb" alt="Hades">
                        <h6 style="color: #fff; margin: 0 0 2px 0; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Hades: Endless Hell Map Mod</h6>
                        <span style="color: #4caf50; font-size: 0.7rem; font-weight: bold; cursor: pointer;" onclick="alert('Downloading community workshop addition completely!')"> Subscribe</span>
                    </div>
                    
                    <div class="loco-workshop-card">
                        <img src="images/left4dead.webp" class="loco-workshop-thumb" alt="L4D2">
                        <h6 style="color: #fff; margin: 0 0 2px 0; font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">L4D2: Retro Locomotive Train Skin</h6>
                        <span style="color: #4caf50; font-size: 0.7rem; font-weight: bold; cursor: pointer;" onclick="alert('Downloading community workshop addition completely!')"> Subscribe</span>
                    </div>

                </div>
            </div>
        </div>

    </div>

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

</body>
</html>