<?php
require_once 'db.php';
require_once 'config.php';

// Function to get available offers from OGAds API
function getOffers($ip = null, $user_agent = null, $offer_type = null, $max = null, $min = null) {
    // If IP is not provided, get the user's IP
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // If user agent is not provided, get the user's user agent
    if (!$user_agent) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
    }
    
    // Build the API URL
    $api_url = OGADS_API_URL . '?ip=' . urlencode($ip) . '&user_agent=' . urlencode($user_agent);
    
    // Add optional parameters if provided
    if ($offer_type) {
        $api_url .= '&ctype=' . urlencode($offer_type);
    }
    
    if ($max) {
        $api_url .= '&max=' . urlencode($max);
    }
    
    if ($min) {
        $api_url .= '&min=' . urlencode($min);
    }
    
    // Set up cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . OGADS_API_KEY,
        'Accept: application/json'
    ]);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return [
            'status' => 'error',
            'message' => 'API Request Error: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    // Check if response is valid
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        return [
            'status' => 'error',
            'message' => 'Invalid API Response'
        ];
    }
    
    return [
        'status' => 'success',
        'offers' => $data
    ];
}

// Function to get user stats
function getUserStats($user_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get total earnings
    $stmt = $conn->prepare("SELECT SUM(points) as total_earned FROM transactions WHERE user_id = ? AND type = 'earn'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_earned = $result->fetch_assoc()['total_earned'] ?: 0;
    
    // Get total spent
    $stmt = $conn->prepare("SELECT SUM(points) as total_spent FROM transactions WHERE user_id = ? AND type = 'spend'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_spent = $result->fetch_assoc()['total_spent'] ?: 0;
    
    // Get completed offers count
    $stmt = $conn->prepare("SELECT COUNT(*) as completed_offers FROM user_offers WHERE user_id = ? AND completed = 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $completed_offers = $result->fetch_assoc()['completed_offers'] ?: 0;
    
    // Get redeemed rewards count
    $stmt = $conn->prepare("SELECT COUNT(*) as redeemed_rewards FROM redemptions WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $redeemed_rewards = $result->fetch_assoc()['redeemed_rewards'] ?: 0;
    
    // Get current points
    $stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_points = $result->fetch_assoc()['points'] ?: 0;
    
    return [
        'total_earned' => $total_earned,
        'total_spent' => $total_spent,
        'completed_offers' => $completed_offers,
        'redeemed_rewards' => $redeemed_rewards,
        'current_points' => $current_points
    ];
}

// Function to get available rewards
function getRewards() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC";
    $result = $db->query($query);
    
    $rewards = [];
    while ($row = $result->fetch_assoc()) {
        $rewards[] = $row;
    }
    
    return $rewards;
}

// Function to get reward by ID
function getRewardById($reward_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

// Function to redeem a reward
function redeemReward($user_id, $reward_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $auth = new Auth();
    
    // Get reward details
    $reward = getRewardById($reward_id);
    
    if (!$reward) {
        return [
            'status' => 'error',
            'message' => 'Reward not found'
        ];
    }
    
    // Check if user has enough points
    $user = $auth->getUser();
    
    if ($user['points'] < $reward['points_required']) {
        return [
            'status' => 'error',
            'message' => 'Not enough points to redeem this reward'
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create redemption record
        $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_used, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iii", $user_id, $reward_id, $reward['points_required']);
        $stmt->execute();
        $redemption_id = $conn->insert_id;
        
        // Deduct points from user
        $description = "Redeemed " . $reward['name'];
        $auth->updatePoints($user_id, $reward['points_required'], 'spend', $description, $redemption_id, 'redemption');
        
        // Commit transaction
        $conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Reward redeemed successfully',
            'redemption_id' => $redemption_id
        ];
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        return [
            'status' => 'error',
            'message' => 'Failed to redeem reward: ' . $e->getMessage()
        ];
    }
}

// Function to get user transactions
function getUserTransactions($user_id, $limit = 10) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

// Function to get user redemptions
function getUserRedemptions($user_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT r.*, rw.name, rw.points_required 
        FROM redemptions r
        JOIN rewards rw ON r.reward_id = rw.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $redemptions = [];
    while ($row = $result->fetch_assoc()) {
        $redemptions[] = $row;
    }
    
    return $redemptions;
}

// Function to generate a tracking URL for an offer
function generateTrackingUrl($offer_id, $user_id) {
    $base_url = APP_URL . "/tasks.php?action=track&offer_id={$offer_id}&user_id={$user_id}";
    return $base_url;
}

// Function to record user offer attempt
function recordOfferAttempt($user_id, $offer_id, $ip_address) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if the user has already attempted this offer
    $stmt = $conn->prepare("SELECT id FROM user_offers WHERE user_id = ? AND offer_id = ?");
    $stmt->bind_param("is", $user_id, $offer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User has already attempted this offer
        return [
            'status' => 'error',
            'message' => 'You have already attempted this offer'
        ];
    }
    
    // Record the offer attempt
    $stmt = $conn->prepare("INSERT INTO user_offers (user_id, offer_id, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $offer_id, $ip_address);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Offer attempt recorded',
            'user_offer_id' => $stmt->insert_id
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to record offer attempt: ' . $stmt->error
        ];
    }
}

// Function to generate a unique postback URL for tracking offer completions
function generatePostbackUrl($user_id) {
    $token = md5(uniqid($user_id, true));
    $postback_url = APP_URL . "/postback.php?user_id={$user_id}&offer_id={offer_id}&payout={payout}&ip={session_ip}&token={$token}";
    
    return $postback_url;
}

// Validate postback token
function validatePostbackToken($user_id, $token) {
    // In a real application, you would store and validate tokens
    // For this example, we'll just return true
    return true;
}

// Function to complete user offer
function completeUserOffer($user_id, $offer_id, $payout) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $auth = new Auth();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Calculate points based on payout
        $points = (int)($payout * POINTS_CONVERSION_RATE);
        
        // Update user offer record
        $stmt = $conn->prepare("
            UPDATE user_offers 
            SET completed = 1, points_earned = ?, completed_at = NOW() 
            WHERE user_id = ? AND offer_id = ?
        ");
        $stmt->bind_param("iis", $points, $user_id, $offer_id);
        $stmt->execute();
        
        // Check if any rows were affected
        if ($stmt->affected_rows === 0) {
            // Create a record if it doesn't exist
            $stmt = $conn->prepare("
                INSERT INTO user_offers (user_id, offer_id, completed, points_earned, completed_at) 
                VALUES (?, ?, 1, ?, NOW())
            ");
            $stmt->bind_param("isi", $user_id, $offer_id, $points);
            $stmt->execute();
        }
        
        $user_offer_id = $stmt->insert_id;
        
        // Add points to user
        $description = "Completed offer: " . $offer_id;
        $auth->updatePoints($user_id, $points, 'earn', $description, $user_offer_id, 'offer');
        
        // Commit transaction
        $conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Offer completed successfully',
            'points_earned' => $points
        ];
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        
        return [
            'status' => 'error',
            'message' => 'Failed to complete offer: ' . $e->getMessage()
        ];
    }
}

// Admin Functions

// Get all users
function getAllUsers() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM users ORDER BY id DESC";
    $result = $db->query($query);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Get all rewards
function getAllRewards() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM rewards ORDER BY points_required ASC";
    $result = $db->query($query);
    
    $rewards = [];
    while ($row = $result->fetch_assoc()) {
        $rewards[] = $row;
    }
    
    return $rewards;
}

