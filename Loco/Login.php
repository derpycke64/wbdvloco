<?php
session_start();
require_once 'db.php';

if(isset($_SESSION['username'])){
    header("Location: products.php");
    exit();
}

$message = "";
$prefilled_name = isset($_GET['Registered']) ? $_GET['Registered'] : '';
$success_msg = !empty($prefilled_name) ? "Registration Successful" : "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(!empty($username) && !empty($password)){
        try {
            $sql = "SELECT * FROM users WHERE username = :user_param OR email = :email_param LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_param' => $username, 
                ':email_param' => $username
            ]);
            $user = $stmt->fetch();
            
            if($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']        = $user['id'];
                $_SESSION['username']       = $user['username'];
                $_SESSION['wallet_balance'] = $user['wallet_balance'];
                header("Location: products.php");
                exit();
            } else {
                $message = "The username/email or password you typed is incorrect.";
            }
        }
        catch (\PDOException $e) {
            $message = "Database login error: " . $e->getMessage();
        }
    }
    else {
        $message = "Please fill in all fields.";
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
                    <?php
                    $current_page = basename($_SERVER['SCRIPT_NAME']);
                    ?>
                    
                    <a href="products.php" class="loco-nav-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">STORE</a>
                    <a href="community.php" class="loco-nav-link <?php echo ($current_page == 'community.php') ? 'active' : ''; ?>">COMMUNITY</a>
                    <a href="about.php" class="loco-nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">ABOUT</a>
                    <a href="support.php" class="loco-nav-link <?php echo ($current_page == 'support.php') ? 'active' : ''; ?>">SUPPORT</a>
                </nav>
            </div>
        </header>
        
        <div class="loco-header-outside">
            <h1 class="loco-logo-text">Sign in</h1>
        </div>

        <div class="loco-panel">
            <form method="POST" action="Login.php">
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
                </div>  
                <?php if(!empty($message)) echo "<p class='loco-alert-error'>$message</p>"; ?>
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