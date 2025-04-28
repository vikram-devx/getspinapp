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

// If user is already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    header('Location: ' . url('dashboard', [], true));
    exit;
}

$error = '';
$success = '';

// Get referral code from URL if present
$referral_code = isset($_GET['ref']) ? trim($_GET['ref']) : '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $referral_code = isset($_POST['referral_code']) ? trim($_POST['referral_code']) : '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = 'Username must be between 3 and 20 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Register the user with referral code if provided
        $result = $auth->register($username, $email, $password, $referral_code);
        
        if ($result['status'] === 'success') {
            $success = 'Registration successful! You can now <a href="/login">login</a>.';
            
            // Add additional message if they used a referral code
            if (!empty($referral_code)) {
                $success .= '<br>You registered with a referral code!';
            }
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
                    <h2>Create an Account</h2>
                    <p class="text-muted">Fill out the form below to register</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else: ?>
                
                <form id="registerForm" method="post" action="/register">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div id="password-error" class="invalid-feedback" style="display: none;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="mb-4">
                        <label for="referral_code" class="form-label">Referral Code (Optional)</label>
                        <input type="text" class="form-control" id="referral_code" name="referral_code" value="<?php echo htmlspecialchars($referral_code); ?>">
                        <div class="form-text">If you were referred by a friend, enter their code here</div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                    </div>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="/login">Login</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include $base_path . 'includes/auth_footer.php'; ?>
