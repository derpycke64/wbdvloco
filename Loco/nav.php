<?php
// Shared classic-LOCO navigation. Pages including nav.php already start the session and load db.php.
$isLoggedIn = !empty($_SESSION['user_id']) && isset($_SESSION['username']);
$currentUser = $isLoggedIn ? (string)$_SESSION['username'] : '';
$current_page = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
$isAdmin = false;
$navWalletBalance = 0.0;
if ($isLoggedIn && isset($pdo) && $pdo instanceof PDO) {
    try {
        $navStmt = $pdo->prepare('SELECT role, wallet_balance FROM users WHERE id=:uid LIMIT 1');
        $navStmt->execute([':uid'=>(int)$_SESSION['user_id']]);
        $navAccount = $navStmt->fetch(PDO::FETCH_ASSOC);
        $isAdmin = ($navAccount['role'] ?? 'user') === 'admin';
        $navWalletBalance = (float)($navAccount['wallet_balance'] ?? 0);
    } catch (PDOException $e) {
        // Keep site navigation available even during database migration.
        error_log('LOCO navigation account lookup: '.$e->getMessage());
    }
}
function locoNavEsc($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$storeActive = in_array($current_page, ['products.php','game_details.php','buy_game.php','wishlist.php','wallet.php'], true);
$communityActive = $current_page === 'community.php';
$libraryActive = $current_page === 'profile.php';
?>
<header class="loco-global-nav">
  <div class="loco-nav-inner">
    <a href="Products.php" class="loco-nav-logo" aria-label="LOCO Store home">
      <img src="images/logo.png" class="loco-logo-img" alt="LOCO emblem">
      <span>LOCO</span>
    </a>
    <nav class="loco-nav-links" aria-label="Main navigation">
      <div class="loco-nav-group">
        <a class="loco-nav-link <?= $storeActive?'active':'' ?>" href="Products.php">STORE</a>
        <div class="loco-nav-menu">
          <a href="Products.php">Store Home</a>
          <a href="Products.php#catalog-tab-grid">Browse Games</a>
          <?php if($isLoggedIn): ?>
            <a href="wishlist.php">Wishlist</a>
            <a href="wallet.php">Add Wallet Funds</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="loco-nav-group">
        <a class="loco-nav-link <?= $libraryActive?'active':'' ?>" href="<?= $isLoggedIn?'profile.php#library':'Login.php' ?>">LIBRARY</a>
        <div class="loco-nav-menu">
          <a href="<?= $isLoggedIn?'profile.php#library':'Login.php' ?>">Owned Games</a>
          <?php if($isLoggedIn): ?><a href="transaction_history.php">Purchase History</a><?php endif; ?>
        </div>
      </div>
      <div class="loco-nav-group">
        <a class="loco-nav-link <?= $communityActive?'active':'' ?>" href="<?= $isLoggedIn?'community.php?tab=market':'Login.php' ?>">COMMUNITY</a>
        <div class="loco-nav-menu">
          <?php if($isLoggedIn): ?>
            <a href="community.php?tab=market">Marketplace</a>
            <a href="community.php?tab=sell">My Listings</a>
            <a href="community.php?tab=trades">Trade Center</a>
            <a href="community.php?tab=workshop">Workshop</a>
            <a href="community.php?tab=discussions">Discussions</a>
          <?php else: ?>
            <a href="Login.php">Sign in to Community</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
    <div class="loco-nav-account">
      <?php if($isLoggedIn): ?>
        <div class="loco-nav-group loco-nav-account-group">
          <button class="loco-nav-user-trigger" type="button" aria-label="Open account menu" aria-haspopup="true" aria-expanded="false">
            <img src="profile_avatar.php?id=<?=(int)$_SESSION['user_id']?>" class="loco-nav-avatar" alt="">
            <span class="loco-nav-user-info">
              <strong><?=locoNavEsc($currentUser)?></strong>
              <?php if($navWalletBalance>0): ?>
                <span class="loco-nav-balance">₱<?=number_format($navWalletBalance,2)?></span>
              <?php endif; ?>
            </span>
            <span class="loco-nav-caret" aria-hidden="true">▾</span>
          </button>
          <div class="loco-nav-menu loco-nav-menu-right">
            <a href="profile.php">My Profile</a>
            <a href="community.php?tab=profile">Edit Profile / Settings</a>
            <a href="community.php?tab=inventory">Inventory</a>
            <a href="community.php?tab=reports">My Reports</a>
            <a href="wallet.php">Wallet / Add Funds</a>
            <?php if($isAdmin): ?><a href="admin.php">Admin Dashboard</a><?php endif; ?>
            <a href="logout.php" class="loco-menu-logout">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="Login.php" class="loco-nav-signin">SIGN IN</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<script>
(function(){
  // Hover works on desktop; click toggles the account dropdown on touch/keyboard.
  const buttons=document.querySelectorAll('.loco-nav-user-trigger');
  buttons.forEach(function(button){
    button.addEventListener('click',function(event){
      event.stopPropagation();
      const group=button.closest('.loco-nav-group');
      const open=!group.classList.contains('is-open');
      document.querySelectorAll('.loco-nav-group.is-open').forEach(function(g){g.classList.remove('is-open');const b=g.querySelector('button[aria-expanded]');if(b)b.setAttribute('aria-expanded','false');});
      group.classList.toggle('is-open',open);
      button.setAttribute('aria-expanded',String(open));
    });
  });
  // On touchscreen devices the first tap expands Store/Library/Community; menu links navigate normally.
  document.querySelectorAll('.loco-nav-group > .loco-nav-link').forEach(function(link){
    link.addEventListener('click',function(event){
      if(window.matchMedia('(hover: none)').matches){
        const group=link.closest('.loco-nav-group');
        if(!group.classList.contains('is-open')){
          event.preventDefault();
          document.querySelectorAll('.loco-nav-group.is-open').forEach(function(g){g.classList.remove('is-open');});
          group.classList.add('is-open');
        }
      }
    });
  });
  document.addEventListener('click',function(event){if(!event.target.closest('.loco-nav-group'))document.querySelectorAll('.loco-nav-group.is-open').forEach(function(g){g.classList.remove('is-open');const b=g.querySelector('button[aria-expanded]');if(b)b.setAttribute('aria-expanded','false');});});
  document.addEventListener('keydown',function(event){if(event.key==='Escape')document.querySelectorAll('.loco-nav-group.is-open').forEach(function(g){g.classList.remove('is-open');const b=g.querySelector('button[aria-expanded]');if(b){b.setAttribute('aria-expanded','false');b.focus();}});});
})();
</script>