// Add a new reward
function addReward($name, $description, $points_required, $is_active = 1) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO rewards (name, description, points_required, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $description, $points_required, $is_active);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Reward added successfully',
            'reward_id' => $stmt->insert_id
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to add reward: ' . $stmt->error
        ];
    }
}

// Update a reward
function updateReward($reward_id, $name, $description, $points_required, $is_active) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("UPDATE rewards SET name = ?, description = ?, points_required = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $name, $description, $points_required, $is_active, $reward_id);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Reward updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update reward: ' . $stmt->error
        ];
    }
}

// Delete a reward
function deleteReward($reward_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if any redemptions exist for this reward
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM redemptions WHERE reward_id = ?");
    $stmt->bind_param("i", $reward_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        return [
            'status' => 'error',
            'message' => 'Cannot delete reward: It has been redeemed by users'
        ];
    }
    
    $stmt = $conn->prepare("DELETE FROM rewards WHERE id = ?");
    $stmt->bind_param("i", $reward_id);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Reward deleted successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to delete reward: ' . $stmt->error
        ];
    }
}

// Get all redemptions
function getAllRedemptions() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "
        SELECT r.*, u.username, rw.name as reward_name 
        FROM redemptions r
        JOIN users u ON r.user_id = u.id
        JOIN rewards rw ON r.reward_id = rw.id
        ORDER BY r.created_at DESC
    ";
    $result = $db->query($query);
    
    $redemptions = [];
    while ($row = $result->fetch_assoc()) {
        $redemptions[] = $row;
    }
    
    return $redemptions;
}

// Update redemption status
function updateRedemptionStatus($redemption_id, $status) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("UPDATE redemptions SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $redemption_id);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Redemption status updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update redemption status: ' . $stmt->error
        ];
    }
}

// Get all transactions
function getAllTransactions() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "
        SELECT t.*, u.username 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 1000
    ";
    $result = $db->query($query);
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    return $transactions;
}

// Format points as currency
function formatPoints($points) {
    return number_format($points);
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}
?>
