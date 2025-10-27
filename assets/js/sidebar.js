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

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('headerSearch');
    const searchResults = document.getElementById('searchResults');
    const siteUrl = '<?php echo SITE_URL; ?>'; // Make sure this is defined in a global scope or passed correctly

    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            fetch(`${siteUrl}api/search.php?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    let resultsHtml = '';
                    const hasResults = data.members.length > 0 || data.plans.length > 0 || data.trainers.length > 0;

                    if (hasResults) {
                        if (data.members.length > 0) {
                            resultsHtml += '<div class="search-result-category">Members</div>';
                            data.members.forEach(item => {
                                resultsHtml += `
                                    <a href="${item.url}" class="search-result-item">
                                        <div class="result-title">${item.name}</div>
                                        <div class="result-meta">${item.email}</div>
                                    </a>
                                `;
                            });
                        }
                        if (data.plans.length > 0) {
                            resultsHtml += '<div class="search-result-category">Plans</div>';
                            data.plans.forEach(item => {
                                resultsHtml += `
                                    <a href="${item.url}" class="search-result-item">
                                        <div class="result-title">${item.name}</div>
                                        <div class="result-meta">Duration: ${item.duration} days | Price: ${item.price}</div>
                                    </a>
                                `;
                            });
                        }
                        if (data.trainers.length > 0) {
                            resultsHtml += '<div class="search-result-category">Trainers</div>';
                            data.trainers.forEach(item => {
                                resultsHtml += `
                                    <a href="${item.url}" class="search-result-item">
                                        <div class="result-title">${item.name}</div>
                                        <div class="result-meta">${item.specialization}</div>
                                    </a>
                                `;
                            });
                        }
                    } else {
                        resultsHtml = '<div class="no-results">No results found</div>';
                    }

                    searchResults.innerHTML = resultsHtml;
                    searchResults.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                    searchResults.style.display = 'none';
                });
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
});