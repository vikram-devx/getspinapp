<?php
require_once 'db.php';
require_once 'config.php';

// Function to get available offers from OGAds API
function getOffers($ip = null, $user_agent = null, $offer_type = null, $max = null, $min = null) {
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get API settings from database
    $settings = [];
    try {
        $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Error retrieving API settings: " . $e->getMessage());
    }
    
    // Use settings from database or fallback to constants
    $api_key = isset($settings['ogads_api_key']) && !empty($settings['ogads_api_key']) 
        ? $settings['ogads_api_key'] 
        : OGADS_API_KEY;
    
    $api_url = OGADS_API_URL; // Always use the URL from constants
    
    // If IP is not provided, get the user's IP
    if (!$ip) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Check if the IP is a private/local IP address
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            // This is a private IP, use a public IP for testing
            // Using a common test IP (example.com's IP) to ensure we get geo data
            $ip = '93.184.216.34'; 
            error_log("Using public IP for OGAds API request: " . $ip);
        }
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
    
    // Use settings from database if available
    if (!$offer_type && isset($settings['ogads_ctype']) && !empty($settings['ogads_ctype'])) {
        $data['ctype'] = (int)$settings['ogads_ctype'];
    } elseif ($offer_type) {
        $data['ctype'] = $offer_type;
    }
    
    if (!$max && isset($settings['ogads_max_offers']) && !empty($settings['ogads_max_offers'])) {
        $data['max'] = (int)$settings['ogads_max_offers'];
    } elseif ($max) {
        $data['max'] = $max;
    }
    
    if (!$min && isset($settings['ogads_min_offers']) && !empty($settings['ogads_min_offers'])) {
        $data['min'] = (int)$settings['ogads_min_offers'];
    } elseif ($min) {
        $data['min'] = $min;
    }
    
    // Log the API request parameters for debugging
    error_log("OGAds API Request Parameters: " . print_r($data, true));
    
    // Set up cURL with GET method as required by OGAds API v2
    $ch = curl_init();
    
    // Create query string for GET request
    $query_params = http_build_query($data);
    $get_url = $api_url . '?' . $query_params;
    
    // Set CURL options for GET request
    curl_setopt_array($ch, [
        CURLOPT_URL            => $get_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,  // Enable SSL certificate verification
        CURLOPT_SSL_VERIFYHOST => 2,     // Verify the certificate's name against host
        CURLOPT_TIMEOUT        => 30,    // Set a reasonable timeout
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
    
    // Log the full API response structure for debugging
    error_log("Full API Response Structure: " . print_r($data, true));
    
    // Check if the API response indicates success
    if (isset($data['success']) && $data['success'] === true) {
        return [
            'status' => 'success',
            'offers' => $data
        ];
    } else if (isset($data['error']) && !empty($data['error'])) {
        return [
            'status' => 'error',
            'message' => 'API Error: ' . $data['error']
        ];
    } else {
        return [
            'status' => 'success',
            'offers' => $data
        ];
    }
}

// Function to get specific offer details from OGAds API
function getOfferDetails($offer_id) {
    error_log("Getting offer details for offer ID: " . $offer_id);
    
    if (empty($offer_id)) {
        error_log("Error: Empty offer ID");
        return [
            'status' => 'error',
            'message' => 'Invalid offer ID'
        ];
    }
    
    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get API settings from database
    $settings = [];
    try {
        $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ogads_%'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Error retrieving API settings: " . $e->getMessage());
    }
    
    // Use settings from database or fallback to constants
    $api_key = isset($settings['ogads_api_key']) && !empty($settings['ogads_api_key']) 
        ? $settings['ogads_api_key'] 
        : OGADS_API_KEY;
    
    $api_url = OGADS_API_URL; // Always use the URL from constants
    
    // Get the IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Check if the IP is a private/local IP address
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        // This is a private IP, use a public IP for testing
        // Using a common test IP (example.com's IP) to ensure we get geo data
        $ip = '93.184.216.34'; 
        error_log("Using public IP for OGAds API offer details request: " . $ip);
    }
    
    // For v2 API, we need to use GET method as POST is not supported
    $data = [
        'offer_id' => $offer_id,
        'ip' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];
    
    // Set up cURL with GET method
    $ch = curl_init();
    
    // Create query string for GET request
    $query_params = http_build_query($data);
    $get_url = $api_url . '?' . $query_params;
    
    // Set CURL options for GET request
    curl_setopt_array($ch, [
        CURLOPT_URL            => $get_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => true,  // Enable SSL certificate verification
        CURLOPT_SSL_VERIFYHOST => 2,     // Verify the certificate's name against host
        CURLOPT_TIMEOUT        => 30,    // Set a reasonable timeout
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
        'offer' => $data
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
        
        // Get referral count
        $stmt = $conn->prepare("SELECT COUNT(*) as total_referrals FROM referrals WHERE referrer_id = :user_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_referrals = $result['total_referrals'] ?: 0;
        
        // Get referral income
        $stmt = $conn->prepare("SELECT SUM(points) as referral_income FROM transactions 
                               WHERE user_id = :user_id AND type = 'earn' AND description LIKE 'Referral bonus%'");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $referral_income = $result['referral_income'] ?: 0;
        
        return [
            'total_earned' => $total_earned,
            'total_spent' => $total_spent,
            'completed_offers' => $completed_offers,
            'redeemed_rewards' => $redeemed_rewards,
            'current_points' => $current_points,
            'total_referrals' => $total_referrals,
            'referral_income' => $referral_income
        ];
    } catch (PDOException $e) {
        error_log("Error getting user stats: " . $e->getMessage());
        return [
            'total_earned' => 0,
            'total_spent' => 0,
            'completed_offers' => 0,
            'redeemed_rewards' => 0,
            'current_points' => 0,
            'total_referrals' => 0,
            'referral_income' => 0
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
function redeemReward($user_id, $reward_id, $redemption_details = null) {
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
        // Create redemption record without transaction initially
        if ($redemption_details) {
            $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_used, status, redemption_details) VALUES (:user_id, :reward_id, :points_used, 'pending', :redemption_details)");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':reward_id', $reward_id);
            $stmt->bindValue(':points_used', $reward['points_required']);
            $stmt->bindValue(':redemption_details', $redemption_details);
        } else {
            $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_used, status) VALUES (:user_id, :reward_id, :points_used, 'pending')");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':reward_id', $reward_id);
            $stmt->bindValue(':points_used', $reward['points_required']);
        }
        $stmt->execute();
        $redemption_id = $conn->lastInsertId();
        
        // Deduct points from user
        $description = "Redeemed " . $reward['name'];
        $result = $auth->updatePoints($user_id, $reward['points_required'], 'spend', $description, $redemption_id, 'redemption');
        
        if ($result) {
            return [
                'status' => 'success',
                'message' => 'Reward redeemed successfully',
                'redemption_id' => $redemption_id
            ];
        } else {
            // If point update failed, delete the redemption record
            $deleteStmt = $conn->prepare("DELETE FROM redemptions WHERE id = :id");
            $deleteStmt->bindValue(':id', $redemption_id);
            $deleteStmt->execute();
            
            return [
                'status' => 'error',
                'message' => 'Failed to update user points'
            ];
        }
    } catch (Exception $e) {
        error_log("Error in redeemReward: " . $e->getMessage());
        
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
    
    try {
        $stmt = $conn->prepare("
            SELECT r.*, rw.name, rw.points_required 
            FROM redemptions r
            JOIN rewards rw ON r.reward_id = rw.id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
        ");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->execute();
        
        $redemptions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $redemptions[] = $row;
        }
        
        return $redemptions;
    } catch (PDOException $e) {
        error_log("Error getting user redemptions: " . $e->getMessage());
        return [];
    }
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
    
    // Debug logging
    error_log("Recording offer attempt for user: $user_id, offer: $offer_id, IP: $ip_address");
    
    try {
        // Check if the user has already attempted this offer
        $stmt = $conn->prepare("SELECT id FROM user_offers WHERE user_id = :user_id AND offer_id = :offer_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':offer_id', $offer_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // User has already attempted this offer
            // During testing, we'll allow multiple attempts
            error_log("User has already attempted this offer, but allowing it for testing purposes");
            
            // Update the existing record
            $stmt = $conn->prepare("UPDATE user_offers SET completed = 0, completed_at = NULL WHERE user_id = :user_id AND offer_id = :offer_id");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':offer_id', $offer_id);
            $stmt->execute();
            
            return [
                'status' => 'success',
                'message' => 'Offer attempt updated for testing'
            ];
        }
        
        // Record the offer attempt
        $stmt = $conn->prepare("INSERT INTO user_offers (user_id, offer_id, ip_address) VALUES (:user_id, :offer_id, :ip_address)");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':offer_id', $offer_id);
        $stmt->bindValue(':ip_address', $ip_address);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Offer attempt recorded',
                'user_offer_id' => $conn->lastInsertId()
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to record offer attempt'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error recording offer attempt: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to record offer attempt: Database error'
        ];
    }
}

// Function to generate a unique postback URL for tracking offer completions
function generatePostbackUrl($user_id) {
    $token = md5(uniqid($user_id, true));
    
    // Format the postback URL according to OGAds expectations
    // We use aff_sub4 to store the user_id as per OGAds best practices
    $postback_url = APP_URL . "/postback.php?user_id={$user_id}&offer_id={offer_id}&payout={payout}&ip={session_ip}&aff_sub4={$user_id}&token={$token}";
    
    return $postback_url;
}

// Validate postback token
function validatePostbackToken($user_id, $token) {
    // In a real application, you would store and validate tokens
    // For this example, we'll just return true
    return true;
}

// Function to create or update task progress
function trackTaskProgress($user_id, $offer_id, $status, $progress_percent = 0, $message = '', $estimated_time = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Check if a record already exists for this user and offer
        $stmt = $conn->prepare("SELECT id, status FROM task_progress WHERE user_id = :user_id AND offer_id = :offer_id");
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':offer_id', $offer_id);
        $stmt->execute();
        
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE task_progress SET 
                    status = :status, 
                    progress_percent = :progress_percent, 
                    progress_message = :message,
                    estimated_completion_time = :est_time,
                    last_updated = CURRENT_TIMESTAMP";
            
            // If the status is 'completed', set the completion time
            if ($status === 'completed') {
                $sql .= ", completion_time = CURRENT_TIMESTAMP";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':id', $existing['id']);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':progress_percent', $progress_percent);
            $stmt->bindValue(':message', $message);
            $stmt->bindValue(':est_time', $estimated_time);
            $stmt->execute();
            
            return $existing['id'];
        } else {
            // Create new record
            $stmt = $conn->prepare("INSERT INTO task_progress 
                (user_id, offer_id, status, progress_percent, progress_message, estimated_completion_time) 
                VALUES 
                (:user_id, :offer_id, :status, :progress_percent, :message, :est_time)");
            
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':offer_id', $offer_id);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':progress_percent', $progress_percent);
            $stmt->bindValue(':message', $message);
            $stmt->bindValue(':est_time', $estimated_time);
            $stmt->execute();
            
            return $conn->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log("Error tracking task progress: " . $e->getMessage());
        return false;
    }
}

