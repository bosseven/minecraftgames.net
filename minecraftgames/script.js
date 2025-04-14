// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            window.scrollTo({
                top: targetElement.offsetTop - 70, // Subtract header height
                behavior: 'smooth'
            });
        });
    });
    
    // Game cards hover effect enhancement
    const gameCards = document.querySelectorAll('.game-card');
    
    gameCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            playMinecraftSound('hover');
        });
        
        card.addEventListener('click', function() {
            playMinecraftSound('click');
        });
    });
    
    // Handle game cover clicks
    const handleGameCoverClicks = () => {
        const minecraftCovers = document.querySelectorAll('.minecraft-cover');
        
        minecraftCovers.forEach(cover => {
            cover.addEventListener('click', function() {
                const container = this.closest('.game-iframe-container');
                const iframe = container.querySelector('.game-iframe');
                
                // Load iframe if it has a data-src attribute
                if (iframe && iframe.dataset.src) {
                    iframe.src = iframe.dataset.src;
                    iframe.classList.add('loaded');
                    iframe.removeAttribute('data-src');
                    
                    // Play Minecraft sound
                    playMinecraftSound('place');
                }
                
                // Hide cover
                this.style.display = 'none';
            });
        });
    };
    
    // Mobile menu toggle (if needed in the future)
    const createMobileMenu = () => {
        const header = document.querySelector('header');
        const nav = document.querySelector('nav');
        
        const mobileMenuButton = document.createElement('button');
        mobileMenuButton.classList.add('mobile-menu-button');
        mobileMenuButton.innerHTML = '<span></span><span></span><span></span>';
        
        header.insertBefore(mobileMenuButton, nav);
        
        mobileMenuButton.addEventListener('click', function() {
            nav.classList.toggle('active');
            this.classList.toggle('active');
            playMinecraftSound('click');
        });
    };
    
    // Lazy loading for iframes to improve performance
    const lazyLoadIframes = () => {
        const iframeContainers = document.querySelectorAll('.game-iframe-container');
        
        // Options for the Intersection Observer
        const options = {
            root: null, // Use the viewport as the root
            rootMargin: '0px',
            threshold: 0.1 // Trigger when 10% of the element is visible
        };
        
        // Create an observer
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const container = entry.target;
                    const iframe = container.querySelector('iframe');
                    
                    // Only load iframe if clicked, we don't auto-load them
                    // We just stop observing when it becomes visible
                    observer.unobserve(container);
                }
            });
        }, options);
        
        // Observe each iframe container
        iframeContainers.forEach(container => {
            observer.observe(container);
        });
    };
    
    // Check if game cover images exist and preload them
    const preloadGameCoverImages = () => {
        const coverTypes = ['classic', 'townscaper', 'krunker', 'sandspiel', 
                           'shellshockers', 'skribbl', 'alchemy', 'noclip', 'powder'];
        
        coverTypes.forEach(type => {
            const img = new Image();
            img.src = `./images/${type}-cover.jpg`;
            
            // Handle missing images by using the default cover
            img.onerror = function() {
                console.log(`Warning: Cover image for ${type} not found, using default.`);
                const covers = document.querySelectorAll(`.minecraft-cover.${type}`);
                covers.forEach(cover => {
                    cover.style.backgroundImage = "url('./images/minecraft-cover.jpg')";
                });
            };
        });
    };
    
    // Minecraft sound effects
    const playMinecraftSound = (type) => {
        const sounds = {
            click: 'https://minecraft-sounds.com/sounds/random/click.ogg',
            hover: 'https://minecraft-sounds.com/sounds/random/hover.ogg',
            place: 'https://minecraft-sounds.com/sounds/block/stone/place.ogg',
            dig: 'https://minecraft-sounds.com/sounds/block/grass/dig.ogg',
            walk: 'https://minecraft-sounds.com/sounds/block/grass/step.ogg'
        };
        
        // Create audio element
        if (sounds[type]) {
            const audio = new Audio(sounds[type]);
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Sound play prevented by browser policy.'));
        }
    };
    
    // Create floating Minecraft blocks in the background
    const createMinecraftBlocks = () => {
        const blockTypes = ['dirt-block', 'grass-block', 'stone-block'];
        const container = document.createElement('div');
        container.className = 'minecraft-blocks';
        document.body.appendChild(container);
        
        // Create 15 random blocks
        for (let i = 0; i < 15; i++) {
            const block = document.createElement('div');
            const randomType = blockTypes[Math.floor(Math.random() * blockTypes.length)];
            
            block.className = `minecraft-block ${randomType}`;
            block.style.left = `${Math.random() * 100}vw`;
            block.style.animationDelay = `${Math.random() * 15}s`;
            block.style.animationDuration = `${15 + Math.random() * 30}s`;
            block.style.transform = `scale(${0.5 + Math.random() * 1})`;
            
            container.appendChild(block);
        }
    };
    
    // Create click effect
    const createClickEffect = () => {
        document.addEventListener('click', (e) => {
            const effect = document.createElement('div');
            effect.className = 'click-effect';
            effect.style.left = `${e.clientX - 15}px`;
            effect.style.top = `${e.clientY - 15}px`;
            document.body.appendChild(effect);
            
            // Remove element after animation completes
            setTimeout(() => {
                effect.remove();
            }, 500);
        });
    };
    
    // Day-night cycle
    const createDayNightCycle = () => {
        const overlay = document.createElement('div');
        overlay.className = 'day-night-overlay';
        document.body.appendChild(overlay);
        
        let isDaytime = true;
        
        // Toggle day/night every 30 seconds
        setInterval(() => {
            isDaytime = !isDaytime;
            overlay.style.opacity = isDaytime ? 0 : 0.5;
        }, 30000);
    };
    
    // Handle game iframe container clicks for all games
    const handleAllGameClicks = () => {
        const gameCards = document.querySelectorAll('.game-card');
        
        gameCards.forEach(card => {
            const container = card.querySelector('.game-iframe-container');
            const iframe = container.querySelector('.game-iframe');
            const cover = container.querySelector('.minecraft-cover');
            
            container.addEventListener('click', function() {
                // If there's no cover (already clicked) or if we just clicked the cover, load the iframe
                if (!cover || cover.style.display === 'none') {
                    if (iframe && iframe.dataset.src && !iframe.src) {
                        iframe.src = iframe.dataset.src;
                        iframe.classList.add('loaded');
                        iframe.removeAttribute('data-src');
                        
                        // Play Minecraft sound
                        playMinecraftSound('place');
                    }
                }
            });
        });
    };
    
    // Handle iframe load errors due to X-Frame-Options
    const handleIframeLoadErrors = () => {
        document.querySelectorAll('.game-iframe').forEach(iframe => {
            // Handle load errors
            iframe.addEventListener('error', handleIframeError);
            
            // Check if load was successful
            iframe.addEventListener('load', function() {
                try {
                    // Try to access iframe content - if blocked by cookie settings, this will fail
                    const iframeContent = this.contentWindow || this.contentDocument;
                    if (!iframeContent) {
                        handleIframeError.call(this);
                    }
                } catch (e) {
                    // If we get a security error, it's likely due to cookie blocking
                    handleIframeError.call(this, e);
                }
            });
        });
        
        function handleIframeError(error) {
            // Create a message element if it doesn't exist
            const container = this.closest('.game-iframe-container');
            let message = container.querySelector('.frame-blocked-message');
            
            if (!message) {
                message = document.createElement('div');
                message.className = 'frame-blocked-message';
                
                // Check if this is likely a third-party cookie issue
                if (error && error.message && error.message.includes('cookie')) {
                    message.innerHTML = `
                        <h4>Third-Party Cookie Restriction</h4>
                        <p>Your browser is blocking third-party cookies needed for this game.</p>
                        <p>Please click the button below to play the game directly.</p>
                    `;
                } else {
                    message.innerHTML = `
                        <h4>This game restricts embedding.</h4>
                        <p>Please click the button below to play the game directly.</p>
                    `;
                }
                
                container.appendChild(message);
            }
            
            // Show the message
            message.style.display = 'flex';
            
            // Hide the iframe
            this.style.display = 'none';
        }
    };
    
    // Handle cookie consent
    const handleCookieConsent = () => {
        const cookieBanner = document.getElementById('cookie-consent-banner');
        const acceptButton = document.getElementById('accept-cookies');
        const rejectButton = document.getElementById('reject-cookies');
        
        // Check if user has already made a choice
        const cookieChoice = localStorage.getItem('cookieChoice');
        
        if (cookieChoice) {
            cookieBanner.classList.add('hidden');
        }
        
        acceptButton.addEventListener('click', () => {
            localStorage.setItem('cookieChoice', 'accepted');
            cookieBanner.classList.add('hidden');
            
            // Enable tracking/cookies if needed
            if (window.dataLayer && window.gtag) {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted',
                    'ad_storage': 'granted'
                });
            }
        });
        
        rejectButton.addEventListener('click', () => {
            localStorage.setItem('cookieChoice', 'rejected');
            cookieBanner.classList.add('hidden');
            
            // Disable tracking/cookies if needed
            if (window.dataLayer && window.gtag) {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied'
                });
            }
        });
    };
    
    // Initialize Minecraft features
    createMinecraftBlocks();
    createClickEffect();
    createDayNightCycle();
    
    // Initialize other features
    handleGameCoverClicks();
    handleAllGameClicks();
    // Uncomment when needed
    // createMobileMenu();
    lazyLoadIframes();
    preloadGameCoverImages();
    handleIframeLoadErrors();
    handleCookieConsent();
    
    // Update copyright year automatically
    const updateCopyrightYear = () => {
        const copyrightElement = document.querySelector('.copyright');
        if (copyrightElement) {
            const currentYear = new Date().getFullYear();
            copyrightElement.innerHTML = copyrightElement.innerHTML.replace(/\d{4}/, currentYear);
        }
    };
    
    updateCopyrightYear();

    // 平滑滚动
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
                
                // Play Minecraft sound
                playMinecraftSound('click');
            }
        });
    });

    // 添加页面滚动效果
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 50) {
            header.style.backgroundColor = 'rgba(139, 90, 43, 0.95)';
        } else {
            header.style.backgroundColor = 'var(--minecraft-dirt)';
        }
    });
    
    // Create pickaxe cursor on button hover
    const buttons = document.querySelectorAll('.play-button, button, .btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            document.body.style.cursor = "url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"32\" height=\"32\" viewBox=\"0 0 32 32\"><path fill=\"%23A0522D\" d=\"M16,6 L20,2 L24,6 L22,8 L26,12 L22,16 L18,12 L16,14 Z\"/><path fill=\"%23555555\" d=\"M16,14 L8,22 L12,26 L20,18 L18,16 Z\"/></svg>'), auto";
        });
        
        button.addEventListener('mouseleave', function() {
            document.body.style.cursor = '';
        });
        
        button.addEventListener('click', function() {
            playMinecraftSound('click');
        });
    });
    
    // Minecraft breaking animation on logo click
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.addEventListener('click', function() {
            this.classList.add('break-animation');
            playMinecraftSound('dig');
            
            setTimeout(() => {
                this.classList.remove('break-animation');
            }, 1000);
        });
    }
}); 