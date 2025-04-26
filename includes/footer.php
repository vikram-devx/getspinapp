    </div>
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
