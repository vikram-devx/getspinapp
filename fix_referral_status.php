<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Connect to the database
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();
    
    echo "<h2>Fixing Referral Status</h2>";
    
    // Get all pending referrals
    $stmt = $conn->query("
        SELECT r.id, r.referrer_id, r.referred_id, u.username, r.status
        FROM referrals r
        JOIN users u ON r.referred_id = u.id
        WHERE r.status = 'pending'
    ");
    $pendingReferrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($pendingReferrals) . " pending referrals to check</p>";
    
    $updatedCount = 0;
    
    foreach ($pendingReferrals as $referral) {
        // Check if points were awarded for this referral
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM transactions
            WHERE user_id = :referrer_id
            AND description LIKE :description
        ");
        $stmt->bindValue(':referrer_id', $referral['referrer_id']);
        $stmt->bindValue(':description', 'Referral bonus for inviting ' . $referral['username']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If points were awarded, update status to completed
        if ($result && $result['count'] > 0) {
            $stmt = $conn->prepare("
                UPDATE referrals 
                SET status = 'completed', updated_at = CURRENT_TIMESTAMP 
                WHERE id = :referral_id
            ");
            $stmt->bindValue(':referral_id', $referral['id']);
            $stmt->execute();
            
            $updatedCount++;
            echo "<p>Updated referral #" . $referral['id'] . " - " . $referral['username'] . " to completed status</p>";
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "<p><strong>Completed! Updated " . $updatedCount . " referrals to 'completed' status.</strong></p>";
    echo "<p><a href='referrals.php'>Return to Referrals Page</a></p>";
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>