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
            
            // Check for API key in both database and environment
            $env_api_key = getenv('OGADS_API_KEY');
            
            if (!$api_key && !$env_api_key) {
                $message = 'API Key is not set. Please enter an API Key first or configure OGADS_API_KEY environment variable.';
                $message_type = 'warning';
            } elseif ($env_api_key && (!$api_key || $api_key != $env_api_key)) {
                // If environment API key is available but different from database, use it
                $api_key = $env_api_key;
                
                // Update the database with the environment API key
                $stmt = $conn->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES ('ogads_api_key', :api_key)");
                $stmt->bindParam(':api_key', $api_key);
                $stmt->execute();
                
                error_log("Used environment API key instead of database key");
            } else {
                // Test connection to the API
                error_log("Testing OGAds API connection");
                
                // First attempt: Try POST method
                $ch = curl_init();
                $api_url = OGADS_API_URL;
                
                // Setup API request data 
                $request_data = [
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'max' => 1
                ];
                
                // Set up cURL with POST method
                curl_setopt_array($ch, [
                    CURLOPT_URL            => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($request_data),
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $api_key,
                        'Content-Type: application/json',
                        'Accept: application/json'
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,  // Enable SSL certificate verification
                    CURLOPT_SSL_VERIFYHOST => 2,     // Verify the certificate's name against host
                    CURLOPT_TIMEOUT        => 30,    // Set a reasonable timeout
                ]);
                
                error_log("Testing OGAds API with POST method");
                error_log("URL: " . $api_url);
                error_log("Data: " . json_encode($request_data));
                
                // Execute POST request
                $response_post = curl_exec($ch);
                $curl_error = curl_errno($ch) ? curl_error($ch) : null;
                $http_code_post = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                error_log("POST Response Code: " . $http_code_post);
                if ($curl_error) {
                    error_log("cURL Error: " . $curl_error);
                }
                error_log("POST Response: " . $response_post);
                
                curl_close($ch);
                
                // Second attempt: Try GET method if POST failed with 405
                if ($http_code_post === 405) {
                    error_log("POST method not allowed, trying GET method");
                    
                    $ch = curl_init();
                    
                    // Create query string for GET request
                    $query_params = http_build_query($request_data);
                    $get_url = $api_url . '?' . $query_params;
                    
                    curl_setopt_array($ch, [
                        CURLOPT_URL            => $get_url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $api_key,
                            'Accept: application/json'
                        ],
                        CURLOPT_SSL_VERIFYPEER => true,  // Enable SSL certificate verification
                        CURLOPT_SSL_VERIFYHOST => 2,     // Verify the certificate's name against host
                        CURLOPT_TIMEOUT        => 30,    // Set a reasonable timeout
                    ]);
                    
                    error_log("Testing OGAds API with GET method");
                    error_log("URL: " . $get_url);
                    
                    // Execute GET request
                    $response = curl_exec($ch);
                    $curl_error = curl_errno($ch) ? curl_error($ch) : null;
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    
                    error_log("GET Response Code: " . $http_code);
                    if ($curl_error) {
                        error_log("cURL Error: " . $curl_error);
                    }
                    error_log("GET Response: " . $response);
                    
                    curl_close($ch);
                } else {
                    // Use POST response
                    $response = $response_post;
                    $http_code = $http_code_post;
                }
                
                // Check for curl errors first
                if ($curl_error) {
                    $message = 'API connection failed due to a cURL error: ' . $curl_error;
                    
                    // SSL certificate errors are common, provide specific guidance
                    if (strpos($curl_error, 'SSL certificate') !== false) {
                        $message .= '<br><br>This appears to be an SSL certificate issue. You might need to:
                        <ul>
                            <li>Check if your server has updated CA certificates</li>
                            <li>Verify the API endpoint URL is correct and uses HTTPS</li>
                            <li>Contact OGAds to ensure their SSL certificate is valid</li>
                        </ul>';
                    }
                    
                    $message_type = 'danger';
                } else if ($http_code === 200) {
                    $message = 'API connection successful! Received valid response from OGAds API.';
                    $message_type = 'success';
                } else {
                    $error_message = '';
                    $response_data = json_decode($response, true);
                    
                    // Handle specific error codes
                    if ($http_code === 401) {
                        $error_message = " - Invalid API key or authorization issue. Please check your API key is correct.";
                    } elseif ($http_code === 403) {
                        $error_message = " - Access forbidden. Your account may not have proper permissions.";
                    } elseif ($http_code === 404) {
                        $error_message = " - API endpoint not found. Please check the API URL configuration.";
                    } elseif ($http_code === 405) {
                        $error_message = " - Method not allowed. The API may have changed its expected request method.";
                    } elseif ($http_code === 429) {
                        $error_message = " - Too many requests. API rate limit reached.";
                    } elseif ($http_code >= 500) {
                        $error_message = " - OGAds server error. Please try again later.";
                    }
                    
                    // Add response error if available
                    if ($response_data && isset($response_data['error'])) {
                        $error_message .= " Response: " . $response_data['error'];
                    }
                    
                    $message = 'API connection failed. Status code: ' . $http_code . $error_message;
                    $message_type = 'danger';
                    
                    // Provide clearer guidance
                    $message .= '<br><br>Troubleshooting tips: 
                    <ul>
                        <li>Confirm your API key is correctly entered without extra spaces</li>
                        <li>Check if your OGAds account is active and has the necessary permissions</li>
                        <li>Try again later if it might be a temporary server issue</li>
                        <li>Contact OGAds support if the problem persists</li>
                    </ul>';
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