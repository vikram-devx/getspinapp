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
$db = Database::getInstance();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle form submission for notification settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notification_settings'])) {
    $notification_email = filter_input(INPUT_POST, 'notification_email', FILTER_VALIDATE_EMAIL);
    $notification_email_enabled = isset($_POST['notification_email_enabled']) ? 1 : 0;
    
    try {
        // Update notification email setting
        $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'notification_email'");
        $stmt->execute([$notification_email ?: '']);
        
        // Update notification email enabled setting
        $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'notification_email_enabled'");
        $stmt->execute([$notification_email_enabled]);
        
        $success_message = 'Notification settings updated successfully.';
        
        // Send a test notification if enabled and email is provided
        if ($notification_email_enabled && $notification_email) {
            $test_sent = sendAdminEmailNotification(
                'Test Notification', 
                'This is a test email notification from your GetSpins admin panel.', 
                'system'
            );
            
            if ($test_sent) {
                $success_message .= ' A test notification was sent to ' . htmlspecialchars($notification_email) . '.';
            } else {
                $error_message = 'Could not send test notification. Please check your server\'s mail configuration.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Error updating notification settings: ' . $e->getMessage();
    }
}

// Get current notification settings
try {
    $notification_settings = [];
    $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('notification_email', 'notification_email_enabled')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notification_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error_message = 'Error retrieving notification settings: ' . $e->getMessage();
}

// Include header
$page_title = 'Admin Settings';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Admin Settings</h1>
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

    <!-- Notification Settings Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Notification Settings</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notification_email_enabled" name="notification_email_enabled" 
                               <?php echo (isset($notification_settings['notification_email_enabled']) && $notification_settings['notification_email_enabled'] == '1') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="notification_email_enabled">Enable Email Notifications</label>
                    </div>
                    <div class="form-text text-muted">
                        When enabled, important notifications will be sent to the email address below.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notification_email" class="form-label">Notification Email</label>
                    <input type="email" class="form-control" id="notification_email" name="notification_email" 
                           value="<?php echo htmlspecialchars($notification_settings['notification_email'] ?? ''); ?>"
                           placeholder="admin@example.com">
                    <div class="form-text text-muted">
                        Email address that will receive admin notifications.
                    </div>
                </div>
                
                <button type="submit" name="save_notification_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
        </div>
    </div>
    
    <!-- Notification Testing Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Notification Testing</h6>
        </div>
        <div class="card-body">
            <p>
                You can test the notifications system by clicking the button below. 
                This will create a test notification that will appear in your notifications panel.
            </p>
            <form method="post" action="">
                <input type="hidden" name="create_test_notification" value="1">
                <button type="button" id="testNotificationBtn" class="btn btn-info">
                    <i class="fas fa-bell"></i> Create Test Notification
                </button>
            </form>
            
            <div id="testNotificationResult" class="mt-3" style="display: none;"></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBtn = document.getElementById('testNotificationBtn');
    const resultDiv = document.getElementById('testNotificationResult');
    
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            // Create a test notification via AJAX
            resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Creating test notification...';
            resultDiv.style.display = 'block';
            
            fetch('../includes/ajax_handlers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_test_notification'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Test notification created successfully! Check your notification bell.</div>';
                    
                    // Update the notification count if possible
                    const notificationBadge = document.querySelector('#alertsDropdown .badge');
                    if (notificationBadge) {
                        const currentCount = parseInt(notificationBadge.textContent) || 0;
                        notificationBadge.textContent = currentCount + 1;
                        notificationBadge.style.display = 'inline-block';
                    }
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Error creating test notification: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        });
    }
});
</script>