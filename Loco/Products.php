<?php
session_start();

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
                <a href="javascript:void(0);" onclick="handleAddToCart(event, 'NieR Replicant', '2,990.00', 'images/nierreplicant.webp')" class="loco-promo-card">
                    <img src="images/nierreplicant.webp" class="loco-promo-img" alt="Promo 1">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">NieR Replicant™ ver.1.22474487139...</span>
                        <span class="loco-promo-price">P2,990.00</span>
                    </div>
                </a>

                <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Saints Row IV: Re-Elected', '794.30', 'images/SaintsRowIV.jpg')" class="loco-promo-card">
                    <img src="images/SaintsRowIV.jpg" class="loco-promo-img" alt="Promo 2">
                    <div class="loco-promo-overlay">
                        <span class="loco-promo-title">Saints Row IV: Re-Elected</span>
                        <span class="loco-promo-price">P794.30</span>
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
                    <img src="images/Buckshot_Roulette_Cover.jpg" class="game-thumb" alt="Game 1">
                    <div class="game-details">
                        <h4 class="game-title">Buckshot Roulette</h4>
                        <p class="game-desc">Play Russian roulette with a 12-gauge shotgun. Four enter. One leaves. Roll the dice with your life. Good luck!</p>
                    </div>
                    <div class="game-purchase-bar">
                        <span class="game-price">₱109.95</span>
                        <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Buckshot Roulette', '109.95', 'images/Buckshot_Roulette_Cover.jpg')" class="game-buy-btn">BUY NOW</a>
                    </div>  
                </div>

                <div class="game-card">
                    <img src="images/deltarune.jpg" class="game-thumb" alt="Game 2">
                    <div class="game-details">
                        <h4 class="game-title">Deltarune</h4>
                        <p class="game-desc">Dive into the parallel story to UNDERTALE! Fight or spare your way through action-packed battles as you explore a mysterious world alongside an endearing cast of new and familiar characters.</p>
                    </div>
                    <div class="game-purchase-bar">
                        <span class="game-price">₱765.00</span>
                        <a href="javascript:void(0);" onclick="handleAddToCart(event, 'Deltarune', '765.00', 'images/deltarune.jpg')" class="game-buy-btn">BUY NOW</a>
                    </div>
                </div>
            </div>

            <div class="loco-tabbed-section">
                <div class="loco-tabs-header">
                    <button class="loco-tab-nav active" onclick="switchCatalogTab('sellers')">Top Sellers</button>
                    <button class="loco-tab-nav" onclick="switchCatalogTab('rated')">Top Rated</button>
                    <button class="loco-tab-nav" onclick="switchCatalogTab('releases')">New Releases</button>
                </div>
                <div class="loco-tab-content-grid" id="catalog-tab-grid"></div>
            </div>

        </div> 
        
    
        <div class="loco-store-sidebar-col">
            
            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Find More...</h3>
                <div class="sidebar-content-box">
                    <a href="#" class="sidebar-link">» Action Games</a>
                    <a href="#" class="sidebar-link">» Strategy Hub</a>
                    <a href="#" class="sidebar-link">» Indie / RPG</a>
                    <a href="#" class="sidebar-link">» Casual Pipeline</a>
                </div>
            </div>

            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Publisher Catalog</h3>
                <div class="sidebar-content-box">
                    <a href="#" class="sidebar-link">» Stoker Studios Inc.</a>
                    <a href="#" class="sidebar-link">» Wally Stark studios</a>
                    <a href="#" class="sidebar-link">» Partner API Portals</a>
                </div>
            </div>

            <div class="sidebar-panel">
                <h3 class="loco-label-retro">Browse Tags</h3>
                <div class="sidebar-content-box">
                    <a href="#" class="sidebar-link">» Co-op Multiplayer</a>
                    <a href="#" class="sidebar-link">» Cyberpunk Neon</a>
                    <a href="#" class="sidebar-link">» Retro Software Engine</a>
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
            {
                title: "Counter-Strike 2",
                price: "Free",
                img: "images/cs2.jpg"
            },
            {
                title: "Metal Gear Rising: Revengeance",
                price: "P569.00",
                img: "images/mgrr.jpg"
            },
            {
                title: "Metal Gear Solid 4: Guns Of The Patriots",
                price: "P2,300.00",
                img: "images/mgs4.avif"
            },
            {
                title: "Witcher 3: Wild Hunt - Songs of the Past",
                price: "P2,099.00",
                img: "images/witcher3.jpeg"
            }
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
                { title: "NieR Automata", price: "P2,990.00", img: "images/Nier_Automata_cover_art.jpg" },
                { title: "Yakuza 0", price: "P1,990.00", img: "images/yakuza0.jpg" },
                { title: "Half-Life Alyx", price: "P1,070.00", img: "images/Half-life_Alyx_Cover_Art.jpg" },
                { title: "Apex Legends", price: "Free", img: "images/apex.jpg" }
            ],
            rated: [
                { title: "DOTA 2", price: "Free", img: "images/dota2.jpg" },
                { title: "Left 4 Dead", price: "P335.00", img: "images/left4dead.webp" },
                { title: "Left 4 Dead 2", price: "P335.00", img: "images/Left4dead2cover.webp" },
                { title: "Half-Life 2", price: "P335.00", img: "images/halflife2.jpg" }
            ],
            releases: [
                { title: "Hades", price: "P765.00", img: "images/hades.jpg" },
                { title: "Hollow Knight: Silksong", price: "P615.00", img: "images/silksong.jpg" },
                { title: "Omori", price: "P449.95", img: "images/omori.jpg" },
                { title: "Doki Doki Literature Club Plus", price: "P565.95", img: "images/Doki_Doki_Literature_Club_Cover.jpg" }
            ]
        };

        function renderTabCatalog(categoryKey) {
            const gridTarget = document.getElementById('catalog-tab-grid');
            gridTarget.innerHTML = ''; 

            const activeList = tabbedCatalogData[categoryKey];
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
    </script>
    </script>
</body>
</html> 