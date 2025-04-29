<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

echo "INDIAN IP FALLBACK TEST\n";
echo "=====================\n\n";

// 1. Test country detection
echo "1. Detecting country...\n";
$country = detectUserCountry();
echo "Detected country: $country\n\n";

// 2. Test getOffers with the detected country
echo "2. Testing getOffers with detected country...\n";
$offers = getOffers();
echo "API response status: " . ($offers['status'] ?? 'unknown') . "\n";

if (isset($offers['status']) && $offers['status'] === 'success' && isset($offers['offers']['offers'])) {
    $offerList = $offers['offers']['offers'];
    echo "Number of offers returned: " . count($offerList) . "\n\n";
    
    if (count($offerList) > 0) {
        echo "First 3 offers details:\n";
        $counter = 0;
        foreach ($offerList as $offer) {
            if ($counter >= 3) break;
            echo "\nOffer #" . ($counter + 1) . ":\n";
            echo "- ID: " . ($offer['offerid'] ?? 'Unknown') . "\n";
            echo "- Name: " . ($offer['name'] ?? 'Unknown') . "\n";
            echo "- Country: " . ($offer['country'] ?? 'Unknown') . "\n";
            echo "- Payout: " . ($offer['payout'] ?? 'Unknown') . "\n";
            $counter++;
        }
    }
} else {
    echo "Error or no offers: " . ($offers['message'] ?? 'Unknown error') . "\n\n";
    echo "Full response: " . print_r($offers, true) . "\n";
}

echo "\nTEST COMPLETED";
?>