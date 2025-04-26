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

// Get parameters from request
$offer_id = isset($_GET['offer_id']) ? $_GET['offer_id'] : null;
$progress = isset($_GET['progress']) ? (int)$_GET['progress'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : 'in_progress';
$message = isset($_GET['message']) ? $_GET['message'] : 'Task in progress...';

// Validate parameters
if (!$offer_id || $progress === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters (offer_id, progress)'
    ]);
    exit;
}

// Validate status
$allowed_statuses = ['started', 'in_progress', 'completed', 'failed'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'in_progress';
}

// Validate progress percentage
if ($progress < 0) $progress = 0;
if ($progress > 100) $progress = 100;

// Calculate estimated time remaining (in seconds) based on progress
$estimated_time = 0;
if ($progress < 100 && $status !== 'completed' && $status !== 'failed') {
    // Simulate some time remaining - less as progress increases
    $estimated_time = (int)(300 * (100 - $progress) / 100);
}

// Update task progress
$result = trackTaskProgress(
    $user_id,
    $offer_id,
    $status,
    $progress,
    $message,
    $estimated_time
);

if ($result) {
    // If progress is 100%, mark task as completed
    if ($progress >= 100 && $status !== 'failed') {
        // Simulate a random payout between 0.5 and 3.0 for demo purposes
        $payout = round(mt_rand(50, 300) / 100, 2);
        
        // Complete the offer and award points
        completeUserOffer($user_id, $offer_id, $payout);
        
        // Update status to completed
        trackTaskProgress(
            $user_id,
            $offer_id,
            'completed',
            100,
            'Task completed successfully! Points credited to your account.',
            0
        );
    }
    
    // Return updated progress
    $progress_data = getTaskProgress($user_id, $offer_id);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Progress updated',
        'progress' => $progress_data
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update progress'
    ]);
}
?>