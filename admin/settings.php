<?php
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

$db = Database::getInstance();
$conn = $db->getConnection();

// Initialize message variables
$success_message = '';
$error_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $settings = [
        'points_conversion_rate' => isset($_POST['points_conversion_rate']) ? intval($_POST['points_conversion_rate']) : 100,
        'points_to_spin_ratio' => isset($_POST['points_to_spin_ratio']) ? intval($_POST['points_to_spin_ratio']) : 100,
        'app_name' => isset($_POST['app_name']) ? trim($_POST['app_name']) : 'GetSpins App'
    ];
    
    // Handle logo upload if a file was submitted
    if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 1024 * 1024; // 1MB
        
        // Verify file type and size
        if (!in_array($_FILES['app_logo']['type'], $allowed_types)) {
            $error_message = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
        } elseif ($_FILES['app_logo']['size'] > $max_size) {
            $error_message = 'File size exceeds the maximum limit (1MB).';
        } else {
            // Create uploads directory if it doesn't exist
            $uploads_dir = '../uploads';
            if (!file_exists($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }
            
            // Generate a unique filename
            $filename = 'logo_' . time() . '_' . strtolower(str_replace(' ', '_', $_FILES['app_logo']['name']));
            $target_path = $uploads_dir . '/' . $filename;
            
            // Move the uploaded file to the uploads directory
            if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $target_path)) {
                // Set the logo path in settings
                $settings['app_logo'] = 'uploads/' . $filename;
            } else {
                $error_message = 'Failed to upload logo. Please try again.';
            }
        }
    }
    
    // Update settings in the database
    if (empty($error_message)) {
        try {
            $conn->beginTransaction();
            
            foreach ($settings as $key => $value) {
                // Check if the setting already exists
                $stmt = $conn->prepare("SELECT setting_key FROM settings WHERE setting_key = :key");
                $stmt->bindValue(':key', $key);
                $stmt->execute();
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exists) {
                    // Update existing setting
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
                    $stmt->bindValue(':key', $key);
                    $stmt->bindValue(':value', $value);
                    $stmt->execute();
                } else {
                    // Insert new setting
                    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
                    $stmt->bindValue(':key', $key);
                    $stmt->bindValue(':value', $value);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            $success_message = 'Settings updated successfully.';
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = 'Error updating settings: ' . $e->getMessage();
        }
    }
}

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

// Set default values if settings don't exist
$points_conversion_rate = isset($settings['points_conversion_rate']) ? $settings['points_conversion_rate'] : POINTS_CONVERSION_RATE;
$points_to_spin_ratio = isset($settings['points_to_spin_ratio']) ? $settings['points_to_spin_ratio'] : 100;
$app_name = isset($settings['app_name']) ? $settings['app_name'] : APP_NAME;
$app_logo = isset($settings['app_logo']) ? $settings['app_logo'] : '';

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Application Settings</h1>
    </div>
    
    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="app_name">Application Name</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?php echo htmlspecialchars($app_name); ?>">
                            <small class="form-text text-muted">The name of your application that will be displayed to users.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_logo">Application Logo</label>
                            <?php if (!empty($app_logo)): ?>
                            <div class="mb-2">
                                <img src="<?php echo htmlspecialchars('../' . $app_logo); ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 100px;">
                                <p class="text-muted small">Current logo: <?php echo htmlspecialchars($app_logo); ?></p>
                            </div>
                            <?php endif; ?>
                            <input type="file" class="form-control-file" id="app_logo" name="app_logo">
                            <small class="form-text text-muted">Upload a new logo for your application (JPEG, PNG, or GIF, max 1MB). The logo will only appear on public pages (home, login, and register).</small>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label for="points_conversion_rate">Points to Currency Conversion Rate</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="points_conversion_rate" name="points_conversion_rate" value="<?php echo htmlspecialchars($points_conversion_rate); ?>" min="1">
                                <div class="input-group-append">
                                    <span class="input-group-text">points = $1.00</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">How many points equal one dollar in value.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="points_to_spin_ratio">Points to Spin Ratio</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="points_to_spin_ratio" name="points_to_spin_ratio" value="<?php echo htmlspecialchars($points_to_spin_ratio); ?>" min="1">
                                <div class="input-group-append">
                                    <span class="input-group-text">points = 1 spin</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">How many points equal one spin in Monopoly Go or Coin Master.</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Settings Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Application Branding</h5>
                        <p>The application name and logo will be displayed on public pages like the home page, login, and registration pages. The logo will not appear in the user dashboard or admin panel.</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Points Conversion</h5>
                        <p>The points conversion rate determines how your points system translates to real-world value. This helps users understand the value of their points.</p>
                    </div>
                    
                    <div>
                        <h5>Spin Ratio</h5>
                        <p>This setting controls how many points a user needs to earn one spin in Monopoly Go or Coin Master games. This will be used when users redeem their points for game rewards.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>