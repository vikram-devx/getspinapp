<?php
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Generate a unique referral code
    private function generateReferralCode() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Check if code already exists
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = :code");
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            // Code exists, generate a new one
            return $this->generateReferralCode();
        }
        
        return $code;
    }
    
    // Register a new user
    public function register($username, $email, $password, $referral_code = null) {
        $conn = $this->db->getConnection();
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'status' => 'error',
                    'message' => 'Username already exists'
                ];
            }
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'status' => 'error',
                    'message' => 'Email already exists'
                ];
            }
            
            // Find referrer if referral code was provided
            $referrer_id = null;
            if ($referral_code) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE referral_code = :referral_code");
                $stmt->bindValue(':referral_code', $referral_code);
                $stmt->execute();
                $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($referrer) {
                    $referrer_id = $referrer['id'];
                }
            }
            
            // Generate unique referral code for new user
            $new_referral_code = $this->generateReferralCode();
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, referral_code, referred_by) VALUES (:username, :email, :password, :referral_code, :referred_by)");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $hashed_password);
            $stmt->bindValue(':referral_code', $new_referral_code);
            $stmt->bindValue(':referred_by', $referrer_id);
            
            if ($stmt->execute()) {
                $new_user_id = $conn->lastInsertId();
                
                // If this was a referral, create a record in the referrals table
                if ($referrer_id) {
                    $stmt = $conn->prepare("INSERT INTO referrals (referrer_id, referred_id) VALUES (:referrer_id, :referred_id)");
                    $stmt->bindValue(':referrer_id', $referrer_id);
                    $stmt->bindValue(':referred_id', $new_user_id);
                    $stmt->execute();
                    
                    // Award 100 points to the referrer
                    $this->updatePoints($referrer_id, 100, 'earn', 'Referral bonus for inviting ' . $username);
                }
                
                // Commit the transaction
                $conn->commit();
                
                return [
                    'status' => 'success',
                    'message' => 'Registration successful',
                    'user_id' => $new_user_id
                ];
            } else {
                $conn->rollBack();
                return [
                    'status' => 'error',
                    'message' => 'Registration failed'
                ];
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            return [
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Login a user
    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        try {
            // Get user by username
            $stmt = $conn->prepare("SELECT id, username, email, password, points, is_admin FROM users WHERE username = :username");
            $stmt->bindValue(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid username or password'
                ];
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['points'] = $user['points'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['logged_in'] = true;
                
                return [
                    'status' => 'success',
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'points' => $user['points'],
                        'is_admin' => $user['is_admin']
                    ]
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Invalid username or password'
                ];
            }
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Login failed: ' . $e->getMessage()
            ];
        }
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Check if user is admin
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    // Logout user
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        return [
            'status' => 'success',
            'message' => 'Logout successful'
        ];
    }
    
    // Get current user
    public function getUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $user_id = $_SESSION['user_id'];
        $conn = $this->db->getConnection();
        
        try {
            $stmt = $conn->prepare("SELECT id, username, email, points, is_admin, created_at, referral_code, referred_by FROM users WHERE id = :user_id");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return null;
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log('Error getting user: ' . $e->getMessage());
            return null;
        }
    }
    
    // Get referral stats for a user
    public function getReferralStats($user_id) {
        $conn = $this->db->getConnection();
        
        try {
            // Get total number of referrals
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_id = :user_id");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_referrals = $result ? $result['count'] : 0;
            
            // Get successful referrals (claimed bonus)
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_id = :user_id AND status = 'completed'");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $successful_referrals = $result ? $result['count'] : 0;
            
            // Get recent referrals
            $stmt = $conn->prepare("
                SELECT r.*, u.username 
                FROM referrals r
                JOIN users u ON r.referred_id = u.id
                WHERE r.referrer_id = :user_id
                ORDER BY r.created_at DESC
                LIMIT 5
            ");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            $recent_referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total earned from referrals
            $stmt = $conn->prepare("
                SELECT SUM(t.points) as total
                FROM transactions t
                WHERE t.user_id = :user_id
                AND t.description LIKE 'Referral bonus%'
            ");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_earned = $result && $result['total'] ? $result['total'] : 0;
            
            return [
                'total_referrals' => $total_referrals,
                'successful_referrals' => $successful_referrals,
                'recent_referrals' => $recent_referrals,
                'total_earned' => $total_earned
            ];
        } catch (PDOException $e) {
            error_log('Error getting referral stats: ' . $e->getMessage());
            return [
                'total_referrals' => 0,
                'successful_referrals' => 0,
                'recent_referrals' => [],
                'total_earned' => 0
            ];
        }
    }
    
    // Update user points
    public function updatePoints($user_id, $points, $type, $description, $reference_id = null, $reference_type = null) {
        $conn = $this->db->getConnection();
        
        try {
            // Determine if we need to manage our own transaction
            $needsTransaction = !$conn->inTransaction();
            
            // Begin transaction if needed
            if ($needsTransaction) {
                $conn->beginTransaction();
            }
            
            // Update user points
            if ($type === 'earn') {
                $stmt = $conn->prepare("UPDATE users SET points = points + :points WHERE id = :user_id");
            } else {
                $stmt = $conn->prepare("UPDATE users SET points = points - :points WHERE id = :user_id");
            }
            
            $stmt->bindValue(':points', $points);
            $stmt->bindValue(':user_id', $user_id);
            $stmt->execute();
            
            // Record transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, points, description, reference_id, reference_type) VALUES (:user_id, :type, :points, :description, :reference_id, :reference_type)");
            $stmt->bindValue(':user_id', $user_id);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':points', $points);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':reference_id', $reference_id);
            $stmt->bindValue(':reference_type', $reference_type);
            $stmt->execute();
            
            // Update session points if it's the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                if ($type === 'earn') {
                    $_SESSION['points'] += $points;
                } else {
                    $_SESSION['points'] -= $points;
                }
            }
            
            // Commit transaction if we started it
            if ($needsTransaction) {
                $conn->commit();
            }
            
            return true;
        } catch (PDOException $e) {
            // Roll back transaction if we started it
            if (isset($needsTransaction) && $needsTransaction && $conn->inTransaction()) {
                $conn->rollBack();
            }
            
            error_log("Error updating points: " . $e->getMessage());
            return false;
        }
    }
}
?>
