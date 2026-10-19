<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: Login.php");
    exit();
}


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['game']) && isset($_GET['price'])) {
    $new_game = $_GET['game'];
    $new_price = floatval(str_replace([',', 'P', '₱'], '', $_GET['price'])); 
    $already_in_cart = false;
    foreach ($_SESSION['cart'] as $item) {
        if ($item['title'] === $new_game) {
            $already_in_cart = true;
            break;
        }
    }
    
    if (!$already_in_cart) {
        $_SESSION['cart'][] = [
            'title' => $new_game,
            'price' => $new_price
        ];
    }
    

    header("Location: payment.php");
    exit();
}


if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['key'])) {
    $key_to_drop = intval($_GET['key']);
    if (isset($_SESSION['cart'][$key_to_drop])) {
        unset($_SESSION['cart'][$key_to_drop]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); 
    }
    header("Location: payment.php");
    exit();
}

$cart_count = count($_SESSION['cart']);
$is_cart_empty = ($cart_count === 0);

$total_sum = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_sum += $item['price'];
}


$game_image_map = [
    "Buckshot Roulette" => "images/Buckshot_Roulette_Cover.jpg",
    "Deltarune" => "images/deltarune.jpg",
    "Metal Gear Solid 4: Guns of the Patriots" => "images/mgs4.jpg",
    "The Witcher 3: Wild Hunt" => "images/witcher3.jpg",
    "Metal Gear Rising: Revengeance" => "images/mgrr.jpg",
    "Saints Row IV: Re-Elected" => "images/SaintsRowIV.jpg",
    "NieR Replicant" => "images/nierreplicant.webp",
    "NieR Automata" => "images/Nier_Automata_cover_art.jpg",
    "Yakuza 0" => "images/yakuza0.jpg",
    "Half-Life Alyx" => "images/Half-life_Alyx_Cover_Art.jpg",
    "Apex Legends" => "images/apex.jpg",
    "DOTA 2" => "images/dota2.jpg",
    "Left 4 Dead" => "images/left4dead.webp",
    "Left 4 Dead 2" => "images/Left4dead2cover.webp",
    "Half-Life 2" => "images/halflife2.jpg",
    "Hades" => "images/hades.jpg",
    "Hollow Knight: Silksong" => "images/silksong.jpg",
    "Omori" => "images/omori.jpg",
    "Doki Doki Literature Club Plus" => "images/Doki_Doki_Literature_Club_Cover.jpg"
];

