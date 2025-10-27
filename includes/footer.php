<footer class="main-footer">
    <div class="container-fluid">
        <div class="row align-items-center py-3">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <p class="mb-0 text-muted">
                    <i class="fas fa-dumbbell me-2 text-gradient"></i>
                    <strong><?php echo SITE_NAME; ?></strong> - Your Fitness Journey Partner
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0 text-muted">
                    Built with <i class="fas fa-heart text-danger"></i> by 
                    <a href="https://www.piyushgurav.me" target="_blank" class="footer-link text-gradient" style="font-weight: 600;">Piyush</a>
                    <span class="mx-2">|</span>
                    &copy; <?php echo date('Y'); ?> All rights reserved
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
.main-footer {
    background: var(--bg-light);
    border-top: 2px solid var(--border-color);
    padding: 1rem 0;
    margin-top: 3rem;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
}

.main-footer .footer-link {
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
}

.main-footer .footer-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    transition: width 0.3s ease;
}

.main-footer .footer-link:hover::after {
    width: 100%;
}

.main-footer .text-gradient {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>