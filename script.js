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
            this.style.transform = 'translateY(-10px)';
            this.style.boxShadow = '0 12px 30px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
        });
    });
    
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
                    
                    // Load the iframe src only when it's in viewport
                    if (iframe.dataset.src) {
                        iframe.src = iframe.dataset.src;
                        iframe.removeAttribute('data-src');
                    }
                    
                    // Stop observing once loaded
                    observer.unobserve(container);
                }
            });
        }, options);
        
        // Observe each iframe container
        iframeContainers.forEach(container => {
            observer.observe(container);
        });
    };
    
    // Initialize features that require additional setup
    // Uncomment these when needed
    // createMobileMenu();
    lazyLoadIframes();
    
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
            }
        });
    });

    // 添加页面滚动效果
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 50) {
            header.style.backgroundColor = 'rgba(51, 51, 51, 0.95)';
        } else {
            header.style.backgroundColor = '#333';
        }
    });
}); 