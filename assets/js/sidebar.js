// Modern Sidebar functionality for Gym Management System

document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('sidebar');
    const body = document.body;

    const isDesktop = () => window.innerWidth >= 1200;

    // Function to apply the correct classes based on state and screen size
    const applySidebarState = () => {
        if (isDesktop()) {
            // Desktop view
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        } else {
            // Mobile view
            if (body.classList.contains('sidebar-open')) {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
            } else {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        }
    };

    // Toggle sidebar state
    const toggleSidebar = () => {
        body.classList.toggle('sidebar-open');
        applySidebarState();
    };

    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    if (sidebarClose) {
        sidebarClose.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
            applySidebarState();
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', () => {
            body.classList.remove('sidebar-open');
            applySidebarState();
        });
    }

    // Adjust on window resize
    window.addEventListener('resize', applySidebarState);

    // Set initial state on page load
    if (isDesktop()) {
        body.classList.add('sidebar-open');
    } else {
        body.classList.remove('sidebar-open');
    }
    applySidebarState();
});