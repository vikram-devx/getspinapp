<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

echo "COUNTRY DETECTION TEST\n";
echo "=====================\n\n";

// 1. Test country detection
echo "1. Detecting country...\n";
$country = detectUserCountry();
echo "Detected country: $country\n\n";

// 2. Test getOffers with the detected country
echo "2. Testing getOffers with country parameter...\n";
$offers = getOffers(null, null, null, 3, null, $country);
echo "API response status: " . $offers['status'] . "\n";

if ($offers['status'] === 'success' && isset($offers['offers']['offers'])) {
    $offerList = $offers['offers']['offers'];
    echo "Number of offers returned: " . count($offerList) . "\n\n";
    
    if (count($offerList) > 0) {
        $firstOffer = $offerList[0];
        echo "First offer details:\n";
        echo "- ID: " . ($firstOffer['offerid'] ?? 'Unknown') . "\n";
        echo "- Name: " . ($firstOffer['name'] ?? 'Unknown') . "\n";
        echo "- Country: " . ($firstOffer['country'] ?? 'Unknown') . "\n";
        echo "- Countries (if present): " . (isset($firstOffer['countries']) ? implode(',', $firstOffer['countries']) : 'Not provided') . "\n\n";
    }
} else {
    echo "No offers or error: " . ($offers['message'] ?? 'Unknown error') . "\n\n";
}

// 3. Explicitly test with different countries
echo "3. Testing with various country codes:\n";
$testCountries = ['US', 'GB', 'CA', 'AU', 'DE', 'FR'];

foreach ($testCountries as $testCountry) {
    echo "\nTesting with country: $testCountry\n";
    $countryOffers = getOffers(null, null, null, 2, null, $testCountry);
    
    if ($countryOffers['status'] === 'success' && isset($countryOffers['offers']['offers'])) {
        $countryOfferList = $countryOffers['offers']['offers'];
        echo "Number of offers: " . count($countryOfferList) . "\n";
        
        if (count($countryOfferList) > 0) {
            $offer = $countryOfferList[0];
            echo "First offer country: " . ($offer['country'] ?? 'Unknown') . "\n";
            echo "First offer name: " . ($offer['name'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "Error or no offers for $testCountry\n";
    }
}

echo "\nTEST COMPLETED";
?>