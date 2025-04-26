<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <i class="fas fa-trophy"></i>
        </div>
        <div class="sidebar-brand-text ms-2"><?php echo $app_name; ?> Admin</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                Dashboard
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            Management
        </div>

        <!-- Nav Item - Users -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-fw fa-users"></i>
                Users
            </a>
        </li>

        <!-- Nav Item - Rewards -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'rewards.php' ? 'active' : ''; ?>" href="rewards.php">
                <i class="fas fa-fw fa-gift"></i>
                Rewards
            </a>
        </li>

        <!-- Nav Item - Redemptions -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'redemptions.php' ? 'active' : ''; ?>" href="redemptions.php">
                <i class="fas fa-fw fa-exchange-alt"></i>
                Redemptions
            </a>
        </li>

        <!-- Nav Item - Transactions -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                <i class="fas fa-fw fa-money-bill-wave"></i>
                Transactions
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            Configuration
        </div>

        <!-- Nav Item - App Settings -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-fw fa-sliders-h"></i>
                App Settings
            </a>
        </li>
        
        <!-- Nav Item - Promo Slides -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'promo_slides.php' ? 'active' : ''; ?>" href="promo_slides.php">
                <i class="fas fa-fw fa-images"></i>
                Promo Slides
            </a>
        </li>
        
        <!-- Nav Item - Notifications -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                <i class="fas fa-fw fa-bell"></i>
                Notifications
                <?php 
                $unread_count = getUnreadNotificationsCount();
                if ($unread_count > 0): 
                ?>
                <span class="badge bg-danger rounded-pill ms-1" style="font-size: 0.65rem; transform: translateY(-1px); display: inline-block;"><?php echo $unread_count < 100 ? $unread_count : '99+'; ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- Nav Item - API Settings -->
        <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'api_settings.php' ? 'active' : ''; ?>" href="api_settings.php">
                <i class="fas fa-fw fa-cogs"></i>
                OGAds API Settings
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider">

        <!-- Heading -->
        <div class="sidebar-heading">
            Other
        </div>

        <!-- Nav Item - View Website -->
        <li class="nav-item">
            <a class="nav-link" href="../index.php" target="_blank">
                <i class="fas fa-fw fa-external-link-alt"></i>
                View Website
            </a>
        </li>

        <!-- Nav Item - Logout -->
        <li class="nav-item">
            <a class="nav-link" href="../login.php?action=logout">
                <i class="fas fa-fw fa-sign-out-alt"></i>
                Logout
            </a>
        </li>

        <!-- Divider -->
        <hr class="sidebar-divider d-none d-md-block">
    </ul>
</div>
