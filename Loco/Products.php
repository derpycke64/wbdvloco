<?php
session_start();
if (($_SESSION['loco_game_catalog_version'] ?? '') !== 'original-games-v2') {
    unset($_SESSION['cart']);
    $_SESSION['loco_game_catalog_version'] = 'original-games-v2';
}

if(!isset($_SESSION['username'])){
    header("Location: Login.php");
    exit();
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

    <nav class="loco-global-nav">
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
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="loco-logout-link">Logout</a>
            </div>
        </div>
    </nav>

     <div class="store-wrapper">
        <div class="loco-store-main-col">
            <div class="loco-install-banner">
                <a href="#" class="loco-install-btn">Get Loco Now !</a>
                <p class="loco-install-text">Join LOCO for Free and get games delivered straight to your desktop with automatic updates and a massive gaming community pipeline platform environment.</p>
            </div>

            <div class="loco-promo-row">
                <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Nebula Drift', '2490.00', 'images/gameplay_01.jpg')" class="loco-promo-card">
                    <img src="images/gameplay_01.jpg" class="loco-promo-img" alt="Nebula Drift original cover art">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">Nebula Drift</span>
                        <span class="loco-promo-price">₱2,490.00</span>
                    </div>
                </a>
                <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Crimson Freight', '899.00', 'images/gameplay_02.jpg')" class="loco-promo-card">
                    <img src="images/gameplay_02.jpg" class="loco-promo-img" alt="Crimson Freight original cover art">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">Crimson Freight</span>
                        <span class="loco-promo-price">₱899.00</span>
                    </div>
                </a>
            </div>
            
            <div class="store-hero" id="carousel-viewport"></div>
            <div class="carousel-control-bar">
                <div class="carousel-tabs" id="carousel-tabs-row"></div>    
                <span class="carousel-status-text" id="carousel-label">Loading...</span>
                <span class="carousel-price-tag" id="carousel-price">$0.00</span>
            </div>
            
            <h3 class="loco-label-thingy">Featured Games & Bargain Deals</h3>
            
            <div class="game-grid">
                <div class="game-card">
                    <img src="images/gameplay_01.jpg" class="game-thumb" alt="Nebula Drift original cover art">
                    <div class="game-details">
                        <h4 class="game-title">Nebula Drift</h4>
                        <p class="game-desc">Chart a course across luminous alien oceans and distant moons in an atmospheric cosmic exploration adventure.</p>
                    </div>
                    <div class="game-purchase-bar">
                        <span class="game-price">₱2,490.00</span>
                        <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Nebula Drift', '2490.00', 'images/gameplay_01.jpg')" class="game-buy-btn">BUY NOW</a>
                    </div>
                </div>
                <div class="game-card">
                    <img src="images/gameplay_06.jpg" class="game-thumb" alt="Neon Velocity original cover art">
                    <div class="game-details">
                        <h4 class="game-title">Neon Velocity</h4>
                        <p class="game-desc">Tear through a rain-lit megacity in a high-speed future where every turn can make you a legend.</p>
                    </div>
                    <div class="game-purchase-bar">
                        <span class="game-price">₱1,399.00</span>
                        <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Neon Velocity', '1399.00', 'images/gameplay_06.jpg')" class="game-buy-btn">BUY NOW</a>
                    </div>
                </div>
            </div>

            <div class="loco-tabbed-section">
                <div class="loco-tabs-header">
                    <button class="loco-tab-nav active" onclick="switchCatalogTab('sellers')">Top Sellers</button>
                    <button class="loco-tab-nav" onclick="switchCatalogTab('rated')">Top Rated</button>
                    <button class="loco-tab-nav" onclick="switchCatalogTab('releases')">New Releases</button>
                </div>
                <p id="genre-filter-status" class="loco-filter-status" aria-live="polite"></p>
                <div class="loco-tab-content-grid" id="catalog-tab-grid"></div>
            </div>

        </div> 
        
    
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
    <script>
        function handleAddToCart(event, gameTitle, gamePrice, gameThumb) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const addConfirm = confirm(`Would you like to add "${gameTitle}" to your shopping cart?`);
            
            if (!addConfirm) {
                return; 
            }
            const sanitizedPrice = gamePrice.replace(/[₱P,]/g, '').trim();
            const targetUrl = `payment.php?game=${encodeURIComponent(gameTitle)}&price=${encodeURIComponent(sanitizedPrice)}&thumb=${encodeURIComponent(gameThumb)}`;
            const goToCheckOut = confirm(`"${gameTitle}" has been successfully reserved!\n\nClick 'OK' to go straight to your shopping cart checkout layout.\nClick 'Cancel' to continue shopping and add more items!`);

            if (goToCheckOut) {
                window.location.href = targetUrl;
            } else {
                fetch(targetUrl)
                    .then(() => {
                        alert(`"${gameTitle}" successfully added to your cart columns behind the scenes! You can keep browsing.`);
                    })
                    .catch(err => {
                        console.error("Cart registration error:", err);
                        const hiddenIframe = document.createElement('iframe');
                        hiddenIframe.style.display = 'none';
                        hiddenIframe.src = targetUrl;
                        document.body.appendChild(hiddenIframe);
                        setTimeout(() => { document.body.removeChild(hiddenIframe); }, 500);
                    });
            }
        }

        const slideData = [
            { title: "Nebula Drift", price: "₱2,490.00", img: "images/gameplay_01.jpg" },
            { title: "Crimson Freight", price: "₱899.00", img: "images/gameplay_02.jpg" },
            { title: "Ashen Crown", price: "₱1,299.00", img: "images/gameplay_08.jpg" },
            { title: "Starfall Cartographer", price: "₱899.00", img: "images/gameplay_12.jpg" }
        ];

        let activeIndex = 0;
        let slideTimer;

        const viewport = document.getElementById('carousel-viewport');
        const tabsRow = document.getElementById('carousel-tabs-row');
        const label = document.getElementById('carousel-label');
        const priceTag = document.getElementById('carousel-price');

        function buildCarouselControls() {
            tabsRow.innerHTML = '';
            slideData.forEach((slide, index) => {
                const tab = document.createElement('div');
                tab.className = 'carousel-tab-item';
                if (index === activeIndex) tab.classList.add('active');
                tab.addEventListener('click', () => { jumpToSlide(index); });
                tabsRow.appendChild(tab);
            });
        }

        function renderActiveSlide() {
            const activeSlide = slideData[activeIndex];
            viewport.style.backgroundImage = `linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.7)), url('${activeSlide.img}')`;
            label.textContent = activeSlide.title;
            priceTag.textContent = activeSlide.price;

            viewport.onclick = function(e) {
                handleAddToCart(e, activeSlide.title, activeSlide.price, activeSlide.img);
            };
            viewport.style.cursor = 'pointer';

            const tabs = tabsRow.querySelectorAll('.carousel-tab-item');
            tabs.forEach((tab, i) => {
                if (i === activeIndex) tab.classList.add('active');
                else tab.classList.remove('active');
            });
        }

        function advanceSlide() {
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

        const tabbedCatalogData = {
            sellers: [
                { title: "Nebula Drift", price: "₱2,490.00", img: "images/gameplay_01.jpg" },
                { title: "Crimson Freight", price: "₱899.00", img: "images/gameplay_02.jpg" },
                { title: "Moonroot Hollow", price: "₱699.00", img: "images/gameplay_03.jpg" },
                { title: "Abyss Protocol", price: "₱1,299.00", img: "images/gameplay_04.jpg" },
                { title: "Stormbanner Tactics", price: "₱1,150.00", img: "images/gameplay_05.jpg" },
                { title: "Neon Velocity", price: "₱1,399.00", img: "images/gameplay_06.jpg" }
            ],
            rated: [
                { title: "Clockwork Reverie", price: "₱599.00", img: "images/gameplay_07.jpg" },
                { title: "Ashen Crown", price: "₱1,299.00", img: "images/gameplay_08.jpg" },
                { title: "Skydrift Homestead", price: "₱799.00", img: "images/gameplay_09.jpg" },
                { title: "The Last Bell", price: "₱499.00", img: "images/gameplay_10.jpg" },
                { title: "Emberline Chronicles", price: "₱1,499.00", img: "images/gameplay_11.jpg" },
                { title: "Starfall Cartographer", price: "₱899.00", img: "images/gameplay_12.jpg" }
            ],
            releases: [
                { title: "Riftbound Arena", price: "Free", img: "images/gameplay_13.jpg" },
                { title: "Lanterns of Luma", price: "₱649.00", img: "images/gameplay_14.jpg" },
                { title: "Iron Orchard", price: "₱1,099.00", img: "images/gameplay_15.jpg" },
                { title: "Echoes Below", price: "₱999.00", img: "images/gameplay_16.jpg" },
                { title: "Glass Harbor", price: "₱549.00", img: "images/gameplay_17.jpg" },
                { title: "Wild Circuit", price: "₱1,199.00", img: "images/gameplay_18.jpg" }
            ]
        };

        const gameGenres = {
            "Nebula Drift": ["action"],
            "Crimson Freight": ["action", "strategy"],
            "Moonroot Hollow": ["indie-rpg", "casual"],
            "Abyss Protocol": ["action"],
            "Stormbanner Tactics": ["strategy"],
            "Neon Velocity": ["action"],
            "Clockwork Reverie": ["casual"],
            "Ashen Crown": ["action", "indie-rpg"],
            "Skydrift Homestead": ["casual"],
            "The Last Bell": ["indie-rpg", "casual"],
            "Emberline Chronicles": ["indie-rpg"],
            "Starfall Cartographer": ["strategy", "indie-rpg"],
            "Riftbound Arena": ["action"],
            "Lanterns of Luma": ["indie-rpg", "casual"],
            "Iron Orchard": ["strategy"],
            "Echoes Below": ["action"],
            "Glass Harbor": ["indie-rpg", "casual"],
            "Wild Circuit": ["action"]
        };
        const queryParams = new URLSearchParams(window.location.search);
        const requestedGenre = queryParams.get('genre');
        const requestedPublisher = queryParams.get('publisher');
        const requestedTag = queryParams.get('tag');
        const genreLabels = { action: 'Action Games', strategy: 'Strategy Games', 'indie-rpg': 'Indie / RPG', casual: 'Casual Games' };
        const publisherLabels = { stoker: 'Stoker Studios Inc.', 'wally-stark': 'Wally Stark Studios', partner: 'Partner Studios' };
        const tagLabels = { coop: 'Co-op Multiplayer', cyberpunk: 'Cyberpunk Neon', retro: 'Retro Software Engine' };
        const gamePublishers = {
            'Nebula Drift': 'stoker', 'Crimson Freight': 'wally-stark', 'Moonroot Hollow': 'partner',
            'Abyss Protocol': 'stoker', 'Stormbanner Tactics': 'wally-stark', 'Neon Velocity': 'partner',
            'Clockwork Reverie': 'stoker', 'Ashen Crown': 'wally-stark', 'Skydrift Homestead': 'partner',
            'The Last Bell': 'stoker', 'Emberline Chronicles': 'wally-stark', 'Starfall Cartographer': 'partner',
            'Riftbound Arena': 'stoker', 'Lanterns of Luma': 'partner', 'Iron Orchard': 'wally-stark',
            'Echoes Below': 'stoker', 'Glass Harbor': 'partner', 'Wild Circuit': 'wally-stark'
        };
        const gameTags = {
            'Nebula Drift': ['coop'], 'Crimson Freight': ['coop', 'retro'], 'Moonroot Hollow': ['coop', 'retro'],
            'Abyss Protocol': ['coop'], 'Stormbanner Tactics': ['retro'], 'Neon Velocity': ['cyberpunk'],
            'Clockwork Reverie': ['retro'], 'Ashen Crown': ['coop', 'retro'], 'Skydrift Homestead': ['coop'],
            'The Last Bell': ['retro'], 'Emberline Chronicles': ['coop'], 'Starfall Cartographer': ['retro'],
            'Riftbound Arena': ['coop', 'cyberpunk'], 'Lanterns of Luma': ['retro'], 'Iron Orchard': ['retro'],
            'Echoes Below': ['coop'], 'Glass Harbor': ['retro'], 'Wild Circuit': ['cyberpunk']
        };

        function renderTabCatalog(categoryKey) {
            const gridTarget = document.getElementById('catalog-tab-grid');
            const status = document.getElementById('genre-filter-status');
            gridTarget.innerHTML = '';

            let activeList = tabbedCatalogData[categoryKey] || [];
            const uniqueGames = Object.values(tabbedCatalogData).flat()
                .filter((item, index, all) => all.findIndex(other => other.title === item.title) === index);
            const filterParts = [];
            if (requestedGenre && genreLabels[requestedGenre]) {
                activeList = uniqueGames.filter(item => (gameGenres[item.title] || []).includes(requestedGenre));
                filterParts.push(genreLabels[requestedGenre]);
            }
            if (requestedPublisher && publisherLabels[requestedPublisher]) {
                activeList = (requestedGenre && genreLabels[requestedGenre] ? activeList : uniqueGames)
                    .filter(item => gamePublishers[item.title] === requestedPublisher);
                filterParts.push(publisherLabels[requestedPublisher]);
            }
            if (requestedTag && tagLabels[requestedTag]) {
                activeList = (filterParts.length ? activeList : uniqueGames)
                    .filter(item => (gameTags[item.title] || []).includes(requestedTag));
                filterParts.push(tagLabels[requestedTag]);
            }
            if (filterParts.length) {
                status.innerHTML = 'Showing: <strong>' + filterParts.join(' · ') + '</strong> · <a href="Products.php#catalog-tab-grid">Clear filters</a>';
            } else {
                status.textContent = 'Choose a genre, publisher, or tag to filter the catalog.';
            }

            if (!activeList.length) {
                gridTarget.innerHTML = '<p class="loco-empty-state">No games are listed in this genre yet.</p>';
                return;
            }
            activeList.forEach(item => {
                const cellWrapper = document.createElement('a');
                cellWrapper.className = 'loco-mini-game-card';
                cellWrapper.href = 'javascript:void(0);'; 
                
                cellWrapper.onclick = function(e) {
                    handleAddToCart(e, item.title, item.price, item.img);
                };
                
                cellWrapper.innerHTML = `
                    <img src="${item.img}" class="loco-mini-thumb" alt="${item.title}">
                    <div class="loco-mini-details">
                        <h5 class="loco-mini-title">${item.title}</h5>
                        <span class="loco-mini-price">${item.price}</span>
                    </div>
                `;
                gridTarget.appendChild(cellWrapper);
            });
        }

        function switchCatalogTab(categoryKey) {
            const buttons = document.querySelectorAll('.loco-tab-nav');
            buttons.forEach(btn => {
                if (btn.getAttribute('onclick').includes(categoryKey)) btn.classList.add('active');
                else btn.classList.remove('active');
            });
            renderTabCatalog(categoryKey);
        }

        buildCarouselControls();
        renderActiveSlide();
        startAutoplay();
        renderTabCatalog('sellers');
        if ((requestedGenre && genreLabels[requestedGenre]) || (requestedPublisher && publisherLabels[requestedPublisher]) || (requestedTag && tagLabels[requestedTag])) {
            document.querySelectorAll('.loco-tab-nav').forEach(btn => btn.classList.remove('active'));
            document.getElementById('catalog-tab-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    </script>
</body>
</html> 