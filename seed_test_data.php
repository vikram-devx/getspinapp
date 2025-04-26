<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();
$auth = new Auth();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Seeding Test Data for GetSpins App</h1>";

try {
    // Start transaction
    $conn->beginTransaction();
    
    // First, create test users
    $testUsers = [
        [
            'username' => 'alice_smith',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'points' => 850
        ],
        [
            'username' => 'bob_jones',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'points' => 1200
        ],
        [
            'username' => 'charlie_brown',
            'email' => 'charlie@example.com',
            'password' => 'password123',
            'points' => 2500
        ],
        [
            'username' => 'diana_prince',
            'email' => 'diana@example.com',
            'password' => 'password123',
            'points' => 300
        ],
        [
            'username' => 'edward_stark',
            'email' => 'edward@example.com',
            'password' => 'password123',
            'points' => 1500
        ],
        [
            'username' => 'fiona_green',
            'email' => 'fiona@example.com',
            'password' => 'password123',
            'points' => 750
        ],
        [
            'username' => 'george_martin',
            'email' => 'george@example.com',
            'password' => 'password123',
            'points' => 100
        ],
        [
            'username' => 'hannah_montana',
            'email' => 'hannah@example.com',
            'password' => 'password123',
            'points' => 3200
        ],
        [
            'username' => 'ian_fleming',
            'email' => 'ian@example.com',
            'password' => 'password123',
            'points' => 450
        ],
        [
            'username' => 'julia_roberts',
            'email' => 'julia@example.com',
            'password' => 'password123',
            'points' => 1800
        ]
    ];
    
    $userIds = [];
    $referralCodes = [];
    
    echo "<h2>Creating Test Users</h2>";
    
    // Create each test user
    foreach ($testUsers as $userData) {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->bindValue(':username', $userData['username']);
        $stmt->bindValue(':email', $userData['email']);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "<p>User {$userData['username']} already exists, skipping...</p>";
            continue;
        }
        
        // Generate a random referral code
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $referralCode = '';
        for ($i = 0; $i < 8; $i++) {
            $referralCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Create the user
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, points, referral_code) VALUES (:username, :email, :password, :points, :referral_code)");
        $stmt->bindValue(':username', $userData['username']);
        $stmt->bindValue(':email', $userData['email']);
        $stmt->bindValue(':password', $hashedPassword);
        $stmt->bindValue(':points', $userData['points']);
        $stmt->bindValue(':referral_code', $referralCode);
        $stmt->execute();
        
        $userId = $conn->lastInsertId();
        $userIds[] = $userId;
        $referralCodes[$userId] = $referralCode;
        
        echo "<p>Created user: {$userData['username']} with ID: $userId and points: {$userData['points']}</p>";
    }
    
    // Create referral relationships (some users refer others)
    echo "<h2>Creating Referral Relationships</h2>";
    
    $referralRelationships = [
        [0, 1], // Alice referred Bob
        [0, 2], // Alice referred Charlie
        [1, 3], // Bob referred Diana
        [2, 4], // Charlie referred Edward
        [4, 5], // Edward referred Fiona
        [4, 6], // Edward referred George
        [2, 7], // Charlie referred Hannah
        [7, 8], // Hannah referred Ian
        [7, 9]  // Hannah referred Julia
    ];
    
    foreach ($referralRelationships as $rel) {
        if (isset($userIds[$rel[0]]) && isset($userIds[$rel[1]])) {
            $referrerId = $userIds[$rel[0]];
            $referredId = $userIds[$rel[1]];
            
            // Check if relationship already exists
            $stmt = $conn->prepare("SELECT id FROM referrals WHERE referrer_id = :referrer_id AND referred_id = :referred_id");
            $stmt->bindValue(':referrer_id', $referrerId);
            $stmt->bindValue(':referred_id', $referredId);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                echo "<p>Referral relationship already exists, skipping...</p>";
                continue;
            }
            
            // Update the referred_by field for the referred user
            $stmt = $conn->prepare("UPDATE users SET referred_by = :referrer_id WHERE id = :referred_id");
            $stmt->bindValue(':referrer_id', $referrerId);
            $stmt->bindValue(':referred_id', $referredId);
            $stmt->execute();
            
            // Create referral record
            $status = (rand(0, 10) > 2) ? 'completed' : 'pending'; // 80% completed, 20% pending
            $stmt = $conn->prepare("INSERT INTO referrals (referrer_id, referred_id, status) VALUES (:referrer_id, :referred_id, :status)");
            $stmt->bindValue(':referrer_id', $referrerId);
            $stmt->bindValue(':referred_id', $referredId);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            
            // If completed, add points transaction
            if ($status === 'completed') {
                $referredUsername = $testUsers[$rel[1]]['username'];
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, points, description) VALUES (:user_id, 'earn', 100, :description)");
                $stmt->bindValue(':user_id', $referrerId);
                $stmt->bindValue(':description', "Referral bonus for inviting $referredUsername");
                $stmt->execute();
            }
            
            echo "<p>Created referral: {$testUsers[$rel[0]]['username']} referred {$testUsers[$rel[1]]['username']} (Status: $status)</p>";
        }
    }
    
    // Create sample offer completions
    echo "<h2>Creating Sample Offer Completions</h2>";
    
    // Sample offer IDs
    $offerIds = ['offer1', 'offer2', 'offer3', '12345', '67890', '54321'];
    
    // For each user, complete some random offers
    foreach ($userIds as $index => $userId) {
        $numOffers = rand(0, 5); // Each user completes between 0 and 5 offers
        
        for ($i = 0; $i < $numOffers; $i++) {
            $offerId = $offerIds[array_rand($offerIds)];
            $payout = round(rand(50, 350) / 100, 2); // Random payout between $0.50 and $3.50
            $points = (int)($payout * POINTS_CONVERSION_RATE);
            $completed = rand(0, 10) > 2 ? 1 : 0; // 80% completed, 20% in progress
            
            // Check if this offer is already recorded for this user
            $stmt = $conn->prepare("SELECT id FROM user_offers WHERE user_id = :user_id AND offer_id = :offer_id");
            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':offer_id', $offerId);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                continue; // Skip if already exists
            }
            
            // Record the offer
            $stmt = $conn->prepare("INSERT INTO user_offers (user_id, offer_id, completed, points_earned, ip_address) VALUES (:user_id, :offer_id, :completed, :points, :ip)");
            $stmt->bindValue(':user_id', $userId);
            $stmt->bindValue(':offer_id', $offerId);
            $stmt->bindValue(':completed', $completed);
            $stmt->bindValue(':points', $completed ? $points : 0);
            $stmt->bindValue(':ip', '127.0.0.1');
            $stmt->execute();
            
            // If completed, create a transaction record
            if ($completed) {
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, points, description, reference_id, reference_type) VALUES (:user_id, 'earn', :points, :description, :reference_id, 'offer')");
                $stmt->bindValue(':user_id', $userId);
                $stmt->bindValue(':points', $points);
                $stmt->bindValue(':description', "Completed offer #$offerId");
                $stmt->bindValue(':reference_id', $offerId);
                $stmt->execute();
                
                echo "<p>{$testUsers[$index]['username']} completed offer #$offerId and earned $points points</p>";
            } else {
                // Create task progress for incomplete offers
                $progress = rand(5, 95);
                $status = $progress < 50 ? 'started' : 'in_progress';
                
                $stmt = $conn->prepare("INSERT INTO task_progress (user_id, offer_id, status, progress_percent, progress_message) VALUES (:user_id, :offer_id, :status, :progress, :message)");
                $stmt->bindValue(':user_id', $userId);
                $stmt->bindValue(':offer_id', $offerId);
                $stmt->bindValue(':status', $status);
                $stmt->bindValue(':progress', $progress);
                $stmt->bindValue(':message', "Task in progress... ($progress%)");
                $stmt->execute();
                
                echo "<p>{$testUsers[$index]['username']} has in-progress offer #$offerId at $progress%</p>";
            }
        }
    }
    
    // Create reward redemptions
    echo "<h2>Creating Reward Redemptions</h2>";
    
    // Get available rewards
    $stmt = $conn->query("SELECT id, name, points_required FROM rewards");
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rewards)) {
        echo "<p>No rewards found! Please set up rewards first.</p>";
    } else {
        // For some users, create redemption records
        foreach ($userIds as $index => $userId) {
            // Only some users redeem rewards (based on if they have enough points)
            if ($testUsers[$index]['points'] >= 500 && rand(0, 1)) {
                // Pick a random reward they can afford
                $affordableRewards = array_filter($rewards, function($reward) use ($testUsers, $index) {
                    return $reward['points_required'] <= $testUsers[$index]['points'];
                });
                
                if (!empty($affordableRewards)) {
                    $reward = $affordableRewards[array_rand($affordableRewards)];
                    
                    // Choose a status (pending, completed, or rejected)
                    $statuses = ['pending', 'completed', 'rejected'];
                    $status = $statuses[rand(0, 2)];
                    
                    $stmt = $conn->prepare("INSERT INTO redemptions (user_id, reward_id, points_used, status) VALUES (:user_id, :reward_id, :points, :status)");
                    $stmt->bindValue(':user_id', $userId);
                    $stmt->bindValue(':reward_id', $reward['id']);
                    $stmt->bindValue(':points', $reward['points_required']);
                    $stmt->bindValue(':status', $status);
                    $stmt->execute();
                    
                    // If status is completed, record a transaction
                    if ($status === 'completed') {
                        $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, points, description, reference_id, reference_type) VALUES (:user_id, 'spend', :points, :description, :reference_id, 'redemption')");
                        $stmt->bindValue(':user_id', $userId);
                        $stmt->bindValue(':points', $reward['points_required']);
                        $stmt->bindValue(':description', "Redeemed {$reward['name']}");
                        $stmt->bindValue(':reference_id', $reward['id']);
                        $stmt->execute();
                    }
                    
                    echo "<p>{$testUsers[$index]['username']} redeemed {$reward['name']} for {$reward['points_required']} points (Status: $status)</p>";
                }
            }
        }
    }
    
    // Commit the transaction
    $conn->commit();
    
    echo "<h2>Data Seeding Completed Successfully!</h2>";
    echo "<p><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></p>";
    
} catch (PDOException $e) {
    // Roll back the transaction if something failed
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "<div class='alert alert-danger'>";
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>