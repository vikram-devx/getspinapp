<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$auth = new Auth();
$current_user = $auth->getUser();

// Determine if this is a public page (no logged-in user required)
$current_page = basename($_SERVER['PHP_SELF']);
// Also check for pretty URLs (without .php extension)
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);
$request_path = trim($request_path, '/');
$is_public_page = !$auth->isLoggedIn() || 
                  in_array($current_page, ['index.php', 'login.php', 'register.php']) || 
                  in_array($request_path, ['', 'index', 'login', 'register']);

// Get app name and logo from settings
$app_name = getSetting('app_name', APP_NAME);
$app_logo = getSetting('app_logo', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $app_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- EmailJS SDK -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <!-- EmailJS Credentials -->
    <script type="text/javascript">
        // Set EmailJS credentials as global variables
        window.EMAILJS_USER_ID = '<?php echo getenv("EMAILJS_USER_ID"); ?>';
        window.EMAILJS_SERVICE_ID = '<?php echo getenv("EMAILJS_SERVICE_ID"); ?>';
        window.EMAILJS_TEMPLATE_ID = '<?php echo getenv("EMAILJS_TEMPLATE_ID"); ?>';
        
        // Log to console if EmailJS is properly configured
        console.log('EmailJS status:', window.EMAILJS_USER_ID ? 'Configured' : 'Not configured');
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url('index'); ?>">
                <?php if ($is_public_page && !empty($app_logo)): ?>
                <img src="<?php echo $app_logo; ?>" alt="<?php echo htmlspecialchars($app_name); ?>" height="30" class="d-inline-block align-text-top">
                <?php else: ?>
                <?php echo htmlspecialchars($app_name); ?>
                <?php endif; ?>
            </a>
            
            <!-- Spacer div to push mobile elements to the right -->
            <div class="flex-grow-1 d-lg-none"></div>
            
            <?php if (!$auth->isLoggedIn()): ?>
            <!-- Mobile/Tablet Login Icon (only shown on small screens) -->
            <a href="<?php echo url('login'); ?>" class="d-lg-none mobile-login-icon" title="Login">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="<?php echo url('register'); ?>" class="d-lg-none mobile-login-icon mobile-register-icon" title="Register">
                <i class="fas fa-user-plus"></i>
            </a>
            <?php endif; ?>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('dashboard'); ?>">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('tasks'); ?>">Earn Points</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('rewards'); ?>">Rewards</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('leaderboard'); ?>">Leaderboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('index'); ?>">Home</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <?php if ($auth->isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo url('admin/index'); ?>">Admin Panel</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-coins"></i> <?php echo formatPoints($current_user['points']); ?> Points
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog"></i> My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="login.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('login'); ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo url('register'); ?>">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="<?php echo ($current_page === 'index.php' || $request_path === '' || $request_path === 'index') && !$auth->isLoggedIn() ? 'full-width-container' : 'container py-4'; ?>"><?php 
    /* Use full width for homepage when not logged in, otherwise use regular container */
    ?>
