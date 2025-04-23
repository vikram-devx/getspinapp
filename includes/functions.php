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
    
    // Setup API request data
    $data = [
        'ip' => $ip,                   // Client IP (REQUIRED)
        'user_agent' => $user_agent,   // Client User Agent (REQUIRED)
    ];
    
    // Add optional parameters if provided
    if ($offer_type) {
        $data['ctype'] = $offer_type;
    }
    
    if ($max) {
        $data['max'] = $max;
    }
    
    if ($min) {
        $data['min'] = $min;
    }
    
    // Create URL with query parameters
    $url = OGADS_API_URL . '?' . http_build_query($data);
    
    // Set up cURL
    $ch = curl_init();
    
    // Set CURL options
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . OGADS_API_KEY,
            'Accept: application/json'
        ],
    ]);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'status' => 'error',
            'message' => 'API Request Error: ' . $error
        ];
    }
    
    curl_close($ch);
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    // Check if response is valid
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid API Response: " . $response);
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
    
    try {
        // Get total earnings
        $stmt = $conn->prepare("SELECT SUM(points) as total_earned FROM transactions WHERE user_id = :user_id AND type = 'earn'");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_earned = $result['total_earned'] ?: 0;
        
        // Get total spent
        $stmt = $conn->prepare("SELECT SUM(points) as total_spent FROM transactions WHERE user_id = :user_id AND type = 'spend'");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_spent = $result['total_spent'] ?: 0;
        
        // Get completed offers count
        $stmt = $conn->prepare("SELECT COUNT(*) as completed_offers FROM user_offers WHERE user_id = :user_id AND completed = 1");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $completed_offers = $result['completed_offers'] ?: 0;
        
        // Get redeemed rewards count
        $stmt = $conn->prepare("SELECT COUNT(*) as redeemed_rewards FROM redemptions WHERE user_id = :user_id AND status = 'completed'");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $redeemed_rewards = $result['redeemed_rewards'] ?: 0;
        
        // Get current points
        $stmt = $conn->prepare("SELECT points FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $current_points = $result['points'] ?: 0;
        
        return [
            'total_earned' => $total_earned,
            'total_spent' => $total_spent,
            'completed_offers' => $completed_offers,
            'redeemed_rewards' => $redeemed_rewards,
            'current_points' => $current_points
        ];
    } catch (PDOException $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        return [
            'total_earned' => 0,
            'total_spent' => 0,
            'completed_offers' => 0,
            'redeemed_rewards' => 0,
            'current_points' => 0
        ];
    }
}

// Function to get available rewards
function getRewards() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $query = "SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC";
        $result = $db->query($query);
        
        $rewards = [];
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $rewards[] = $row;
            }
        }
        
        return $rewards;
    } catch (PDOException $e) {
        error_log("Error getting rewards: " . $e->getMessage());
        return [];
    }
}

// Function to get reward by ID
function getRewardById($reward_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = :reward_id");
        $stmt->bindValue(':reward_id', $reward_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error getting reward by ID: " . $e->getMessage());
        return null;
    }
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
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Create redemption record
        $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_used, status) VALUES (:user_id, :reward_id, :points_used, 'pending')");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':reward_id', $reward_id);
        $stmt->bindValue(':points_used', $reward['points_required']);
        $stmt->execute();
        $redemption_id = $conn->lastInsertId();
        
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
        $conn->rollBack();
        
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
    
    try {
        $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = $row;
        }
        
        return $transactions;
    } catch (PDOException $e) {
        error_log("Error getting user transactions: " . $e->getMessage());
        return [];
    }
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
