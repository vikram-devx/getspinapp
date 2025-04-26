<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Authenticate user
$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$current_user = $auth->getUser();
$user_id = $current_user['id'];

// Get the offer ID from request
$offer_id = isset($_GET['offer_id']) ? $_GET['offer_id'] : null;

// Get task progress
$progress = getTaskProgress($user_id, $offer_id);

// Check if we found progress data
if (!$progress) {
    // If no specific offer ID was requested, return all tasks
    if (!$offer_id) {
        echo json_encode([
            'status' => 'success',
            'message' => 'No tasks in progress',
            'progress' => []
        ]);
    } else {
        // If a specific offer was requested but not found
        echo json_encode([
            'status' => 'error',
            'message' => 'No progress data found for this task',
            'progress' => null
        ]);
    }
    exit;
}

// Return progress data
echo json_encode([
    'status' => 'success',
    'progress' => $progress
]);
?>