// Function to get task progress for a user
function getTaskProgress($user_id, $offer_id = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $sql = "SELECT tp.*, 
                uo.completed as task_completed,
                CASE 
                    WHEN uo.completed = 1 THEN 100 
                    ELSE tp.progress_percent 
                END as current_progress
                FROM task_progress tp
                LEFT JOIN user_offers uo ON tp.user_id = uo.user_id AND tp.offer_id = uo.offer_id
                WHERE tp.user_id = :user_id";
        
        $params = [':user_id' => $user_id];
        
        if ($offer_id) {
            $sql .= " AND tp.offer_id = :offer_id";
            $params[':offer_id'] = $offer_id;
        }
        
        $sql .= " ORDER BY tp.last_updated DESC";
        
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        if ($offer_id) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error getting task progress: " . $e->getMessage());
        return false;
    }
}

// Function to complete user offer
function completeUserOffer($user_id, $offer_id, $payout) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $auth = new Auth();
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Calculate points based on payout
        $points = (int)($payout * POINTS_CONVERSION_RATE);
        
        // Update user offer record
        $stmt = $conn->prepare("
            UPDATE user_offers 
            SET completed = 1, points_earned = :points, completed_at = datetime('now') 
            WHERE user_id = :user_id AND offer_id = :offer_id
        ");
        $stmt->bindValue(':points', $points);
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':offer_id', $offer_id);
        $stmt->execute();
        
        // Check if any rows were affected (SQLite doesn't have affected_rows, we need to query)
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM user_offers WHERE user_id = :user_id AND offer_id = :offer_id");
        $checkStmt->bindValue(':user_id', $user_id);
        $checkStmt->bindValue(':offer_id', $offer_id);
        $checkStmt->execute();
        $count = (int)$checkStmt->fetchColumn();
        
        if ($count === 0) {
            // Create a record if it doesn't exist
            $stmt = $conn->prepare("
                INSERT INTO user_offers (user_id, offer_id, completed, points_earned, completed_at) 
                VALUES (:user_id, :offer_id, 1, :points, datetime('now'))
            ");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':offer_id', $offer_id);
            $stmt->bindValue(':points', $points);
            $stmt->execute();
            $user_offer_id = $conn->lastInsertId();
        } else {
            // Get the id of the updated record
            $idStmt = $conn->prepare("SELECT id FROM user_offers WHERE user_id = :user_id AND offer_id = :offer_id");
            $idStmt->bindValue(':user_id', $user_id);
            $idStmt->bindValue(':offer_id', $offer_id);
            $idStmt->execute();
            $user_offer_id = $idStmt->fetchColumn();
        }
        
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
        $conn->rollBack();
        error_log("Error completing offer: " . $e->getMessage());
        
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
    
    try {
        $query = "SELECT * FROM users ORDER BY id DESC";
        $result = $db->query($query);
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $row;
            }
        }
        
        return $users;
    } catch (PDOException $e) {
        error_log("Error getting all users: " . $e->getMessage());
        return [];
    }
}

