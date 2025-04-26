<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

/*
* OGAds Postback URL format:
* 
* http://example.com/postback.php?user_id={aff_sub4}&offer_id={offer_id}&payout={payout}&ip={session_ip}&token=SECRET_TOKEN
* 
* or:
* 
* http://example.com/postback.php?datetime={datetime}&offer_id={offer_id}&payout={payout}&ip={session_ip}&aff_sub4={aff_sub4}&aff_sub5={aff_sub5}
* 
* Parameters explained:
* - {offer_id} - ID of the offer that was completed
* - {payout} - Amount paid to affiliate for conversion
* - {session_ip} - IP address that started the tracking session
* - {aff_sub4} - We use this for user_id (who completed the offer)
* - {aff_sub5} - We can use this for additional tracking (optional)
* - {datetime} - When the conversion was reported
* 
* Our implementation:
* - aff_sub4 contains the user_id
* - aff_sub5 can be used for token or additional tracking info
*/

// Log postback request for debugging (always helpful for postbacks)
$log_file = 'postback_log.txt';
$log_data = date('Y-m-d H:i:s') . ' - ' . print_r($_GET, true) . ' - ' . print_r($_SERVER, true) . "\n\n";
file_put_contents($log_file, $log_data, FILE_APPEND);

// Extract parameters from request
$datetime = isset($_GET['datetime']) ? $_GET['datetime'] : date('Y-m-d H:i:s');
$offer_id = isset($_GET['offer_id']) ? $_GET['offer_id'] : '';
$payout = isset($_GET['payout']) ? (float)$_GET['payout'] : 0;
$ip = isset($_GET['ip']) ? $_GET['ip'] : '';

// User ID can come from several possible fields based on how OGAds formats their postback
// We check aff_sub4 first (preferred), then aff_sub, then user_id
$user_id = 0;
if (isset($_GET['aff_sub4']) && !empty($_GET['aff_sub4'])) {
    $user_id = (int)$_GET['aff_sub4'];
} elseif (isset($_GET['aff_sub']) && !empty($_GET['aff_sub'])) {
    $user_id = (int)$_GET['aff_sub'];
} elseif (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
}

$token = isset($_GET['aff_sub5']) ? $_GET['aff_sub5'] : (isset($_GET['token']) ? $_GET['token'] : '');

// Log the received data
error_log("Postback received: User ID: $user_id, Offer ID: $offer_id, Payout: $payout");

// Validate required parameters
if (empty($user_id) || empty($offer_id) || empty($payout)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters',
        'received' => [
            'user_id' => $user_id,
            'offer_id' => $offer_id,
            'payout' => $payout
        ]
    ]);
    exit;
}

// Validate token (simple token validation for example purposes)
if (!validatePostbackToken($user_id, $token)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid token'
    ]);
    exit;
}

// Complete the offer
$result = completeUserOffer($user_id, $offer_id, $payout);

// Send response
if ($result['status'] === 'success') {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Offer completed successfully',
        'points_earned' => $result['points_earned']
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $result['message']
    ]);
}
?>
