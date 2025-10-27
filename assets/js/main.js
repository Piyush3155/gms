// Main javascript file for GMS

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // HEADER SEARCH FUNCTIONALITY
    // ==========================================
    const searchInput = document.getElementById('headerSearch');
    const searchResults = document.getElementById('searchResults');
    const mainScript = document.getElementById('main-script');
    
    if (!mainScript) {
        console.error('Main script tag with site URL not found.');
        return;
    }
    const siteUrl = mainScript.getAttribute('data-site-url');

    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            // Clear previous timeout
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Debounce search requests
            searchTimeout = setTimeout(() => {
                fetch(`${siteUrl}api/search.php?query=${encodeURIComponent(query)}`)
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
                                            <div class="result-title"><i class="fas fa-user me-2"></i>${item.name}</div>
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
                                            <div class="result-title"><i class="fas fa-dumbbell me-2"></i>${item.name}</div>
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
                                            <div class="result-title"><i class="fas fa-user-tie me-2"></i>${item.name}</div>
                                            <div class="result-meta">${item.specialization}</div>
                                        </a>
                                    `;
                                });
                            }
                        } else {
                            resultsHtml = '<div class="no-results"><i class="fas fa-search me-2"></i>No results found</div>';
                        }

                        searchResults.innerHTML = resultsHtml;
                        searchResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                        searchResults.style.display = 'none';
                    });
            }, 300);
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Show results when input is focused and has value
        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 2) {
                searchResults.style.display = 'block';
            }
        });
    }

    // ==========================================
    // SMOOTH SCROLL TO TOP
    // ==========================================
    const createScrollToTop = () => {
        const scrollBtn = document.createElement('button');
        scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollBtn.className = 'scroll-to-top';
        scrollBtn.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        `;
        
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollBtn.style.display = 'flex';
            } else {
                scrollBtn.style.display = 'none';
            }
        });

        document.body.appendChild(scrollBtn);
    };

    createScrollToTop();

    // ==========================================
    // FORM VALIDATION ENHANCEMENT
    // ==========================================
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ==========================================
    // TOOLTIP INITIALIZATION
    // ==========================================
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ==========================================
    // ANIMATION ON SCROLL
    // ==========================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in, .slide-up').forEach(el => {
        observer.observe(el);
    });

    // ==========================================
    // CONFIRM DELETE DIALOGS
    // ==========================================
    document.querySelectorAll('[data-confirm-delete]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ==========================================
    // AUTO-HIDE ALERTS
    // ==========================================
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // ==========================================
    // TABLE SEARCH FILTER
    // ==========================================
    const tableSearchInputs = document.querySelectorAll('[data-table-search]');
    tableSearchInputs.forEach(input => {
        const targetTable = document.querySelector(input.dataset.tableSearch);
        if (targetTable) {
            input.addEventListener('keyup', function () {
                const filter = this.value.toLowerCase();
                const rows = targetTable.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });
        }
    });

    // ==========================================
    // LOADING STATES FOR BUTTONS
    // ==========================================
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);
            }
        });
    });

    // ==========================================
    // NUMBER COUNTER ANIMATION
    // ==========================================
    const animateValue = (element, start, end, duration) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            element.textContent = value.toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    // Animate stats numbers
    document.querySelectorAll('.stats-card h2, .kpi-value').forEach(el => {
        const value = parseInt(el.textContent.replace(/[^0-9]/g, ''));
        if (value && !isNaN(value)) {
            el.textContent = '0';
            setTimeout(() => {
                animateValue(el, 0, value, 1500);
            }, 300);
        }
    });

    // ==========================================
    // COPY TO CLIPBOARD FUNCTIONALITY
    // ==========================================
    document.querySelectorAll('[data-copy-text]').forEach(btn => {
        btn.addEventListener('click', function () {
            const text = this.dataset.copyText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });

    // ==========================================
    // PRINT FUNCTIONALITY
    // ==========================================
    document.querySelectorAll('[data-print]').forEach(btn => {
        btn.addEventListener('click', function () {
            window.print();
        });
    });
});

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

// Format currency
function formatCurrency(amount, currency = '₹') {
    return currency + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format date
function formatDate(date, format = 'short') {
    const d = new Date(date);
    const options = format === 'short' 
        ? { year: 'numeric', month: 'short', day: 'numeric' }
        : { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return d.toLocaleDateString('en-IN', options);
}

// Show notification
function showNotification(message, type = 'success') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        alert(message);
    }
}

// Confirm action
function confirmAction(message = 'Are you sure?') {
    return confirm(message);
}
