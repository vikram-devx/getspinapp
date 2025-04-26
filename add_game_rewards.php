<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();
    
    echo "<h1>Adding Game Rewards</h1>";
    
    // Define game rewards
    $gameRewards = [
        [
            'name' => '50 Coin Master Spins',
            'description' => 'Get 50 free spins for Coin Master. We\'ll send the spins to your Coin Master account within 24 hours.',
            'points_required' => 500
        ],
        [
            'name' => '100 Coin Master Spins',
            'description' => 'Get 100 free spins for Coin Master. We\'ll send the spins to your Coin Master account within 24 hours.',
            'points_required' => 900
        ],
        [
            'name' => '200 Coin Master Spins',
            'description' => 'Get 200 free spins for Coin Master. We\'ll send the spins to your Coin Master account within 24 hours.',
            'points_required' => 1700
        ],
        [
            'name' => '50 Monopoly Go Dice',
            'description' => 'Get 50 free dice for Monopoly Go. We\'ll send the dice to your Monopoly Go account within 24 hours.',
            'points_required' => 600
        ],
        [
            'name' => '100 Monopoly Go Dice',
            'description' => 'Get 100 free dice for Monopoly Go. We\'ll send the dice to your Monopoly Go account within 24 hours.',
            'points_required' => 1100
        ],
        [
            'name' => '200 Monopoly Go Dice',
            'description' => 'Get 200 free dice for Monopoly Go. We\'ll send the dice to your Monopoly Go account within 24 hours.',
            'points_required' => 2000
        ]
    ];
    
    // Check if rewards already exist with these names
    foreach ($gameRewards as $reward) {
        $stmt = $conn->prepare("SELECT id FROM rewards WHERE name = :name");
        $stmt->bindValue(':name', $reward['name']);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "<p>Reward '{$reward['name']}' already exists, skipping...</p>";
        } else {
            // Add the reward
            $stmt = $conn->prepare("INSERT INTO rewards (name, description, points_required) VALUES (:name, :description, :points_required)");
            $stmt->bindValue(':name', $reward['name']);
            $stmt->bindValue(':description', $reward['description']);
            $stmt->bindValue(':points_required', $reward['points_required']);
            $stmt->execute();
            
            echo "<p>Added new reward: {$reward['name']} for {$reward['points_required']} points</p>";
        }
    }
    
    // Commit the transaction
    $conn->commit();
    
    echo "<h2>Game Rewards Added Successfully!</h2>";
    echo "<p><a href='seed_test_data.php' class='btn btn-primary'>Proceed to Seed Test Data</a></p>";
    
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