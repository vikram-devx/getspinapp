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

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

$message = '';
$message_type = '';

// Handle form submission to update API settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // API Key Settings
        if (isset($_POST['update_api_key'])) {
            $api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
            
            // Update config file or database with new API key
            $stmt = $conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('ogads_api_key', :api_key)");
            $stmt->bindParam(':api_key', $api_key);
            
            if ($stmt->execute()) {
                $message = 'API Key updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Failed to update API Key.';
                $message_type = 'danger';
            }
        }
        
        // Offer Settings
        if (isset($_POST['update_offer_settings'])) {
            $max_offers = isset($_POST['max_offers']) ? (int)$_POST['max_offers'] : 10;
            $min_offers = isset($_POST['min_offers']) ? (int)$_POST['min_offers'] : 3;
            $offer_types = isset($_POST['offer_types']) ? $_POST['offer_types'] : array();
            
            // Calculate ctype based on selected offer types
            $ctype = 0;
            foreach ($offer_types as $type) {
                $ctype += (int)$type;
            }
            
            // Update settings in the database
            $stmt = $conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('ogads_max_offers', :max_offers)");
            $stmt->bindParam(':max_offers', $max_offers);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('ogads_min_offers', :min_offers)");
            $stmt->bindParam(':min_offers', $min_offers);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('ogads_ctype', :ctype)");
            $stmt->bindParam(':ctype', $ctype);
            $stmt->execute();
            
            $message = 'Offer settings updated successfully!';
            $message_type = 'success';
        }
        
        // Test API Connection
        if (isset($_POST['test_api'])) {
            // Get API key from database
            $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ogads_api_key'");
            $stmt->execute();
            $api_key = $stmt->fetchColumn();
            
            if (!$api_key) {
                $message = 'API Key is not set. Please enter an API Key first.';
                $message_type = 'warning';
            } else {
                // Test connection to the API
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://unlockcontent.net/api/v2');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json'
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'max' => 1
                ]));
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($http_code === 200) {
                    $message = 'API connection successful! Received valid response from OGAds API.';
                    $message_type = 'success';
                } else {
                    $message = 'API connection failed. Status code: ' . $http_code;
                    $message_type = 'danger';
                }
            }
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get current settings
try {
    $settings = [];
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $message = 'Failed to load settings: ' . $e->getMessage();
    $message_type = 'danger';
    $settings = [];
}

// Default values if not set
$api_key = isset($settings['ogads_api_key']) ? $settings['ogads_api_key'] : '';
$max_offers = isset($settings['ogads_max_offers']) ? $settings['ogads_max_offers'] : 10;
$min_offers = isset($settings['ogads_min_offers']) ? $settings['ogads_min_offers'] : 3;
$ctype = isset($settings['ogads_ctype']) ? (int)$settings['ogads_ctype'] : 0;

// Calculate selected offer types
$selected_offer_types = [];
if ($ctype & 1) $selected_offer_types[] = 1; // CPI
if ($ctype & 2) $selected_offer_types[] = 2; // CPA
if ($ctype & 4) $selected_offer_types[] = 4; // PIN
if ($ctype & 8) $selected_offer_types[] = 8; // VID

include 'header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">OGAds API Settings</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- API Key Settings -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">API Credentials</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="api_key" class="form-label">OGAds API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($api_key); ?>" placeholder="Enter your OGAds API Key">
                                <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                This API key is used to communicate with OGAds. You can generate a new key on the <a href="https://www.unlockcontent.net/affiliates/api/docs" target="_blank">OGAds API portal</a>.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="update_api_key" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save API Key
                            </button>
                            <button type="submit" name="test_api" class="btn btn-info">
                                <i class="fas fa-vial"></i> Test API Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Offer Settings -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Offer Settings</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="max_offers" class="form-label">Maximum Offers</label>
                            <input type="number" class="form-control" id="max_offers" name="max_offers" min="1" max="100" value="<?php echo $max_offers; ?>">
                            <div class="form-text">Maximum number of offers to return from the API.</div>
                        </div>

                        <div class="mb-3">
                            <label for="min_offers" class="form-label">Minimum Offers</label>
                            <input type="number" class="form-control" id="min_offers" name="min_offers" min="0" max="50" value="<?php echo $min_offers; ?>">
                            <div class="form-text">Minimum number of offers to return. Setting this may cause offers you have blocked to show if there are not enough unblocked offers available.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Offer Types</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="offer_types[]" value="1" id="cpi" <?php echo in_array(1, $selected_offer_types) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cpi">CPI (Cost Per Install)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="offer_types[]" value="2" id="cpa" <?php echo in_array(2, $selected_offer_types) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="cpa">CPA (Cost Per Action)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="offer_types[]" value="4" id="pin" <?php echo in_array(4, $selected_offer_types) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="pin">PIN (PIN Submission)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="offer_types[]" value="8" id="vid" <?php echo in_array(8, $selected_offer_types) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="vid">VID (Video)</label>
                            </div>
                            <div class="form-text">
                                Select the offer types to include in API requests. If none selected, all offer types will be returned.
                            </div>
                        </div>

                        <button type="submit" name="update_offer_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Offer Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- API Documentation -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">OGAds API Documentation</h6>
                </div>
                <div class="card-body">
                    <h5>API Endpoint</h5>
                    <div class="mb-3">
                        <code>https://unlockcontent.net/api/v2</code>
                    </div>

                    <h5>Required Parameters</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>ip</code></td>
                                <td>String</td>
                                <td>Visitor's IP address</td>
                            </tr>
                            <tr>
                                <td><code>user_agent</code></td>
                                <td>String</td>
                                <td>Visitor's user agent</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5>Optional Parameters</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>ctype</code></td>
                                <td>Integer</td>
                                <td>
                                    Bitwise flag for offer types:<br>
                                    1 = CPI<br>
                                    2 = CPA<br>
                                    4 = PIN<br>
                                    8 = VID<br>
                                    Example: 12 = PIN (4) + VID (8)
                                </td>
                            </tr>
                            <tr>
                                <td><code>max</code></td>
                                <td>Integer</td>
                                <td>Maximum number of offers to return</td>
                            </tr>
                            <tr>
                                <td><code>min</code></td>
                                <td>Integer</td>
                                <td>Minimum number of offers to return</td>
                            </tr>
                            <tr>
                                <td><code>aff_sub4</code>, <code>aff_sub5</code></td>
                                <td>String</td>
                                <td>Additional data for advanced users</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle API Key visibility
    const toggleApiKeyBtn = document.getElementById('toggleApiKey');
    const apiKeyInput = document.getElementById('api_key');
    
    if (toggleApiKeyBtn && apiKeyInput) {
        toggleApiKeyBtn.addEventListener('click', function() {
            const type = apiKeyInput.getAttribute('type') === 'password' ? 'text' : 'password';
            apiKeyInput.setAttribute('type', type);
            
            // Toggle eye icon
            const icon = toggleApiKeyBtn.querySelector('i');
            if (icon.classList.contains('fa-eye')) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Start with password type for security
        apiKeyInput.setAttribute('type', 'password');
    }
});
</script>

<?php include 'footer.php'; ?>