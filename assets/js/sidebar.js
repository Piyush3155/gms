// Modern Sidebar functionality for Gym Management System

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const body = document.body;

    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
        
        // Add body class for desktop layout
        if (window.innerWidth >= 1200) {
            body.classList.toggle('sidebar-open');
        }
    }

    // Event listeners for toggle buttons
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth < 1200) {
            body.classList.remove('sidebar-open');
        } else if (sidebar.classList.contains('show')) {
            body.classList.add('sidebar-open');
        }
    });

    // Close sidebar on navigation (mobile only)
    const menuLinks = document.querySelectorAll('.menu-link:not([data-bs-toggle]), .submenu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1200) {
                setTimeout(toggleSidebar, 150);
            }
        });
    });

    // Handle submenu toggle with smooth animation
    const submenuToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            if (target) {
                // Close other submenus
                const allSubmenus = document.querySelectorAll('.submenu');
                allSubmenus.forEach(submenu => {
                    if (submenu !== target && submenu.classList.contains('show')) {
                        submenu.classList.remove('show');
                        const otherToggle = document.querySelector(`[data-bs-target="#${submenu.id}"]`);
                        if (otherToggle) {
                            otherToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                
                // Toggle current submenu
                target.classList.toggle('show');
                this.setAttribute('aria-expanded', target.classList.contains('show'));
            }
        });
    });

    // Highlight active menu item
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop();
    
    // Check all menu links
    const allMenuLinks = document.querySelectorAll('.menu-link, .submenu-link');
    allMenuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href === currentPage || href.includes(currentPage))) {
            link.classList.add('active');
            
            // If it's a submenu link, expand its parent
            if (link.classList.contains('submenu-link')) {
                const submenu = link.closest('.submenu');
                if (submenu) {
                    submenu.classList.add('show');
                    const parentToggle = document.querySelector(`[data-bs-target="#${submenu.id}"]`);
                    if (parentToggle) {
                        parentToggle.setAttribute('aria-expanded', 'true');
                        parentToggle.classList.add('active');
                    }
                }
            }
        }
    });

    // Search functionality (placeholder for future implementation)
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        searchInput.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });

        searchInput.addEventListener('input', function() {
            // Implement search functionality here
            console.log('Searching for:', this.value);
        });
    }

    // Notification click handler (placeholder)
    const notificationBtn = document.querySelector('.notification-item .action-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // Implement notification panel here
            console.log('Notifications clicked');
        });
    }

    // Add smooth scroll behavior to all menu links
    const smoothScrollLinks = document.querySelectorAll('.menu-link[href^="#"], .submenu-link[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Add animation to menu items on load
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 50 * index);
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // ESC key to close sidebar
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });

    // Touch swipe to close sidebar (mobile)
    let touchStartX = 0;
    let touchEndX = 0;

    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });

    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        if (touchStartX - touchEndX > 50) {
            // Swipe left - close sidebar
            if (sidebar.classList.contains('show')) {
                toggleSidebar();
            }
        }
    }
});