<?php
// Force WAMP to display syntax glitches cleanly instead of going blank
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

if (($_SESSION['loco_game_catalog_version'] ?? '') !== 'original-games-v2') {
    unset($_SESSION['cart']);
    $_SESSION['loco_game_catalog_version'] = 'original-games-v2';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Storefront</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="loco-store-body">

    <!-- Universal Shared Navigation Header Layout -->
    <?php include_once 'nav.php'; ?>

    <div class="store-wrapper">
        <!-- Main Column Area Layout -->
        <div class="loco-store-main-col">
            <div class="loco-install-banner">
                <a href="#" class="loco-install-btn">Get Loco Now !</a>
                <p class="loco-install-text">Join LOCO for Free and get games delivered straight to your desktop with automatic updates and a massive gaming community pipeline platform environment.</p>
            </div>

            <!-- Promotional Banner Grid Rows Row elements -->
            <div class="loco-promo-row">
                <a href="game_details.php?id=1" class="loco-promo-card">
                    <img src="images/gameplay_01.jpg" class="loco-promo-img" alt="Nebula Drift original cover art">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">Nebula Drift</span>
                        <span class="loco-promo-price">₱2,490.00</span>
                    </div>
                </a>
                <a href="game_details.php?id=2" class="loco-promo-card">
                    <img src="images/gameplay_02.jpg" class="loco-promo-img" alt="Crimson Freight original cover art">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">Crimson Freight</span>
                        <span class="loco-promo-price">₱899.00</span>
                    </div>
                </a>
            </div>
            
            <!-- Dynamic Promotional Slideshow Carousel Viewport Container -->
            <div class="store-hero" id="carousel-viewport"></div>
            <div class="carousel-control-bar">
                <div class="carousel-tabs" id="carousel-tabs-row"></div>    
                <span class="carousel-status-text" id="carousel-label">Loading...</span>
                <span class="carousel-price-tag" id="carousel-price">$0.00</span>
            </div>
            
            <!-- Featured Games Section - Fully Dynamic Database Query Loop -->
            <h3 class="loco-label-thingy">Featured Games & Bargain Deals</h3>
            <div class="game-grid">
                <?php
                if (isset($pdo)) {
                    try {
                        // Grab the active user ID from the session if they are logged in
                        $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

                        // 🌟 SMART QUERY: Checks if the user already owns each game row
                        $featured_sql = "SELECT g.*, l.id AS library_check 
                                        FROM games g 
                                        LEFT JOIN library l ON g.id = l.game_id AND l.user_id = :user_id 
                                        LIMIT 2";
                                        
                        $featured_stmt = $pdo->prepare($featured_sql);
                        $featured_stmt->execute([':user_id' => $current_user_id]);
                        $featured_games = $featured_stmt->fetchAll();

                        if (!empty($featured_games)) {
                            foreach ($featured_games as $game) {
                                $display_price = ($game['price'] == 0) ? 'Free' : '₱' . number_format($game['price'], 2);
                                ?>
                                <div class="game-card">
                                    <a href="game_details.php?id=<?php echo $game['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($game['image_url']); ?>" class="game-thumb" alt="Game cover">
                                    </a>
                                    <div class="game-details">
                                        <h4 class="game-title">
                                            <a href="game_details.php?id=<?php echo $game['id']; ?>" style="color: #fff; text-decoration: none;">
                                                <?php echo htmlspecialchars($game['title']); ?>
                                            </a>
                                        </h4>
                                        <p class="game-desc"><?php echo htmlspecialchars($game['description']); ?></p>
                                    </div>
                                    <div class="game-purchase-bar">
                                        <span class="game-price"><?php echo $display_price; ?></span>
                                        
                                        <?php 
                                        // 🌟 DYNAMIC BUTTON CHECK: If library_check is not empty, they own it!
                                        if (!empty($game['library_check'])) { 
                                            ?>
                                            <span class="game-buy-btn" style="background: #444; color: #888; cursor: not-allowed; text-align: center; border: 1px solid #555;">OWNED</span>
                                            <?php 
                                        } else { 
                                            ?>
                                            <a href="javascript:void(0);" 
                                            onclick="handleAddToCart(event, '<?php echo addslashes($game['title']); ?>', '<?php echo $display_price; ?>', '<?php echo addslashes($game['image_url']); ?>')" 
                                            class="game-buy-btn">BUY NOW</a>
                                            <?php 
                                        } 
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    } catch (\PDOException $e) {
                        echo "<p class='loco-alert-danger'>Error loading cards: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                ?>
            </div>

            <!-- Tabbed Sort Categorization Content layout Grid Section -->
            <div class="loco-tabbed-section">
                <div class="loco-tabs-header">
                    <button class="loco-tab-nav active" data-catalog-category="sellers" onclick="switchCatalogTab('sellers')">Top Sellers</button>
                    <button class="loco-tab-nav" data-catalog-category="rated" onclick="switchCatalogTab('rated')">Top Rated</button>
                    <button class="loco-tab-nav" data-catalog-category="releases" onclick="switchCatalogTab('releases')">New Releases</button>
                </div>
                <p id="genre-filter-status" class="loco-filter-status" aria-live="polite"></p>
                <div class="loco-tab-content-grid" id="catalog-tab-grid"></div>
            </div>
        </div> 
        
        <!-- Right Sidebar Filter Panel Columns Column section -->
        <div class="loco-store-sidebar-col">
            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Find More...</h3>
                <div class="sidebar-content-box">
                    <a href="Products.php?genre=action#catalog-tab-grid" class="sidebar-link">» Action Games</a>
                    <a href="Products.php?genre=strategy#catalog-tab-grid" class="sidebar-link">» Strategy Hub</a>
                    <a href="Products.php?genre=indie-rpg#catalog-tab-grid" class="sidebar-link">» Indie / RPG</a>
                    <a href="Products.php?genre=casual#catalog-tab-grid" class="sidebar-link">» Casual Games</a>
                </div>
            </div>

            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Publisher Catalog</h3>
                <div class="sidebar-content-box">
                    <a href="Products.php?publisher=stoker#catalog-tab-grid" class="sidebar-link">» Stoker Studios Inc.</a>
                    <a href="Products.php?publisher=wally-stark#catalog-tab-grid" class="sidebar-link">» Wally Stark Studios</a>
                    <a href="Products.php?publisher=partner#catalog-tab-grid" class="sidebar-link">» Partner Studios</a>
                </div>
            </div>

            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Browse Tags</h3>
                <div class="sidebar-content-box">
                    <a href="Products.php?tag=coop#catalog-tab-grid" class="sidebar-link">» Co-op Multiplayer</a>
                    <a href="Products.php?tag=cyberpunk#catalog-tab-grid" class="sidebar-link">» Cyberpunk Neon</a>
                    <a href="Products.php?tag=retro#catalog-tab-grid" class="sidebar-link">» Retro Software Engine</a>
                </div>
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
    <script>
    function handleAddToCart(
        event, 
        gameTitle, 
        gamePrice, 
        gameThumb
    ) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const buyConfirm = confirm(
            `Purchase "${gameTitle}" for ${gamePrice}?`
        );
        if (!buyConfirm) {
            return; 
        }
        window.location.href = 
            `buy_game.php?game=${encodeURIComponent(gameTitle)}`;
    }

    // 🌟 SLIDER ENGINE DATA
       // 🌟 SLIDE DATA WITH AUTOMATIC FALLBACKS
    const slideData = <?php
        try {
            $slide_query = $pdo->query(
                "SELECT id, title, price, image_url AS img 
                 FROM games ORDER BY id ASC LIMIT 4"
            );
            $slides = $slide_query->fetchAll();
            if (!empty($slides)) {
                foreach($slides as &$s) {
                    $s['price'] = ($s['price'] == 0) ? 
                        'Free' : '₱' . number_format($s['price'], 2);
                }
                echo json_encode($slides);
            } else {
                throw new Exception("Empty records");
            }
        } catch(\Exception $e) { 
            // Safety backup fallback layout data arrays if database stalls
            echo json_encode([
                ["id"=>1, "title"=>"Nebula Drift", "price"=>"₱2,490.00", "img"=>"images/gameplay_01.jpg"],
                ["id"=>2, "title"=>"Crimson Freight", "price"=>"₱899.00", "img"=>"images/gameplay_02.jpg"],
                ["id"=>8, "title"=>"Ashen Crown", "price"=>"₱1,299.00", "img"=>"images/gameplay_08.jpg"],
                ["id"=>12, "title"=>"Starfall Cartographer", "price"=>"₱899.00", "img"=>"images/gameplay_12.jpg"]
            ]); 
        }
    ?>;

    // 🌟 TABBED CATALOG DATA WITH AUTOMATIC FALLBACKS
    const tabbedCatalogData = <?php
        try {
            $all_query = $pdo->query(
                "SELECT id, title, price, image_url AS img 
                 FROM games ORDER BY id ASC"
            );
            $all_games = $all_query->fetchAll();
            if (!empty($all_games)) {
                $formatted_catalog = ['sellers' => [], 'rated' => [], 'releases' => []];
                foreach($all_games as $index => $g) {
                    $item = [
                        'id'    => $g['id'],
                        'title' => $g['title'],
                        'price' => ($g['price'] == 0) ? 
                            'Free' : '₱' . number_format($g['price'], 2),
                        'img'   => $g['img']
                    ];
                    if ($index < 6) {
                        $formatted_catalog['sellers'][] = $item;
                    } else if ($index >= 6 && $index < 12) {
                        $formatted_catalog['rated'][] = $item;
                    } else {
                        $formatted_catalog['releases'][] = $item;
                    }
                }
                echo json_encode($formatted_catalog);
            } else {
                throw new Exception("Empty catalog");
            }
        } catch(\Exception $e) { 
            // Safety backup fallback catalog mapping list parameters if query drops
            echo json_encode([
                'sellers' => [
                    ["id"=>1, "title"=>"Nebula Drift", "price"=>"₱2,490.00", "img"=>"images/gameplay_01.jpg"],
                    ["id"=>2, "title"=>"Crimson Freight", "price"=>"₱899.00", "img"=>"images/gameplay_02.jpg"],
                    ["id"=>3, "title"=>"Moonroot Hollow", "price"=>"₱699.00", "img"=>"images/gameplay_03.jpg"],
                    ["id"=>4, "title"=>"Abyss Protocol", "price"=>"₱1,299.00", "img"=>"images/gameplay_04.jpg"],
                    ["id"=>5, "title"=>"Stormbanner Tactics", "price"=>"₱1,150.00", "img"=>"images/gameplay_05.jpg"],
                    ["id"=>6, "title"=>"Neon Velocity", "price"=>"₱1,399.00", "img"=>"images/gameplay_06.jpg"]
                ],
                'rated' => [],
                'releases' => []
            ]); 
        }
    ?>;




    // 🌟 DYNAMIC GENRE MAPPING
    const gameGenres = <?php
        try {
            $genre_sql = "SELECT g.title, gt.tag_name 
                          FROM games g 
                          JOIN game_tags gt ON g.id = gt.game_id 
                          WHERE gt.tag_name IN (
                            'action', 'strategy', 'indie-rpg', 'casual'
                          )";
            $stmt = $pdo->query($genre_sql); 
            $genres = [];
            while ($row = $stmt->fetch()) { 
                $genres[$row['title']][] = $row['tag_name']; 
            }
            echo json_encode($genres);
        } catch(\PDOException $e) { echo "{}"; }
    ?>;

    // 🌟 DYNAMIC TAG MAPPING
    const gameTags = <?php
        try {
            $tag_sql = "SELECT g.title, gt.tag_name 
                        FROM games g 
                        JOIN game_tags gt ON g.id = gt.game_id 
                        WHERE gt.tag_name IN (
                            'coop', 'cyberpunk', 'retro'
                        )";
            $stmt = $pdo->query($tag_sql); 
            $tags = [];
            while ($row = $stmt->fetch()) { 
                $tags[$row['title']][] = $row['tag_name']; 
            }
            echo json_encode($tags);
        } catch(\PDOException $e) { echo "{}"; }
    ?>;

    // 🌟 DYNAMIC PUBLISHER MAPPING
    const gamePublishers = <?php
        try {
            $pub_sql = "SELECT title, publisher FROM games";
            $stmt = $pdo->query($pub_sql); 
            $publishers = [];
            while ($row = $stmt->fetch()) { 
                $publishers[$row['title']] = $row['publisher']; 
            }
            echo json_encode($publishers);
        } catch(\PDOException $e) { echo "{}"; }
    ?>;

    let activeIndex = 0;
    let slideTimer;

    const viewport = 
        document.getElementById('carousel-viewport');
    const tabsRow = 
        document.getElementById('carousel-tabs-row');
    const label = 
        document.getElementById('carousel-label');
    const priceTag = 
        document.getElementById('carousel-price');

    function buildCarouselControls() {
        tabsRow.innerHTML = '';
        slideData.forEach((slide, index) => {
            const tab = document.createElement('div');
            tab.className = 'carousel-tab-item';
            if (index === activeIndex) {
                tab.classList.add('active');
            }
            tab.addEventListener('click', () => { 
                jumpToSlide(index); 
            });
            tabsRow.appendChild(tab);
        });
    }

    function renderActiveSlide() {
        if(!slideData.length) return;
        const activeSlide = slideData[activeIndex];
        viewport.style.backgroundImage = 
            `linear-gradient(rgba(0,0,0,0.1), ` +
            `rgba(0,0,0,0.7)), url('${activeSlide.img}')`;
        label.textContent = activeSlide.title;
        priceTag.textContent = activeSlide.price;

        viewport.onclick = function(e) {
            handleAddToCart(
                e, 
                activeSlide.title, 
                activeSlide.price, 
                activeSlide.img
            );
        };
        viewport.style.cursor = 'pointer';

        const tabs = 
            tabsRow.querySelectorAll('.carousel-tab-item');
        tabs.forEach((tab, i) => {
            if (i === activeIndex) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
    }

    function advanceSlide() {
        if(!slideData.length) return;
        activeIndex = (activeIndex + 1) % slideData.length;
        renderActiveSlide();
    }

    function jumpToSlide(targetIndex) {
        activeIndex = targetIndex;
        renderActiveSlide();
        clearInterval(slideTimer);
        startAutoplay(); 
    }

    function startAutoplay() {
        slideTimer = setInterval(advanceSlide, 4000);
    }

    const queryParams = 
        new URLSearchParams(window.location.search);
    const requestedGenre = queryParams.get('genre');
    const requestedPublisher = queryParams.get('publisher');
    const requestedTag = queryParams.get('tag');

    const genreLabels = { 
        action: 'Action Games', 
        strategy: 'Strategy Games', 
        'indie-rpg': 'Indie / RPG', 
        casual: 'Casual Games' 
    };
    const publisherLabels = { 
        stoker: 'Stoker Studios Inc.', 
        'wally-stark': 'Wally Stark Studios', 
        partner: 'Partner Studios' 
    };
    const tagLabels = { 
        coop: 'Co-op Multiplayer', 
        cyberpunk: 'Cyberpunk Neon', 
        retro: 'Retro Software Engine' 
    };

    function renderTabCatalog(categoryKey) {
        const gridTarget = 
            document.getElementById('catalog-tab-grid');
        const status = 
            document.getElementById('genre-filter-status');
        gridTarget.innerHTML = '';

        let activeList = 
            tabbedCatalogData[categoryKey] || [];
        const uniqueGames = 
            Object.values(tabbedCatalogData).flat()
            .filter((item, index, all) => 
                all.findIndex(other => 
                    other.title === item.title) === index
            );
        const filterParts = [];
        
        if (requestedGenre && genreLabels[requestedGenre]) {
            activeList = uniqueGames.filter(item => 
                (gameGenres[item.title] || [])
                .includes(requestedGenre)
            );
            filterParts.push(genreLabels[requestedGenre]);
        }
        if (
            requestedPublisher && 
            publisherLabels[requestedPublisher]
        ) {
            activeList = (
                requestedGenre && genreLabels[requestedGenre] ? 
                activeList : uniqueGames
            ).filter(item => 
                gamePublishers[item.title] === requestedPublisher
            );
            filterParts.push(publisherLabels[requestedPublisher]);
        }
        if (requestedTag && tagLabels[requestedTag]) {
            activeList = (
                filterParts.length ? activeList : uniqueGames
            ).filter(item => 
                (gameTags[item.title] || []).includes(requestedTag)
            );
            filterParts.push(tagLabels[requestedTag]);
        }

        if (filterParts.length) {
            status.innerHTML = 
                'Showing: <strong>' + 
                filterParts.join(' · ') + 
                '</strong> · <a href="Products.php' +
                '#catalog-tab-grid">Clear filters</a>';
        } else {
            status.textContent = 
                'Choose a filter option to sort.';
        }

        if (!activeList.length) {
            gridTarget.innerHTML = 
                '<p class="loco-empty-state">' +
                'No games match this choice.</p>';
            return;
        }

                activeList.forEach(item => {
            const cellWrapper = 
                document.createElement('a');
            cellWrapper.className = 
                'loco-mini-game-card';
            
            // 🌟 NO MASTERLIST TRAP: Pulls the real database ID directly!
            cellWrapper.href = 
                `game_details.php?id=${item.id}`;

            let htmlContent = "";
            htmlContent += `<img src="${item.img}" `;
            htmlContent += `class="loco-mini-thumb" `;
            htmlContent += `alt="${item.title}">`;
            htmlContent += `<div class="loco-mini-details">`;
            htmlContent += `<h5 class="loco-mini-title">`;
            htmlContent += `${item.title}</h5>`;
            htmlContent += `<span class="loco-mini-price">`;
            htmlContent += `${item.price}</span>`;
            htmlContent += `</div>`;

            cellWrapper.innerHTML = htmlContent;
            gridTarget.appendChild(cellWrapper);
        });
    }

    function switchCatalogTab(categoryKey) {
        document.querySelectorAll('.loco-tab-nav').forEach(tab => {
            tab.classList.toggle(
                'active',
                tab.dataset.catalogCategory === categoryKey
            );
        });
        renderTabCatalog(categoryKey);
    }

    buildCarouselControls();
    renderActiveSlide();
    if (slideData.length > 1) {
        startAutoplay();
    }
    renderTabCatalog('sellers');
</script> 
</body>
</html> 