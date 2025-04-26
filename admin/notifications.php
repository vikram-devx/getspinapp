<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$current_user = $auth->getUser();
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Mark notifications as read if requested
if ($action === 'mark_read' && isset($_GET['id'])) {
    $id = $_GET['id'];
    markNotificationAsRead($id);
    header('Location: notifications.php');
    exit;
} elseif ($action === 'mark_all_read') {
    markAllNotificationsAsRead();
    header('Location: notifications.php');
    exit;
}

// Get notifications
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$db = Database::getInstance();
$conn = $db->getConnection();

// Get total notifications count for pagination
$count_stmt = $conn->query("SELECT COUNT(*) FROM admin_notifications");
$total_notifications = $count_stmt->fetchColumn();
$total_pages = ceil($total_notifications / $limit);

// Get notifications for current page
$stmt = $conn->prepare("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
$page_title = 'Admin Notifications';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
        <div>
            <?php if ($total_notifications > 0): ?>
            <a href="notifications.php?action=mark_all_read" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-check-circle fa-sm text-white-50"></i> Mark All as Read
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Notifications</h6>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-bell-slash fa-3x text-gray-300"></i>
                    </div>
                    <p class="text-gray-600">No notifications found.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item list-group-item-action <?php echo $notification['is_read'] ? '' : 'bg-light'; ?> py-3">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="icon-circle bg-<?php echo getNotificationIconClass($notification['type']); ?> p-2">
                                                <i class="fas <?php echo getNotificationIcon($notification['type']); ?> text-white"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <?php if (!$notification['is_read']): ?>
                                    <a href="notifications.php?action=mark_read&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo getNotificationUrl($notification); ?>" class="btn btn-sm btn-primary ms-2">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-4">
                    <nav aria-label="Notifications pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="notifications.php?page=<?php echo ($page - 1); ?>">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if ($end_page - $start_page < 4 && $total_pages >= 5) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="notifications.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="notifications.php?page=<?php echo ($page + 1); ?>">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for dropdown notifications
    document.querySelectorAll('.notification-item').forEach(function(notification) {
        notification.addEventListener('click', function(e) {
            const notificationId = this.getAttribute('data-notification-id');
            
            // Make an AJAX call to mark notification as read when clicked
            fetch('notifications.php?action=mark_read&id=' + notificationId, {
                method: 'GET'
            }).then(function() {
                // Could refresh the notification count here if needed
            });
        });
    });
});
</script>