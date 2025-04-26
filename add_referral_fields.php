<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Add referral_code field to users table
    $conn->exec("ALTER TABLE users ADD COLUMN referral_code TEXT");
    
    // Add referred_by field to users table
    $conn->exec("ALTER TABLE users ADD COLUMN referred_by INTEGER");
    
    // Generate unique referral codes for existing users
    $stmt = $conn->query("SELECT id FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $referral_code = generateUniqueReferralCode();
        $stmt = $conn->prepare("UPDATE users SET referral_code = :referral_code WHERE id = :user_id");
        $stmt->bindValue(':referral_code', $referral_code);
        $stmt->bindValue(':user_id', $user['id']);
        $stmt->execute();
    }
    
    // Create a new table to track successful referrals
    $conn->exec("CREATE TABLE IF NOT EXISTS referrals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        referrer_id INTEGER NOT NULL,
        referred_id INTEGER NOT NULL,
        status TEXT DEFAULT 'pending',
        reward_claimed INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users (id),
        FOREIGN KEY (referred_id) REFERENCES users (id)
    )");
    
    // Commit transaction
    $conn->commit();
    
    echo "Successfully added referral system tables and fields.";
} catch (PDOException $e) {
    $conn->rollBack();
    echo "Error: " . $e->getMessage();
}

function generateUniqueReferralCode() {
    // Generate a random string of 8 characters
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}
?>