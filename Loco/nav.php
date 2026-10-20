<?php
$isLoggedIn = isset($_SESSION['username'], $_SESSION['user_id']);
$currentUser = $isLoggedIn ? (string) $_SESSION['username'] : '';
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="loco-global-nav">
    <div class="loco-nav-inner">
        <div class="loco-nav-left">
            <a href="Products.php" class="loco-nav-logo">
                <img src="images/logo.png" class="loco-logo-img" alt="LOCO logo">
                LOCO
            </a>
            <nav class="loco-nav-links" aria-label="Main navigation">
                <a href="Products.php" class="loco-nav-link <?php echo in_array($current_page, ['Products.php', 'game_details.php'], true) ? 'active' : ''; ?>">STORE</a>
                <a href="community.php" class="loco-nav-link <?php echo $current_page === 'community.php' ? 'active' : ''; ?>">COMMUNITY</a>
                <?php if ($isLoggedIn): ?>
                    <a href="library.php" class="loco-nav-link <?php echo $current_page === 'library.php' ? 'active' : ''; ?>">LIBRARY</a>
                    <a href="profile.php" class="loco-nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">PROFILE</a>
                <?php else: ?>
                    <a href="about.php" class="loco-nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">ABOUT</a>
                    <a href="support.php" class="loco-nav-link <?php echo $current_page === 'support.php' ? 'active' : ''; ?>">SUPPORT</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="loco-profile-badge">
            <?php if ($isLoggedIn): ?>
                <a href="profile.php" class="loco-current-user">🎮 <?php echo htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8'); ?></a>
                <a href="Logout.php" class="loco-logout-link">Logout</a>
            <?php else: ?>
                <a href="Login.php" class="loco-logout-link">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</header>
