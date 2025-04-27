<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

// Get the current user
$user = $auth->getUser();

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get action and offer_id from POST data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$offer_id = isset($_POST['offer_id']) ? (int)$_POST['offer_id'] : 0;

// Validate inputs
if (empty($action) || empty($offer_id)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

// Debug log
error_log("Processing task action: " . $action . " for offer ID: " . $offer_id . ", user ID: " . $user['id']);

// Process the action
$result = [];
switch ($action) {
    case 'cancel':
        error_log("Calling cancelTask function");
        $result = cancelTask($user['id'], $offer_id);
        error_log("Cancel task result: " . json_encode($result));
        break;
    case 'resume':
        error_log("Calling resumeTask function");
        $result = resumeTask($user['id'], $offer_id);
        error_log("Resume task result: " . json_encode($result));
        break;
    default:
        $result = ['status' => 'error', 'message' => 'Invalid action'];
        break;
}

// Update task progress in UI
if ($result['status'] === 'success') {
    // Get updated task progress
    $taskProgress = getTaskProgress($user['id']);
    $result['task_progress'] = $taskProgress;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
exit;