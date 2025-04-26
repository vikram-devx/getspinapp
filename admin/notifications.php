<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$current_user = $auth->getUser();
$db = Database::getInstance();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle marking all as read
if (isset($_POST['mark_all_read'])) {
    $count = markAllNotificationsAsRead();
    if ($count !== false) {
        $success_message = "Marked $count notifications as read.";
    } else {
        $error_message = "Error marking notifications as read.";
    }
}

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$unread_only = isset($_GET['unread']) && $_GET['unread'] == '1';

// Get notifications with filtering
$notifications = [];
try {
    $sql = "SELECT * FROM admin_notifications WHERE 1=1";
    $params = [];
    
    if ($filter_type) {
        $sql .= " AND type = ?";
        $params[] = $filter_type;
    }
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching notifications: " . $e->getMessage();
}

// Get notification types for filter dropdown
$notification_types = [];
try {
    $stmt = $conn->query("SELECT DISTINCT type FROM admin_notifications ORDER BY type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notification_types[] = $row['type'];
    }
} catch (PDOException $e) {
    // Silently fail and use empty array
}

// Include header
$page_title = 'Notifications';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Notifications</h1>
        <form method="post" class="d-inline">
            <button type="submit" name="mark_all_read" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-check fa-sm text-white-50"></i> Mark All as Read
            </button>
        </form>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="filter_type" class="form-label">Notification Type</label>
                    <select class="form-select" id="filter_type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($notification_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type == $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($type)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="unread_only" name="unread" value="1" <?php echo $unread_only ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="unread_only">
                            Show Unread Only
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="notifications.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Notifications</h6>
        </div>
        <div class="card-body">
            <?php if (empty($notifications)): ?>
            <div class="text-center p-4">
                <i class="fas fa-bell fa-3x text-gray-300 mb-3"></i>
                <p class="text-gray-500">No notifications found.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover" id="notificationsTable">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Date/Time</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                        <tr class="<?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <td>
                                <?php if ($notification['is_read']): ?>
                                <span class="badge bg-secondary">Read</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($notification['created_at']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getNotificationIconClass($notification['type']); ?>">
                                    <i class="fas <?php echo getNotificationIcon($notification['type']); ?> me-1"></i>
                                    <?php echo ucfirst(htmlspecialchars($notification['type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($notification['title']); ?></td>
                            <td><?php echo htmlspecialchars($notification['message']); ?></td>
                            <td>
                                <?php if (!$notification['is_read']): ?>
                                <button class="btn btn-sm btn-outline-primary mark-read-btn" data-notification-id="<?php echo $notification['id']; ?>">
                                    <i class="fas fa-check"></i> Mark Read
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($notification['related_id'])): ?>
                                <a href="<?php echo getNotificationUrl($notification); ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark notification as read when button is clicked
    document.querySelectorAll('.mark-read-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            const row = this.closest('tr');
            
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
                    // Update row appearance
                    row.classList.remove('bg-light');
                    
                    // Update status badge
                    const statusBadge = row.querySelector('td:first-child .badge');
                    if (statusBadge) {
                        statusBadge.classList.remove('bg-primary');
                        statusBadge.classList.add('bg-secondary');
                        statusBadge.textContent = 'Read';
                    }
                    
                    // Hide mark read button
                    this.style.display = 'none';
                    
                    // Update notification count in header
                    const headerBadge = document.querySelector('#alertsDropdown .badge');
                    if (headerBadge) {
                        const currentCount = parseInt(headerBadge.textContent) || 0;
                        if (currentCount > 1) {
                            headerBadge.textContent = currentCount - 1;
                        } else {
                            headerBadge.style.display = 'none';
                        }
                    }
                } else {
                    alert('Error marking notification as read: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the notification as read.');
            });
        });
    });
    
    // Initialize DataTable if available
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#notificationsTable').DataTable({
            order: [[1, 'desc']], // Sort by date/time, newest first
            pageLength: 25
        });
    }
});
</script>