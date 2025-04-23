<?php
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Register a new user
    public function register($username, $email, $password) {
        $conn = $this->db->getConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'status' => 'error',
                'message' => 'Username already exists'
            ];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return [
                'status' => 'error',
                'message' => 'Email already exists'
            ];
        }
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Registration successful',
                'user_id' => $stmt->insert_id
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Registration failed: ' . $stmt->error
            ];
        }
    }
    
    // Login a user
    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        // Get user by username
        $stmt = $conn->prepare("SELECT id, username, email, password, points, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'status' => 'error',
                'message' => 'Invalid username or password'
            ];
        }
        
        $user = $result->fetch_assoc();
        
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
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Check if user is admin
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === 1;
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
        
        $stmt = $conn->prepare("SELECT id, username, email, points, is_admin, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    // Update user points
    public function updatePoints($user_id, $points, $type, $description, $reference_id = null, $reference_type = null) {
        $conn = $this->db->getConnection();
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update user points
            if ($type === 'earn') {
                $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
            }
            
            $stmt->bind_param("ii", $points, $user_id);
            $stmt->execute();
            
            // Record transaction
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, points, description, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississ", $user_id, $type, $points, $description, $reference_id, $reference_type);
            $stmt->execute();
            
            // Update session points if it's the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                if ($type === 'earn') {
                    $_SESSION['points'] += $points;
                } else {
                    $_SESSION['points'] -= $points;
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            return [
                'status' => 'success',
                'message' => 'Points updated successfully'
            ];
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            
            return [
                'status' => 'error',
                'message' => 'Failed to update points: ' . $e->getMessage()
            ];
        }
    }
}
?>
