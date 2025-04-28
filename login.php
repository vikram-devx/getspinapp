<?php
// Handle both direct access and access via subdirectory (for pretty URLs)
$base_path = '';
if (!file_exists('includes/config.php') && file_exists('../includes/config.php')) {
    $base_path = '../';
}
require_once $base_path . 'includes/config.php';
require_once $base_path . 'includes/auth.php';
require_once $base_path . 'includes/functions.php';

$auth = new Auth();

// Get auth card logo from settings
$auth_card_logo = getSetting('auth_card_logo', '');

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    // Use direct file reference to avoid pretty URL redirection issues
    header('Location: login.php');
    exit;
}

// If user is already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    // Use pretty URL for redirect
    header('Location: dashboard');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'All fields are required';
    } else {
        // Login the user
        $result = $auth->login($username, $password);
        
        if ($result['status'] === 'success') {
            // Use pretty URL for redirect
            header('Location: dashboard');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

include $base_path . 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-6 offset-lg-3">
        <div class="card auth-form">
            <div class="card-body p-4">
                <div class="auth-header text-center mb-4">
                    <?php if (!empty($auth_card_logo)): ?>
                    <div class="mb-3">
                        <img src="<?php echo htmlspecialchars($auth_card_logo); ?>" alt="<?php echo htmlspecialchars($app_name); ?>" class="img-fluid auth-card-logo">
                    </div>
                    <?php elseif (!empty($app_logo)): ?>
                    <div class="mb-3">
                        <img src="<?php echo htmlspecialchars($app_logo); ?>" alt="<?php echo htmlspecialchars($app_name); ?>" class="img-fluid" style="max-height: 60px;">
                    </div>
                    <?php else: ?>
                    <h2><?php echo htmlspecialchars($app_name); ?></h2>
                    <?php endif; ?>
                    <h2>Welcome Back</h2>
                    <p class="text-muted">Login to access your account</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                    
                    <div class="text-center mb-3">
                        <a href="#" class="text-decoration-none">Forgot your password?</a>
                    </div>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="/register">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $base_path . 'includes/auth_footer.php'; ?>
