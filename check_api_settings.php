<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

// Get all OGAds settings from database
$db = Database::getInstance();
$conn = $db->getConnection();

echo "OGAds API Settings:\n";
echo "==================\n";

try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($settings)) {
        echo "No OGAds settings found in database.\n";
    } else {
        foreach ($settings as $setting) {
            $key = $setting['setting_key'];
            $value = $setting['setting_value'];
            
            // Mask API key for security
            if ($key === 'ogads_api_key') {
                $value = substr($value, 0, 5) . '...' . substr($value, -5);
            }
            
            echo "$key: $value\n";
        }
    }
} catch (PDOException $e) {
    echo "Error retrieving API settings: " . $e->getMessage() . "\n";
}

// Get account information if we can
echo "\nAttempting to get account information...\n";

try {
    // Get API key
    $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'ogads_api_key'");
    $api_key = $stmt->fetchColumn();
    
    if (empty($api_key)) {
        echo "API key not configured.\n";
    } else {
        // Try to make an API call to get account info if such an endpoint exists
        // Note: This is a guess, as I'm not sure if OGAds API provides account info
        $account_url = "https://unlockcontent.net/api/v2/account";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $account_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $api_key,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($status === 200 && $response) {
            $data = json_decode($response, true);
            echo "Account info: " . print_r($data, true) . "\n";
        } else {
            echo "Failed to get account information. Status: $status\n";
            echo "Response: " . ($response ?: "None") . "\n";
            
            // If account endpoint doesn't exist, try another approach
            echo "\nTrying alternative endpoints...\n";
            
            // Try getting available countries list if such endpoint exists
            $countries_url = "https://unlockcontent.net/api/v2/countries";
            
            curl_setopt($ch, CURLOPT_URL, $countries_url);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($status === 200 && $response) {
                $data = json_decode($response, true);
                echo "Available countries: " . print_r($data, true) . "\n";
            } else {
                echo "Failed to get countries list. Status: $status\n";
            }
        }
        
        curl_close($ch);
    }
} catch (Exception $e) {
    echo "Error getting account information: " . $e->getMessage() . "\n";
}

echo "\nDONE";
?>