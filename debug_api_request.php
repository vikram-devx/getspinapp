<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

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
    // Let's inspect the raw API call to check how the country parameter is being sent
    $test_country = 'GB'; // Test with UK
    $api_url = OGADS_API_URL;
    
    echo "Testing direct API call to: $api_url\n";
    echo "Using country code: $test_country\n";
    
    // Data for the request
    $data = [
        'ip' => '93.184.216.34',  // Example.com IP as a test
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'country' => $test_country,
        'max' => 3
    ];
    
    // Log the request parameters
    echo "Request parameters: " . print_r($data, true) . "\n";
    
    // Create query string
    $query_params = http_build_query($data);
    $get_url = $api_url . '?' . $query_params;
    
    echo "Full request URL: $get_url\n";
    
    // Make the API call
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $get_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ],
        CURLOPT_VERBOSE        => true,
        CURLOPT_HEADER         => true
    ]);
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    echo "\nResponse Headers:\n$headers\n";
    
    if ($body) {
        $data = json_decode($body, true);
        if ($data) {
            echo "\nResponse body (parsed):\n";
            
            // Check for offers and their country specificity
            if (isset($data['offers']) && is_array($data['offers'])) {
                echo "Number of offers: " . count($data['offers']) . "\n";
                
                // Output country for each offer to see if they match our requested country
                foreach ($data['offers'] as $index => $offer) {
                    echo "Offer #" . ($index+1) . ":\n";
                    echo "  ID: " . ($offer['offerid'] ?? 'Unknown') . "\n";
                    echo "  Name: " . ($offer['name'] ?? 'Unknown') . "\n";
                    echo "  Country: " . ($offer['country'] ?? 'Not specified') . "\n";
                    echo "  Countries (array if present): " . (isset($offer['countries']) ? implode(',', $offer['countries']) : 'Not specified') . "\n";
                    echo "\n";
                }
            } else {
                echo "No offers array in response or invalid structure\n";
            }
        } else {
            echo "\nFailed to parse response as JSON. Raw response:\n$body\n";
        }
    } else {
        echo "\nEmpty response body\n";
    }
    
    curl_close($ch);
} else {
    echo "Cannot test API without a valid API key\n";
}

echo "\nDONE";
?>