<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Technical Support</title>
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
                    <a href="community.php" class="loco-nav-link">COMMUNITY</a>
                    <a href="about.php" class="loco-nav-link">ABOUT</a>
                    <a href="support.php" class="loco-nav-link active">SUPPORT</a>
                </nav>
            </div>
            <div class="loco-profile-badge">
                <span> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="loco-logout-link">Logout</a>
            </div>
        </div>
    </header>

    
    <div class="store-wrapper" style="max-width: 600px; margin: 80px auto; display: block;">
        <div class="loco-panel" style="max-width: 100%; text-align: left;">
            <h1 class="loco-logo-text" style="font-size: 1.8rem; padding-bottom: 10px; margin-bottom: 15px; border-bottom: 1px solid #333;">SUPPORT HELPDESK</h1>
            <p style="color: #b0b3b8; font-size: 0.9rem; line-height: 1.6; margin-bottom: 25px;">
                Having trouble routing parameter allocations or loading image thumbnails? Our automated support scripts are here to help.
            </p>
            <div style="background-color: #111215; border: 1px solid #333; padding: 12px; margin-bottom: 10px; color: #ffffff; font-size: 0.85rem; font-weight: bold; cursor: pointer;">
                » Account Session Management Assistance
            </div>
            <div style="background-color: #111215; border: 1px solid #333; padding: 12px; margin-bottom: 10px; color: #ffffff; font-size: 0.85rem; font-weight: bold; cursor: pointer;">
                » Invoice Billing and Checkout Problems
            </div>
            <div style="background-color: #111215; border: 1px solid #333; padding: 12px; color: #ffffff; font-size: 0.85rem; font-weight: bold; cursor: pointer;">
                » WampServer Directory Allocation Guides
            </div>
        </div>
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

</body>
</html>