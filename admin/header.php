<?php
// Admin panel header
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

$auth = new Auth();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$current_user = $auth->getUser();
$current_page = basename($_SERVER['PHP_SELF']);

// Get app name from settings
$app_name = getSetting('app_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo $app_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <style>
        /* Admin Panel Styles */
        :root {
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: white;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-brand {
            height: 4.375rem;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
            padding: 1.5rem 1rem;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem;
        }

        .sidebar-heading {
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 800;
            padding: 1rem;
            margin-bottom: 0;
        }

        .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 700;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .nav-item .nav-link i {
            margin-right: 0.5rem;
            font-size: 0.85rem;
            width: 1.5rem;
            text-align: center;
        }

        .nav-item .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-item .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Main Content */
        .content {
            margin-left: var(--sidebar-width);
            padding: 0 1.5rem;
        }
        
        /* Fix for content wrapper to not overlap with sidebar */
        #content-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Container padding */
        .container-fluid {
            padding: 1.5rem;
        }

        .content-header {
            padding: 1.5rem 0;
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 1.5rem;
        }

        /* Topbar */
        .topbar {
            height: 4.375rem;
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar .navbar-search {
            width: 25rem;
        }

        .topbar .topbar-divider {
            width: 0;
            border-right: 1px solid #e3e6f0;
            height: 2rem;
            margin: auto 1rem;
        }

        /* Cards */
        .card {
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .card-header {
            padding: 0.75rem 1.25rem;
            margin-bottom: 0;
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        /* Utilities */
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }

        /* Responsive Sidebar */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content {
                margin-left: 0;
            }
            
            #content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar .navbar-search {
                width: 12rem;
            }
        }

        /* Stats Cards */
        .stats-card {
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }

        .stats-card-success {
            background: linear-gradient(45deg, #1cc88a, #13855c);
            color: white;
        }

        .stats-card-info {
            background: linear-gradient(45deg, #36b9cc, #258391);
            color: white;
        }

        .stats-card-warning {
            background: linear-gradient(45deg, #f6c23e, #dda20a);
            color: white;
        }

        .stats-card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stats-card-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stats-card-title {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.8;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
        }
        
        .table th {
            font-weight: 700;
            background-color: #f8f9fc;
        }
    </style>
</head>
<body>
    <!-- Page Wrapper -->
    <div id="wrapper">
        
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        <!-- End of Sidebar -->
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            
            <!-- Main Content -->
            <div id="content">
                
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow" style="height: 60px;">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Notifications -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <?php 
                            // Get unread notifications count
                            $unread_count = getUnreadNotificationsCount();
                            $notifications = getAdminNotifications(5);
                            ?>
                            <a class="nav-link position-relative d-inline-flex align-items-center justify-content-center" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.75rem 1rem;">
                                <i class="fas fa-bell text-primary" style="font-size: 1.25rem;"></i>
                                <!-- Counter - Notifications -->
                                <?php if ($unread_count > 0): ?>
                                <span class="position-absolute badge rounded-pill bg-danger" style="font-size: 0.65rem; top: 0.5rem; right: 0.5rem;">
                                    <?php echo $unread_count < 100 ? $unread_count : '99+'; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                                <?php endif; ?>
                            </a>
                            <!-- Dropdown - Notifications -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header bg-primary text-white">
                                    Notifications
                                </h6>
                                
                                <?php if (empty($notifications)): ?>
                                <div class="dropdown-item text-center small text-gray-500">No notifications found</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                    <a class="dropdown-item d-flex align-items-center notification-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                       href="<?php echo getNotificationUrl($notification); ?>"
                                       data-notification-id="<?php echo $notification['id']; ?>">
                                        <div class="me-3">
                                            <div class="icon-circle bg-<?php echo getNotificationIconClass($notification['type']); ?>">
                                                <i class="fas <?php echo getNotificationIcon($notification['type']); ?> text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small text-gray-500"><?php echo formatDate($notification['created_at']); ?></div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($notification['title']); ?></span>
                                            <div class="small"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <a class="dropdown-item text-center small text-primary" href="notifications.php">Show All Notifications</a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block" style="margin: 0 0.5rem; height: 60%;"></div>
                        
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.75rem 1rem;">
                                <span class="me-2 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($current_user['username']); ?></span>
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;"><?php echo strtoupper(substr($current_user['username'], 0, 1)); ?></div>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="../dashboard.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    User Dashboard
                                </a>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Admin Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../login.php?action=logout">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->
                
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for dropdown notifications
    document.querySelectorAll('.notification-item').forEach(function(notification) {
        notification.addEventListener('click', function(e) {
            const notificationId = this.getAttribute('data-notification-id');
            if (notificationId) {
                // Mark notification as read when clicked
                fetch('../includes/ajax_handlers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=mark_notification_read&notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update notification item UI
                        this.classList.remove('bg-light');
                        
                        // Update badge count
                        updateNotificationCount();
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
            }
        });
    });
    
    // Function to update notification count
    function updateNotificationCount() {
        fetch('../includes/ajax_handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_unread_count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('#alertsDropdown .badge');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count < 100 ? data.count : '99+';
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Error getting notification count:', error));
    }
});
</script>
