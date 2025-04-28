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
    
    // Get EmailJS settings
    $emailjs_user_id = filter_input(INPUT_POST, 'emailjs_user_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $emailjs_service_id = filter_input(INPUT_POST, 'emailjs_service_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $emailjs_template_id = filter_input(INPUT_POST, 'emailjs_template_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update notification email setting
        $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'notification_email'");
        $stmt->execute([$notification_email ?: '']);
        
        // Update notification email enabled setting
        $stmt = $conn->prepare("UPDATE admin_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = 'notification_email_enabled'");
        $stmt->execute([$notification_email_enabled]);
        
        // Save EmailJS settings to environment variables
        if (!empty($emailjs_user_id)) {
            putenv("EMAILJS_USER_ID=$emailjs_user_id");
            // Also save to admin_settings table for persistence
            $stmt = $conn->prepare("INSERT OR REPLACE INTO admin_settings (setting_key, setting_value) VALUES ('emailjs_user_id', ?)");
            $stmt->execute([$emailjs_user_id]);
        }
        
        if (!empty($emailjs_service_id)) {
            putenv("EMAILJS_SERVICE_ID=$emailjs_service_id");
            // Also save to admin_settings table for persistence
            $stmt = $conn->prepare("INSERT OR REPLACE INTO admin_settings (setting_key, setting_value) VALUES ('emailjs_service_id', ?)");
            $stmt->execute([$emailjs_service_id]);
        }
        
        if (!empty($emailjs_template_id)) {
            putenv("EMAILJS_TEMPLATE_ID=$emailjs_template_id");
            // Also save to admin_settings table for persistence
            $stmt = $conn->prepare("INSERT OR REPLACE INTO admin_settings (setting_key, setting_value) VALUES ('emailjs_template_id', ?)");
            $stmt->execute([$emailjs_template_id]);
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_message = 'Notification settings updated successfully.';
        
        // Send a test notification if enabled and email is provided
        if ($notification_email_enabled && $notification_email) {
            // Create a test notification in the admin panel
            createAdminNotification(
                'Test Notification', 
                'This is a test notification created from your notification settings. EmailJS configuration has been updated.',
                'system'
            );
            
            $success_message .= ' A test notification was created in your notifications panel.';
            
            // Note about EmailJS test
            if (!empty($emailjs_user_id) && !empty($emailjs_service_id) && !empty($emailjs_template_id)) {
                $success_message .= ' To test EmailJS, make a test redemption or reload the page with your new settings.';
            }
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error_message = 'Error updating notification settings: ' . $e->getMessage();
    }
}

// Label for processing end
end_of_processing:

// Get current notification settings
try {
    $notification_settings = [];
    $stmt = $conn->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_key IN ('notification_email', 'notification_email_enabled', 'emailjs_user_id', 'emailjs_service_id', 'emailjs_template_id')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notification_settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Set environment variables for EmailJS if they're in the database
    if (!empty($notification_settings['emailjs_user_id'])) {
        putenv("EMAILJS_USER_ID=" . $notification_settings['emailjs_user_id']);
    }
    if (!empty($notification_settings['emailjs_service_id'])) {
        putenv("EMAILJS_SERVICE_ID=" . $notification_settings['emailjs_service_id']);
    }
    if (!empty($notification_settings['emailjs_template_id'])) {
        putenv("EMAILJS_TEMPLATE_ID=" . $notification_settings['emailjs_template_id']);
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
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
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
                                <span class="input-group-text">1 Spin =</span>
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
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
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
                
                <hr>
                <h6 class="mb-3">EmailJS Integration</h6>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> These settings are used to configure EmailJS for sending notification emails. 
                    You'll need to create an account at <a href="https://www.emailjs.com" target="_blank">EmailJS.com</a> and set up a service and template.
                </div>
                
                <div class="mb-3">
                    <label for="emailjs_user_id" class="form-label">EmailJS User ID</label>
                    <input type="text" class="form-control" id="emailjs_user_id" name="emailjs_user_id" 
                           value="<?php echo htmlspecialchars(getenv('EMAILJS_USER_ID') ?: ''); ?>"
                           placeholder="Enter your EmailJS User ID">
                    <div class="form-text text-muted">
                        Your EmailJS User ID from your EmailJS dashboard.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="emailjs_service_id" class="form-label">EmailJS Service ID</label>
                    <input type="text" class="form-control" id="emailjs_service_id" name="emailjs_service_id" 
                           value="<?php echo htmlspecialchars(getenv('EMAILJS_SERVICE_ID') ?: ''); ?>"
                           placeholder="Enter your EmailJS Service ID">
                    <div class="form-text text-muted">
                        The Service ID from your configured EmailJS service.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="emailjs_template_id" class="form-label">EmailJS Template ID</label>
                    <input type="text" class="form-control" id="emailjs_template_id" name="emailjs_template_id" 
                           value="<?php echo htmlspecialchars(getenv('EMAILJS_TEMPLATE_ID') ?: ''); ?>"
                           placeholder="Enter your EmailJS Template ID">
                    <div class="form-text text-muted">
                        The Template ID from your configured EmailJS email template.
                    </div>
                </div>
                
                <div class="alert alert-secondary">
                    <strong>Template variables:</strong> When creating your EmailJS template, use these variables:<br>
                    <code>{{to_email}}</code>, <code>{{user_name}}</code>, <code>{{user_id}}</code>, <code>{{reward_name}}</code>, <code>{{points_used}}</code>, <code>{{redemption_id}}</code>, <code>{{redemption_details}}</code>, <code>{{date_time}}</code>
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
                You can test the notifications system by clicking the buttons below. 
                Create a test notification to appear in your notifications panel or test your EmailJS configuration.
            </p>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">System Notification</h5>
                            <p class="card-text">Test the in-app notification system.</p>
                            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="create_test_notification" value="1">
                                <button type="button" id="testNotificationBtn" class="btn btn-info">
                                    <i class="fas fa-bell"></i> Create Test Notification
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Email Notification</h5>
                            <p class="card-text">Test EmailJS email notification system.</p>
                            <button type="button" id="testEmailJSBtn" class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Send Test Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="testNotificationResult" class="mt-3" style="display: none;"></div>
            <div id="testEmailResult" class="mt-3" style="display: none;"></div>
        </div>
    </div>
    
    <!-- EmailJS Test Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const testEmailBtn = document.getElementById('testEmailJSBtn');
        const emailResultDiv = document.getElementById('testEmailResult');
        
        if (testEmailBtn) {
            testEmailBtn.addEventListener('click', function() {
                console.log('EmailJS credentials:', {
                    user_id: window.EMAILJS_USER_ID || 'Not set',
                    service_id: window.EMAILJS_SERVICE_ID || 'Not set',
                    template_id: window.EMAILJS_TEMPLATE_ID || 'Not set'
                });
                
                // Check if EmailJS is configured
                if (!window.EMAILJS_USER_ID || !window.EMAILJS_SERVICE_ID || !window.EMAILJS_TEMPLATE_ID) {
                    emailResultDiv.innerHTML = '<div class="alert alert-warning">EmailJS is not properly configured. Please fill in the EmailJS settings above and save them first.</div>';
                    emailResultDiv.style.display = 'block';
                    return;
                }
                
                // Get admin email
                fetch('../includes/ajax_handlers.php?action=get_admin_email')
                .then(response => response.json())
                .then(data => {
                    console.log('Admin email response:', data);
                    
                    if (!data.success || !data.email) {
                        emailResultDiv.innerHTML = '<div class="alert alert-warning">Please set a notification email above and save before testing.</div>';
                        emailResultDiv.style.display = 'block';
                        return;
                    }
                    
                    emailResultDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Sending test email...';
                    emailResultDiv.style.display = 'block';
                    
                    // Initialize EmailJS if not already initialized
                    if (!window.emailjs) {
                        emailResultDiv.innerHTML = '<div class="alert alert-danger">EmailJS library is not loaded properly. Please reload the page and try again.</div>';
                        emailResultDiv.style.display = 'block';
                        return;
                    }
                    
                    // Ensure EmailJS is initialized
                    try {
                        emailjs.init(window.EMAILJS_USER_ID);
                    } catch (e) {
                        console.warn('EmailJS already initialized or error:', e);
                    }
                    
                    // Prepare test data
                    const testData = {
                        adminEmail: data.email,
                        userId: 1,
                        username: 'Admin',
                        rewardName: 'Test Reward',
                        pointsUsed: 100,
                        redemptionId: 0,
                        redemptionDetails: 'This is a test email from the admin settings panel.'
                    };
                    
                    const templateParams = {
                        to_email: testData.adminEmail,
                        user_name: testData.username,
                        user_id: testData.userId,
                        reward_name: testData.rewardName,
                        points_used: testData.pointsUsed,
                        redemption_id: testData.redemptionId,
                        redemption_details: testData.redemptionDetails,
                        date_time: new Date().toLocaleString()
                    };
                    
                    console.log('Sending email with params:', templateParams);
                    
                    // Send test email
                    emailjs.send(
                        window.EMAILJS_SERVICE_ID,
                        window.EMAILJS_TEMPLATE_ID,
                        templateParams
                    )
                    .then(function(response) {
                        console.log('EmailJS response:', response);
                        emailResultDiv.innerHTML = '<div class="alert alert-success">Test email sent successfully to ' + testData.adminEmail + '!</div>';
                    })
                    .catch(function(error) {
                        console.error('EmailJS error:', error);
                        emailResultDiv.innerHTML = '<div class="alert alert-danger">Error sending test email: ' + (error.text || error.message || JSON.stringify(error)) + '</div>';
                    });
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    emailResultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
            });
        }
    });
    </script>
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