<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

$auth = new Auth();
$current_user = $auth->getUser();

// Determine if this is a public page (no logged-in user required)
$current_page = basename($_SERVER['PHP_SELF']);
$is_public_page = !$auth->isLoggedIn() || in_array($current_page, ['index.php', 'login.php', 'register.php']);

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
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if ($is_public_page && !empty($app_logo)): ?>
                <img src="<?php echo $app_logo; ?>" alt="<?php echo htmlspecialchars($app_name); ?>" height="30" class="d-inline-block align-text-top">
                <?php else: ?>
                <?php echo htmlspecialchars($app_name); ?>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="tasks.php">Earn Points</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="rewards.php">Rewards</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="leaderboard.php">Leaderboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                        <?php if ($auth->isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/index.php">Admin Panel</a>
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
                                <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="login.php?action=logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="<?php echo $current_page === 'index.php' && !$auth->isLoggedIn() ? 'full-width-container' : 'container py-4'; ?>"><?php 
    /* Use full width for homepage when not logged in, otherwise use regular container */
    ?>
