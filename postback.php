<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

/*
* Based on URL with at least the following variables:
* 
* https://example.com?postback.php?datetime={datetime}&offer_id={offer_id}&payout={payout}&ip={session_ip}&aff_sub4={aff_sub4}&aff_sub5={aff_sub5}
* 
* For our implementation:
* - aff_sub4 will be used for user_id
* - aff_sub5 can be used for token or additional tracking info
*/

// Log postback request for debugging (optional)
$log_file = 'postback_log.txt';
$log_data = date('Y-m-d H:i:s') . ' - ' . print_r($_GET, true) . ' - ' . print_r($_SERVER, true) . "\n\n";
file_put_contents($log_file, $log_data, FILE_APPEND);

// Extract parameters from request
$datetime = isset($_GET['datetime']) ? $_GET['datetime'] : date('Y-m-d H:i:s');
$offer_id = isset($_GET['offer_id']) ? $_GET['offer_id'] : '';
$payout = isset($_GET['payout']) ? (float)$_GET['payout'] : 0;
$ip = isset($_GET['ip']) ? $_GET['ip'] : '';
$user_id = isset($_GET['aff_sub4']) ? (int)$_GET['aff_sub4'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
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
