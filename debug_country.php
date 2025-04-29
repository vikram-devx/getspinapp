<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

// Print server variables to see what IP info we have
echo "SERVER VARIABLES:\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not set') . "\n";
echo "HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'Not set') . "\n";
echo "HTTP_CLIENT_IP: " . ($_SERVER['HTTP_CLIENT_IP'] ?? 'Not set') . "\n";

// Call the country detection function
echo "\nCOUNTRY DETECTION:\n";
$country = detectUserCountry();
echo "Detected country: $country\n";

// Try a different approach as a test
echo "\nTEST ALTERNATIVE IP GEOLOCATION:\n";
$test_ip = $_SERVER['REMOTE_ADDR'] ?? '93.184.216.34';
echo "Using IP: $test_ip\n";

// Try direct call to ip-api
$response = @file_get_contents("http://ip-api.com/json/{$test_ip}");
echo "Direct API response: " . ($response ?: "Failed to get response") . "\n";

if ($response) {
    $data = json_decode($response, true);
    echo "Parsed response: " . print_r($data, true) . "\n";
}

// Debug OGAds API call
echo "\nOGADS API DEBUG:\n";

// Get API key for testing
$db = Database::getInstance();
$conn = $db->getConnection();
$api_key = "";

try {
    $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'ogads_api_key'");
    $api_key = $stmt->fetchColumn();
    echo "API Key from database: " . (empty($api_key) ? 'Not configured' : '[Set]') . "\n";
} catch (PDOException $e) {
    echo "Error getting API key: " . $e->getMessage() . "\n";
}

if (!empty($api_key)) {
    // Call the offers API with explicit country parameter to see if it works
    // Note: We're testing with a few different country codes
    $test_countries = ['US', 'GB', 'CA', 'AU'];
    
    foreach ($test_countries as $test_country) {
        echo "\nTesting with country code: $test_country\n";
        $result = getOffers(null, null, null, 3, null, $test_country);
        echo "API Response for $test_country: " . (isset($result['status']) ? $result['status'] : 'Unknown status') . "\n";
        
        // Check if we got offers for this country
        if (isset($result['offers']['offers']) && is_array($result['offers']['offers'])) {
            echo "Number of offers: " . count($result['offers']['offers']) . "\n";
            
            // Show first offer to check if country data is included
            if (!empty($result['offers']['offers'])) {
                $first_offer = $result['offers']['offers'][0];
                echo "First offer details:\n";
                echo "  Offer ID: " . ($first_offer['offerid'] ?? 'Unknown') . "\n";
                echo "  Name: " . ($first_offer['name'] ?? 'Unknown') . "\n";
                echo "  Countries: " . (isset($first_offer['countries']) ? implode(',', $first_offer['countries']) : 'Not specified') . "\n";
            }
        } else {
            echo "No offers returned or invalid structure\n";
        }
    }
} else {
    echo "Cannot test OGAds API without a valid API key\n";
}

echo "\nDONE";
?>