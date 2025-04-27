<?php
// Include required files for API access
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get database connection to check for API key
$db = Database::getInstance();
$conn = $db->getConnection();

// Get API settings
$settings = [];
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    echo "Error retrieving API settings: " . $e->getMessage();
}

// Check if API key is configured
$api_key = isset($settings['ogads_api_key']) && !empty($settings['ogads_api_key']) 
    ? $settings['ogads_api_key'] 
    : OGADS_API_KEY;

// Fetch CPI offers specifically
$ctype = 'cpi'; // Specifically request CPI offers
$max_offers = 5;
$offers_result = getOffers(null, null, $ctype, $max_offers);

// Debug formatting function
function formatArray($arr, $indent = 0) {
    $result = "";
    foreach ($arr as $key => $value) {
        $result .= str_repeat("  ", $indent) . $key . ": ";
        if (is_array($value)) {
            $result .= "\n" . formatArray($value, $indent + 1);
        } else {
            $result .= $value . "\n";
        }
    }
    return $result;
}

// Output headers for formatting
header("Content-Type: text/plain");

// Display the results
echo "API Request Status: " . $offers_result['status'] . "\n";
echo "Offer Type Requested: CPI (Cost Per Install)\n\n";

if ($offers_result['status'] === 'success' && isset($offers_result['offers'])) {
    // Check the structure to navigate correctly
    if (isset($offers_result['offers']['offers']) && is_array($offers_result['offers']['offers'])) {
        $offers = $offers_result['offers']['offers'];
    } elseif (is_array($offers_result['offers'])) {
        $offers = $offers_result['offers'];
    } else {
        $offers = [];
    }
    
    echo "Number of CPI offers: " . count($offers) . "\n\n";
    
    // Display each offer's fields in detail
    foreach ($offers as $index => $offer) {
        echo "===== CPI OFFER #" . ($index + 1) . " =====\n";
        
        // Output specific fields we're interested in first
        $important_fields = ['name', 'name_short', 'description', 'adcopy', 'instructions', 'requirements', 'payout', 'ctype', 'type'];
        echo "--- IMPORTANT FIELDS ---\n";
        foreach ($important_fields as $field) {
            if (isset($offer[$field])) {
                echo $field . ": " . $offer[$field] . "\n";
            }
        }
        
        echo "\n--- ALL FIELDS ---\n";
        // Output all available fields
        foreach ($offer as $field => $value) {
            echo $field . ": ";
            if (is_array($value)) {
                echo "\n" . formatArray($value, 1);
            } else {
                echo $value . "\n";
            }
        }
        
        echo "\n\n";
    }
    
    // Check if we got any CPI offers
    $found_cpi = false;
    foreach ($offers as $offer) {
        if (isset($offer['ctype']) && strtolower($offer['ctype']) === 'cpi') {
            $found_cpi = true;
            break;
        }
    }
    
    if (!$found_cpi) {
        echo "No CPI offers were found in the API response.\n";
    }
} else {
    // If API request failed or no offers, show sample CPI data from the project
    echo "API request failed or returned no CPI offers. Using sample CPI data instead:\n\n";
    
    $sample_offers = [
        [
            'id' => 'offer1',
            'offerid' => 'offer1',
            'name' => 'Final Fantasy XV',
            'name_short' => 'Final Fantasy XV',
            'description' => 'Be the hero of your own Final Fantasy XV adventure!',
            'adcopy' => 'Download, install and complete the tutorial to unlock this content.',
            'requirements' => 'Download, install, and complete the tutorial of the app.',
            'instructions' => 'Install the app from the link, open it, and follow the in-app tutorial.',
            'payout' => 2.5,
            'type' => 'cpi',
            'ctype' => 'cpi'
        ],
        [
            'id' => 'offer3',
            'offerid' => 'offer3',
            'name' => 'Castle Clash',
            'name_short' => 'Castle Clash',
            'description' => 'Build and battle your way to glory in Castle Clash!',
            'adcopy' => 'Download and install this app then run it for 30 seconds to unlock this content.',
            'requirements' => 'Install the app and play for at least 5 minutes.',
            'instructions' => 'Download the game, create an account, and complete the first battle tutorial.',
            'payout' => 3.0,
            'type' => 'cpi',
            'ctype' => 'cpi'
        ]
    ];
    
    foreach ($sample_offers as $index => $offer) {
        echo "===== SAMPLE CPI OFFER #" . ($index + 1) . " =====\n";
        foreach ($offer as $field => $value) {
            echo $field . ": " . $value . "\n";
        }
        echo "\n\n";
    }
}
?>