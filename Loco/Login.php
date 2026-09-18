<?php
session_start();
if(isset($_SESSION['username'])){
    header("Location: products.php");
    exit();
}

$prefilled_name = isset($_GET['Registered']) ? $_GET['Registered'] : '';
$success_msg = !empty($prefilled_name) ? "Registration Successful" : "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST['username']);
    if(!empty($username)){
        $_SESSION['username'] = $username;
        header("Location: Products.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>LOCO - Login</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header class="loco-global-nav">
            <div class="loco-nav-inner">
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
        </header>
        
        <div class="loco-header-outside">
            <h1 class="loco-logo-text">Sign in</h1>
        </div>

        <div class="loco-panel">
            <form method="POST" action="login.php">
                <div class="loco-group">
                    <label class="loco-label">USERNAME</label>
                    <input type="text" name="username" class="loco-input" value="<?php echo htmlspecialchars($prefilled_name); ?>" required autocomplete="off">
                </div>

                <div class="loco-group">
                    <label class="loco-label-password">PASSWORD</label>
                    <input type="password" name="password" class="loco-input" required>
                </div>

                <div class="loco-forgot-container">
                    <a href="#" class="loco-forgot-link">Forgot Password?</a>
                </div>  X
                <?php if(!empty($success_msg)) echo "<p class='loco-alert-success'>$success_msg</p>"; ?>

                <button type="submit" class="loco-button">Sign in</button>
            </form>
        </div>

        <div class="loco-redirect-canvas">
            <div class="loco-redirect-split-wrapper">
                
                <div class="loco-redirect-action-box">
                    <h2>New to LOCO?</h2>
                    <a href="register.php" class="loco-brand-gloss-btn">Create an account</a>
                </div>

                <div class="loco-redirect-desc-box">
                    <p>It's free and easy. Discover thousands of games to play with millions of new friends. <a href="#">Learn more about LOCO</a></p>
                </div>

            </div>
        </div>

        <footer class="loco-footer-bar">
            <div class="loco-sub-footer">
                <div class="loco-sub-footer-inner">
                    
                    <div class="loco-footer-brand-col">
                        <div class="loco-footer-logos">
                            <span class="loco-footer-logo-text">LOCO</span>
                            <span class="loco-footer-logo-sub">Stoker Studios</span>
                        </div>
                        <p class="loco-copyright-text">
                            © 2026 Stoker Studios. All rights reserved. All trademarks are property of their respective owners in the PH and other countries.
                        </p>
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
                            <a href="#about">About Loco</a>
                            <a href="#status">System Status</a>
                            <a href="#routes">Track Routes</a>
                            <a href="#blog">Dev Blog</a>
                        </div>

                        <div class="loco-links-col">
                            <h4>SERVICES</h4>
                            <a href="#api">API Portal</a>
                            <a href="#jobs">Careers</a>
                            <a href="#hardware">Hardware Specs</a>
                            <a href="#enterprise">Enterprise</a>
                        </div>

                        <div class="loco-links-col">
                            <h4>LEGAL</h4>
                            <a href="#privacy">Privacy</a>
                            <a href="#terms">Accessibility</a>
                            <a href="#policies">Notices & Policies</a>
                            <a href="#cookies">Cookies</a>
                        </div>

                        <div class="loco-links-col">
                            <h4>SUPPORT</h4>
                            <a href="#help">Get Support</a>
                            <a href="#account">My Account</a>
                            <a href="#docs">Documentation</a>
                            <a href="#contact">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>