$show_receipt = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$is_cart_empty) {
    $show_receipt = true;
    $_SESSION['cart'] = []; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Shopping Cart</title>
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
                    <?php
                    $current_page = basename($_SERVER['SCRIPT_NAME']);
                    ?>
                    
                    <a href="products.php" class="loco-nav-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">STORE</a>
                    <a href="community.php" class="loco-nav-link <?php echo ($current_page == 'community.php') ? 'active' : ''; ?>">COMMUNITY</a>
                    <a href="about.php" class="loco-nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">ABOUT</a>
                    <a href="support.php" class="loco-nav-link <?php echo ($current_page == 'support.php') ? 'active' : ''; ?>">SUPPORT</a>
                </nav>
            </div>
            
            <div class="loco-profile-badge">
                <span> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="loco-logout-link">Logout</a>
            </div>
        </div>
    </header>

    
    <div class="store-wrapper">
        
           <?php if ($show_receipt): ?>
            <div class="loco-receipt-card">
                <h2>Purchase Complete!</h2>
                <p>
                    ✓ Transaction authorized. Your digital game licenses have been successfully added to your LOCO profile account library!
                </p>
                <a href="products.php" class="loco-success-btn-override">Return to Storefront</a>
            </div>
        <?php else: ?>
            
           <div class="loco-store-main-col">
                <div class="loco-header-outside" style="margin: 0; max-width: 100%;">
                    <h1 class="loco-logo-text" style="font-size: 1.6rem; padding-bottom: 10px;">
                        Your Shopping Cart (<?php echo $cart_count; ?> <?php echo $cart_count === 1 ? 'item' : 'items'; ?>)
                    </h1>
                </div>

                <?php if ($is_cart_empty): ?>
                    <div class="loco-cart-item-box" style="padding: 30px; justify-content: center; background-color: rgba(0,0,0,0.2);">
                        <p style="color: #7a7e85; font-size: 0.95rem; margin: 0; font-weight: bold; text-align: center;">Your shopping cart is currently empty.</p>
                    </div>
                    
                    <div class="loco-cart-actions-row" style="justify-content: flex-start;">
                        <a href="products.php" class="loco-cart-secondary-btn">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
                        <?php foreach ($_SESSION['cart'] as $index => $item): 
                            $thumb_src = isset($game_image_map[$item['title']]) ? $game_image_map[$item['title']] : "images/logo.png";
                        ?>
                            <div class="loco-cart-item-box">
                                <div class="loco-cart-item-details">
                                    <img src="<?php echo htmlspecialchars($thumb_src); ?>" class="loco-cart-thumb" alt="Game Thumbnail">
                                    <div class="loco-cart-text-meta">
                                        <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <span class="loco-cart-device-tag">💻 For my account ▼</span>
                                    </div>
                                </div>
                                <div class="loco-cart-price-side">
                                    <span class="loco-cart-item-cost">₱<?php echo number_format($item['price'], 2); ?></span>
                                    <a href="payment.php?action=remove&key=<?php echo $index; ?>" class="loco-cart-remove-link">Remove</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="loco-cart-actions-row">
                        <a href="products.php" class="loco-cart-secondary-btn">Continue Shopping</a>
                        <form method="POST" action="" style="margin: 0;">
                            <button type="submit" class="loco-brand-gloss-btn" style="border-radius: 2px;">Continue to payment</button>
                        </form>
                    </div>
                <?php endif; ?>
               
                
                <h3 class="loco-cart-rec-heading">RECOMMENDATIONS FOR YOU</h3>
                <div class="loco-cart-rec-grid">
                    <a href="payment.php?game=Hades&price=765.00" class="game-card" style="text-decoration: none;">
                        <img src="images/hades.jpg" class="game-thumb" alt="Hades">
                        <div class="loco-cart-rec-bar">
                            <span class="game-price">₱765.00</span>
                        </div>
                    </a>
                    
                    <a href="payment.php?game=Yakuza%200&price=1990.00" class="game-card" style="text-decoration: none;">
                        <img src="images/yakuza0.jpg" class="game-thumb" alt="Yakuza 0">
                        <div class="loco-cart-rec-bar">
                            <span class="game-price">₱1,990.00</span>
                        </div>
                    </a>
                    
                    <a href="payment.php?game=Omori&price=449.95" class="game-card" style="text-decoration: none;">
                        <img src="images/omori.jpg" class="game-thumb" alt="Omori">
                        <div class="loco-cart-rec-bar">
                            <span class="game-price">₱449.95</span>
                        </div>
                    </a>

                </div>
            </div>
            <div class="loco-store-sidebar-col">
                <div class="sidebar-panel" style="padding: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #7a7e85; font-size: 0.8rem; font-weight: bold;">Estimated total</span>
                        <span style="color: #ffffff; font-size: 1.1rem; font-weight: bold;">₱<?php echo number_format($total_sum, 2); ?></span>
                    </div>
                    <p style="color: #666e7a; font-size: 0.75rem; margin: 0 0 15px 0; line-height: 1.3;">Sales tax will be calculated during checkout where applicable</p>
                    
                    <form method="POST" action="" style="width: 100%;">
                        <button type="submit" class="loco-brand-gloss-btn" <?php echo $is_cart_empty ? "disabled style='opacity:0.3; cursor:not-allowed; width:100%; border-radius:2px; padding:10px;'" : "style='width: 100%; border-radius: 2px; padding: 10px; font-size: 0.85rem;'"; ?>>
                            Continue to payment
                        </button>
                    </form>
                </div>

                <div class="sidebar-panel" style="padding: 15px; background-color: transparent; border-color: rgba(255,255,255,0.03);">
                    <p style="color: #7a7e85; font-size: 0.75rem; line-height: 1.4; margin: 0;">
                        A purchase of a digital product grants a license for the product on LOCO.<br><br>
                        For full terms and conditions, please see the LOCO Subscriber Agreement.
                    </p>
                </div>
            </div> 
        <?php endif; ?>

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