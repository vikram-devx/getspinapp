<?php
/**
 * AJAX request handlers
 */

require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';

// Check if user is logged in and is an admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = [
    'success' => false,
    'message' => 'No action specified'
];

switch ($action) {
    case 'create_test_notification':
        // Create a test notification
        $notification_id = createAdminNotification(
            'Test Notification',
            'This is a test notification created at ' . date('Y-m-d H:i:s'),
            'system'
        );
        
        if ($notification_id) {
            $response = [
                'success' => true,
                'message' => 'Test notification created successfully',
                'notification_id' => $notification_id
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to create test notification'
            ];
        }
        break;
        
    case 'mark_notification_read':
        $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
        if ($notification_id > 0) {
            $result = markNotificationAsRead($notification_id);
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Notification marked as read'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to mark notification as read'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Invalid notification ID'
            ];
        }
        break;
        
    case 'get_unread_count':
        $count = getUnreadNotificationsCount();
        $response = [
            'success' => true,
            'count' => $count
        ];
        break;

    default:
        $response = [
            'success' => false,
            'message' => 'Invalid action: ' . $action
        ];
        break;
}

// Set response header to JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>