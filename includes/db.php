<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance;
    
    private function __construct() {
        try {
            // Create SQLite database connection
            $this->connection = new PDO('sqlite:' . DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }
    
    // Get the database instance (Singleton pattern)
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Get database connection
    public function getConnection() {
        return $this->connection;
    }
    
    // Execute a query
    public function query($sql) {
        try {
            return $this->connection->query($sql);
        } catch (PDOException $e) {
            // Log error for debugging
            error_log("Query Error: " . $e->getMessage() . "\nSQL: " . $sql);
            return false;
        }
    }
    
    // Prepare statement
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    // Escape string - PDO uses parametrized queries, but keeping method for compatibility
    public function escapeString($string) {
        return $string; // PDO handles escaping with prepared statements
    }
    
    // Get the last inserted ID
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    // Close the connection
    public function close() {
        $this->connection = null;
    }
    
    // Setup the database tables
    public function setupDatabase() {
        // Enable foreign keys
        $this->query("PRAGMA foreign_keys = ON");
        
        // Users table
        $this->query("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            points INTEGER NOT NULL DEFAULT 0,
            is_admin INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Offers table
        $this->query("CREATE TABLE IF NOT EXISTS offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            offer_id TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            requirements TEXT,
            payout REAL NOT NULL,
            payout_points INTEGER NOT NULL,
            offer_type TEXT NOT NULL,
            offer_url TEXT NOT NULL,
            countries TEXT,
            devices TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // User Offers table (tracking which offers users have completed)
        $this->query("CREATE TABLE IF NOT EXISTS user_offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            offer_id TEXT NOT NULL,
            completed INTEGER NOT NULL DEFAULT 0,
            points_earned INTEGER NOT NULL DEFAULT 0,
            ip_address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Rewards table
        $this->query("CREATE TABLE IF NOT EXISTS rewards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            points_required INTEGER NOT NULL,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Redemptions table
        $this->query("CREATE TABLE IF NOT EXISTS redemptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reward_id INTEGER NOT NULL,
            points_used INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
        )");
        
        // Transactions table (to track points earned and spent)
        $this->query("CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('earn', 'spend')),
            points INTEGER NOT NULL,
            description TEXT NOT NULL,
            reference_id INTEGER NULL,
            reference_type TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
        
        // Check if admin user exists
        $stmt = $this->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        $adminExists = (int)$stmt->fetchColumn();
        
        // Create default admin user if not exists
        if (!$adminExists) {
            $stmt = $this->prepare("INSERT INTO users 
                        (username, email, password, is_admin) 
                      VALUES 
                        ('admin', 'admin@rewardsapp.com', :password, 1)");
            $stmt->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT));
            $stmt->execute();
        }
        
        // Check if rewards exist
        $stmt = $this->prepare("SELECT COUNT(*) FROM rewards");
        $stmt->execute();
        $rewardsExist = (int)$stmt->fetchColumn();
        
        // Create default rewards if not exist
        if (!$rewardsExist) {
            $defaultRewards = [
                ['$5 Amazon Gift Card', 'Get a $5 Amazon gift card', 500],
                ['$10 Amazon Gift Card', 'Get a $10 Amazon gift card', 1000],
                ['$25 Amazon Gift Card', 'Get a $25 Amazon gift card', 2500],
                ['$5 PayPal Cash', 'Get $5 in your PayPal account', 550],
                ['$10 PayPal Cash', 'Get $10 in your PayPal account', 1100]
            ];
            
            $stmt = $this->prepare("INSERT INTO rewards 
                          (name, description, points_required) 
                        VALUES 
                          (:name, :description, :points)");
                          
            foreach ($defaultRewards as $reward) {
                $stmt->bindValue(':name', $reward[0]);
                $stmt->bindValue(':description', $reward[1]);
                $stmt->bindValue(':points', $reward[2]);
                $stmt->execute();
            }
        }
    }
}

// Initialize the database and create tables if they don't exist
$db = Database::getInstance();
$db->setupDatabase();
?>
