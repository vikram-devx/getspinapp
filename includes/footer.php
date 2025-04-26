    </div>
    
    <?php if (isset($auth) && $auth->isLoggedIn()): ?>
    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav d-lg-none fixed-bottom">
        <div class="mobile-bottom-nav-item">
            <a href="<?php echo url('dashboard'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        </div>
        <div class="mobile-bottom-nav-item">
            <a href="<?php echo url('tasks'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                <i class="fas fa-tasks"></i>
                <span>Tasks</span>
            </a>
        </div>
        <div class="mobile-bottom-nav-item">
            <a href="<?php echo url('rewards'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i>
                <span>Rewards</span>
            </a>
        </div>
        <div class="mobile-bottom-nav-item">
            <a href="<?php echo url('leaderboard'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-trophy"></i>
                <span>Leaderboard</span>
            </a>
        </div>
        <div class="mobile-bottom-nav-item">
            <a href="<?php echo url('referrals'); ?>" class="<?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i>
                <span>Invite</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-muted">Terms of Service</a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted">Privacy Policy</a></li>
                        <li class="list-inline-item"><a href="#" class="text-muted">Contact Us</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- EmailJS Configuration -->
    <script src="assets/js/emailjs-config.js"></script>
    
    <!-- Error handling script to prevent errors from non-existent scripts -->
    <script>
    // Protect against errors from missing scripts or elements
    window.addEventListener('error', function(e) {
        // Only log the error, don't display it to users
        console.warn('Captured error:', e.message);
        // Prevent the error from propagating
        e.preventDefault();
        return true;
    }, true);
    </script>
</body>
</html>