// Get all rewards
function getAllRewards() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $query = "SELECT * FROM rewards ORDER BY points_required ASC";
        $result = $db->query($query);
        
        $rewards = [];
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $rewards[] = $row;
            }
        }
        
        return $rewards;
    } catch (PDOException $e) {
        error_log("Error getting all rewards: " . $e->getMessage());
        return [];
    }
}

// Add a new reward
function addReward($name, $description, $points_required, $is_active = 1) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("INSERT INTO rewards (name, description, points_required, is_active) VALUES (:name, :description, :points_required, :is_active)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':points_required', $points_required);
        $stmt->bindValue(':is_active', $is_active);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Reward added successfully',
                'reward_id' => $conn->lastInsertId()
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to add reward'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error adding reward: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to add reward: Database error'
        ];
    }
}

// Update a reward
function updateReward($reward_id, $name, $description, $points_required, $is_active) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("UPDATE rewards SET name = :name, description = :description, points_required = :points_required, is_active = :is_active WHERE id = :reward_id");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':points_required', $points_required);
        $stmt->bindValue(':is_active', $is_active);
        $stmt->bindValue(':reward_id', $reward_id);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Reward updated successfully'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to update reward'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error updating reward: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to update reward: Database error'
        ];
    }
}

// Delete a reward
function deleteReward($reward_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Check if any redemptions exist for this reward
        $stmt = $conn->prepare("SELECT COUNT(*) FROM redemptions WHERE reward_id = :reward_id");
        $stmt->bindValue(':reward_id', $reward_id);
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();
        
        if ($count > 0) {
            return [
                'status' => 'error',
                'message' => 'Cannot delete reward: It has been redeemed by users'
            ];
        }
        
        $stmt = $conn->prepare("DELETE FROM rewards WHERE id = :reward_id");
        $stmt->bindValue(':reward_id', $reward_id);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Reward deleted successfully'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to delete reward'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error deleting reward: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to delete reward: Database error'
        ];
    }
}

