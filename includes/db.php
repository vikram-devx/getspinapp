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
            // Check if this is a readonly database error
            if (strpos($e->getMessage(), 'readonly database') !== false) {
                // Try to fix permissions
                $this->fixDatabasePermissions();
                
                // Try to connect again
                try {
                    $this->connection = new PDO('sqlite:' . DB_PATH);
                    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e2) {
                    // If it still fails, show a more helpful error message
                    die('Database Permission Error: Unable to write to the database. Please check server permissions for: ' . DB_PATH);
                }
            } else {
                die('Database Connection Error: ' . $e->getMessage());
            }
        }
    }
    
    // Function to fix database permissions
    private function fixDatabasePermissions() {
        // Check and create data directory with proper permissions
        $dataDir = dirname(DB_PATH);
        if (!file_exists($dataDir)) {
            if (!@mkdir($dataDir, 0777, true)) {
                error_log("Failed to create data directory: " . $dataDir);
            }
        }
        
        // Set directory permissions - suppress warnings if permission denied
        if (file_exists($dataDir)) {
            @chmod($dataDir, 0777);
        }
        
        // Set file permissions if the database file exists - suppress warnings if permission denied
        if (file_exists(DB_PATH)) {
            @chmod(DB_PATH, 0666);
        }
        
        // Try to set database directory writeable by current user
        // Sometimes on shared hosting, this approach works better
        try {
            // Check if we can create a temporary file in the directory
            $temp_file = $dataDir . '/.write_test_' . time();
            if (@file_put_contents($temp_file, 'test')) {
                // We can write to the directory, clean up
                @unlink($temp_file);
            }
            
            // Try to make the database file writeable by setting its content
            if (file_exists(DB_PATH) && filesize(DB_PATH) > 0) {
                // Only do this if the database already has content
                // This is a more aggressive approach, use with caution
                // We're just reading the database content and writing it back
                // to ensure the file is owned by the current PHP process
                $db_content = @file_get_contents(DB_PATH);
                if ($db_content !== false) {
                    @file_put_contents(DB_PATH, $db_content);
                }
            }
        } catch (Exception $e) {
            error_log("Error while trying alternative permission fix: " . $e->getMessage());
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
            // Check if this is a readonly database error
            if (strpos($e->getMessage(), 'readonly database') !== false) {
                // Try to fix permissions
                $this->fixDatabasePermissions();
                
                // Try the query again
                try {
                    return $this->connection->query($sql);
                } catch (PDOException $e2) {
                    // Log error for debugging
                    error_log("Query Error (after permission fix attempt): " . $e2->getMessage() . "\nSQL: " . $sql);
                    throw new Exception("Database Permission Error: Unable to write to the database. Please contact the administrator.");
                }
            } else {
                // Log error for debugging
                error_log("Query Error: " . $e->getMessage() . "\nSQL: " . $sql);
                return false;
            }
        }
    }
    
    // Prepare statement
    public function prepare($sql) {
        try {
            return $this->connection->prepare($sql);
        } catch (PDOException $e) {
            // Check if this is a readonly database error
            if (strpos($e->getMessage(), 'readonly database') !== false) {
                // Try to fix permissions
                $this->fixDatabasePermissions();
                
                // Try to prepare again
                try {
                    return $this->connection->prepare($sql);
                } catch (PDOException $e2) {
                    // Log error for debugging
                    error_log("Prepare Error (after permission fix attempt): " . $e2->getMessage() . "\nSQL: " . $sql);
                    throw new Exception("Database Permission Error: Unable to write to the database. Please contact the administrator.");
                }
            } else {
                // Log error and rethrow
                error_log("Prepare Error: " . $e->getMessage() . "\nSQL: " . $sql);
                throw $e;
            }
        }
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
        
        // Task Progress table (to track task completion status)
        $this->query("CREATE TABLE IF NOT EXISTS task_progress (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            offer_id TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('started', 'in_progress', 'completed', 'failed')),
            progress_percent INTEGER NOT NULL DEFAULT 0,
            progress_message TEXT,
            estimated_completion_time INTEGER, /* Estimated time to completion in seconds */
            start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completion_time TIMESTAMP NULL,
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
