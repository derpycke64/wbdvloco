<?php
$isLoggedIn = isset($_SESSION['username']) && isset($_SESSION['user_id']);
$currentUser = $isLoggedIn ? (string)$_SESSION['username'] : '';
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="loco-global-nav">
    <div class="loco-nav-inner">
        <div class="loco-nav-left">
            <a href="products.php" class="loco-nav-logo">
                <img src="images/logo.png" class="loco-logo-img" alt="Logo">
                LOCO
            </a>
            <nav class="loco-nav-links">
                <a href="products.php" class="loco-nav-link <?php echo ($current_page == 'products.php' || $current_page == 'game_details.php') ? 'active' : ''; ?>">STORE</a>
                <a href="community.php" class="loco-nav-link <?php echo ($current_page == 'community.php') ? 'active' : ''; ?>">COMMUNITY</a>
                <a href="about.php" class="loco-nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">ABOUT</a>
                
                <?php if ($isLoggedIn): ?>
                    <a href="profile.php" class="loco-nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" style="text-transform: uppercase;">
                        <?php echo htmlspecialchars($currentUser); ?>
                    </a>
                <?php else: ?>
                    <a href="support.php" class="loco-nav-link <?php echo ($current_page == 'support.php') ? 'active' : ''; ?>">SUPPORT</a>
                <?php endif; ?>
            </nav>
        </div>
        
        <div class="loco-profile-badge">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" style="color: #fff; text-decoration: none; font-weight: bold; margin-right: 15px;">
                    🎮 <?php echo htmlspecialchars($currentUser); ?>
                </a>
                <a href="logout.php" class="loco-logout-link">Logout</a>
            <?php else: ?>
                <!-- Clean CTA link layout encouraging visitors to sign up or join -->
                <a href="Login.php" class="loco-logout-link" style="background: #d32f2f; padding: 5px 15px; border-radius: 3px; color: #fff;">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</header>