// Get all redemptions
function getAllRedemptions() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $query = "
            SELECT r.*, u.username, rw.name as reward_name 
            FROM redemptions r
            JOIN users u ON r.user_id = u.id
            JOIN rewards rw ON r.reward_id = rw.id
            ORDER BY r.created_at DESC
        ";
        $result = $conn->query($query);
        
        $redemptions = [];
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $redemptions[] = $row;
            }
        }
        
        return $redemptions;
    } catch (PDOException $e) {
        error_log("Error getting all redemptions: " . $e->getMessage());
        return [];
    }
}

// Update redemption status
function updateRedemptionStatus($redemption_id, $status) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("UPDATE redemptions SET status = :status WHERE id = :redemption_id");
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':redemption_id', $redemption_id);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Redemption status updated successfully'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to update redemption status'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error updating redemption status: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to update redemption status: Database error'
        ];
    }
}

// Get all transactions
function getAllTransactions() {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $query = "
            SELECT t.*, u.username 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at DESC
            LIMIT 1000
        ";
        $result = $conn->query($query);
        
        $transactions = [];
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $transactions[] = $row;
            }
        }
        
        return $transactions;
    } catch (PDOException $e) {
        error_log("Error getting all transactions: " . $e->getMessage());
        return [];
    }
}

// Format points as currency
function formatPoints($points) {
    return number_format($points);
}

// Function to get a setting value
function getSetting($key, $default = null) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
        $stmt->bindValue(':key', $key);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['setting_value'];
        } else {
            return $default;
        }
    } catch (PDOException $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return $default;
    }
}

// Function to save a setting
function saveSetting($key, $value) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // Check if the setting already exists
        $stmt = $conn->prepare("SELECT setting_key FROM settings WHERE setting_key = :key");
        $stmt->bindValue(':key', $key);
        $stmt->execute();
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Update existing setting
            $stmt = $conn->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
        } else {
            // Insert new setting
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)");
        }
        
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
        
        return true;
    } catch (PDOException $e) {
        error_log("Error saving setting: " . $e->getMessage());
        return false;
    }
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
