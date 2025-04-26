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

// Get current settings
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $error_message = 'Error retrieving settings: ' . $e->getMessage();
}

// Handle app settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_app_settings'])) {
    $app_name = filter_input(INPUT_POST, 'app_name');
    $app_name = $app_name ? htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8') : '';
    $app_color = filter_input(INPUT_POST, 'app_color');
    $app_color = $app_color ? htmlspecialchars($app_color, ENT_QUOTES, 'UTF-8') : '#4e73df';
    $payout_point_ratio = filter_input(INPUT_POST, 'payout_point_ratio', FILTER_VALIDATE_INT);
    $point_spin_ratio = filter_input(INPUT_POST, 'point_spin_ratio', FILTER_VALIDATE_INT);
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update settings
        saveSetting('app_name', $app_name);
        saveSetting('app_color', $app_color);
        saveSetting('payout_point_ratio', $payout_point_ratio > 0 ? $payout_point_ratio : 1000);
        saveSetting('point_spin_ratio', $point_spin_ratio > 0 ? $point_spin_ratio : 100);
        
        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_name = $_FILES['logo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file extension
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = 'logo_' . time() . '_' . $file_name;
                $upload_path = '../uploads/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    saveSetting('logo', $new_file_name);
                } else {
                    $error_message = 'Failed to upload logo';
                    $conn->rollBack();
                    goto end_of_processing;
                }
            } else {
                $error_message = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_exts);
                $conn->rollBack();
                goto end_of_processing;
            }
        }
        
        // Handle auth logo upload
        if (isset($_FILES['auth_logo']) && $_FILES['auth_logo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['auth_logo']['tmp_name'];
            $file_name = $_FILES['auth_logo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Validate file extension
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = 'auth_logo_' . time() . '_' . $file_name;
                $upload_path = '../uploads/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    saveSetting('auth_logo', $new_file_name);
                } else {
                    $error_message = 'Failed to upload authentication logo';
                    $conn->rollBack();
                    goto end_of_processing;
                }
            } else {
                $error_message = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_exts);
                $conn->rollBack();
                goto end_of_processing;
            }
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = 'App settings updated successfully';
        
        // Refresh settings after save
        $settings = [];
        $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $error_message = 'Error saving settings: ' . $e->getMessage();
    }
}

// Handle notification settings form submission
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

// Label for processing end
end_of_processing:

// Get current notification settings
try {
    $notification_settings = [];
    $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('notification_email', 'notification_email_enabled')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notification_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    if (empty($error_message)) {
        $error_message = 'Error retrieving notification settings: ' . $e->getMessage();
    }
}

// Include header
$page_title = 'App Settings';
include 'header.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">App Settings</h1>
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

    <!-- App Settings Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">App Settings</h6>
        </div>
        <div class="card-body">
            <form method="post" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="app_name" class="form-label">App Name</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                value="<?php echo htmlspecialchars($settings['app_name'] ?? APP_NAME); ?>"
                                placeholder="GetSpins">
                            <div class="form-text text-muted">
                                The name of your application, displayed in headers and titles.
                            </div>
                        </div>
                        

                        
                        <div class="mb-3">
                            <label for="app_color" class="form-label">App Theme Color</label>
                            <input type="color" class="form-control" id="app_color" name="app_color" 
                                value="<?php echo htmlspecialchars($settings['app_color'] ?? '#4e73df'); ?>">
                            <div class="form-text text-muted">
                                The main color theme for your application.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="payout_point_ratio" class="form-label">Payout to Point Ratio</label>
                            <div class="input-group">
                                <span class="input-group-text">$1 =</span>
                                <input type="number" class="form-control" id="payout_point_ratio" name="payout_point_ratio" 
                                    value="<?php echo htmlspecialchars($settings['payout_point_ratio'] ?? '1000'); ?>"
                                    min="1" step="1">
                                <span class="input-group-text">points</span>
                            </div>
                            <div class="form-text text-muted">
                                How many points equal $1 in payout value (e.g., 1000 points = $1).
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="point_spin_ratio" class="form-label">Point to Spin Ratio</label>
                            <div class="input-group">
                                <span class="input-group-text">1 <?php echo htmlspecialchars($settings['point_name'] ?? 'Spin'); ?> =</span>
                                <input type="number" class="form-control" id="point_spin_ratio" name="point_spin_ratio" 
                                    value="<?php echo htmlspecialchars($settings['point_spin_ratio'] ?? '100'); ?>"
                                    min="1" step="1">
                                <span class="input-group-text">points</span>
                            </div>
                            <div class="form-text text-muted">
                                How many points are needed to earn one spin (e.g., 100 points = 1 spin).
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo</label>
                            <?php if (!empty($settings['logo'])): ?>
                            <div class="mb-2">
                                <img src="../uploads/<?php echo htmlspecialchars($settings['logo']); ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="logo" name="logo">
                            <div class="form-text text-muted">
                                Upload your application logo. Recommended size: 200x60 px.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="auth_logo" class="form-label">Authentication Page Logo</label>
                            <?php if (!empty($settings['auth_logo'])): ?>
                            <div class="mb-2">
                                <img src="../uploads/<?php echo htmlspecialchars($settings['auth_logo']); ?>" alt="Authentication Logo" class="img-thumbnail" style="max-height: 100px;">
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="auth_logo" name="auth_logo">
                            <div class="form-text text-muted">
                                Upload the logo displayed on login and register pages. Recommended size: 150x150 px.
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="save_app_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save App Settings
                </button>
            </form>
        </div>
    </div>
    
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
                    <i class="fas fa-save"></i> Save Notification Settings
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
                        const newCount = currentCount + 1;
                        notificationBadge.textContent = newCount < 100 ? newCount : '99+';
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