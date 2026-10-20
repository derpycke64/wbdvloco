<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - About Us</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">

    <<?php include_once 'nav.php'; ?>

    <div class="store-wrapper" style="justify-content: center; margin-top: 100px; margin-bottom: 120px;">
        <div class="loco-panel" style="max-width: 650px; width: 100%; text-align: left; padding: 40px; margin: 0;">
            <h1 class="loco-logo-text" style="font-size: 1.8rem; padding-bottom: 10px; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #2d3035; color: #ffffff; font-weight: 800; letter-spacing: 1px;">ABOUT PLATFORM</h1>
            
            <p style="color: #ffffff; font-size: 0.95rem; line-height: 1.6; margin-top: 0; margin-bottom: 20px; font-weight: 600;">
                LOCO is a high-velocity, vintage-inspired gaming content delivery network pipeline platform environment engineered by <strong>Stoker Studios</strong>.
            </p>
            
            <p style="color: #7a7e85; font-size: 0.85rem; line-height: 1.6; margin: 0;">
                Our mission is to bring back the crisp, tactile interface layout structures of early 2006 desktop application client environments. The platform is fully optimized for database-free array session storage and seamless multi-item tracking architectures.
            </p>
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