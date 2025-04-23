<?php
require_once 'config.php';

class Database {
    private $connection;
    private static $instance;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            die('Database Connection Error: ' . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8");
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
        return $this->connection->query($sql);
    }
    
    // Prepare statement
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    // Escape string
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Get the last inserted ID
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    // Close the connection
    public function close() {
        $this->connection->close();
    }
    
    // Setup the database tables
    public function setupDatabase() {
        // Users table
        $this->query("CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            points INT(11) NOT NULL DEFAULT 0,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // Offers table
        $this->query("CREATE TABLE IF NOT EXISTS offers (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            offer_id VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            requirements TEXT,
            payout DECIMAL(10,2) NOT NULL,
            payout_points INT(11) NOT NULL,
            offer_type VARCHAR(50) NOT NULL,
            offer_url TEXT NOT NULL,
            countries TEXT,
            devices TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // User Offers table (tracking which offers users have completed)
        $this->query("CREATE TABLE IF NOT EXISTS user_offers (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            offer_id VARCHAR(100) NOT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            points_earned INT(11) NOT NULL DEFAULT 0,
            ip_address VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // Rewards table
        $this->query("CREATE TABLE IF NOT EXISTS rewards (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            points_required INT(11) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // Redemptions table
        $this->query("CREATE TABLE IF NOT EXISTS redemptions (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            reward_id INT(11) NOT NULL,
            points_used INT(11) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // Transactions table (to track points earned and spent)
        $this->query("CREATE TABLE IF NOT EXISTS transactions (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            type ENUM('earn', 'spend') NOT NULL,
            points INT(11) NOT NULL,
            description VARCHAR(255) NOT NULL,
            reference_id INT(11) NULL,
            reference_type VARCHAR(50) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        
        // Create default admin user
        $this->query("INSERT IGNORE INTO users 
                        (username, email, password, is_admin) 
                      VALUES 
                        ('admin', 'admin@rewardsapp.com', '".password_hash('admin123', PASSWORD_DEFAULT)."', 1)");
        
        // Create some default rewards
        $this->query("INSERT IGNORE INTO rewards 
                        (name, description, points_required) 
                      VALUES 
                        ('$5 Amazon Gift Card', 'Get a $5 Amazon gift card', 500),
                        ('$10 Amazon Gift Card', 'Get a $10 Amazon gift card', 1000),
                        ('$25 Amazon Gift Card', 'Get a $25 Amazon gift card', 2500),
                        ('$5 PayPal Cash', 'Get $5 in your PayPal account', 550),
                        ('$10 PayPal Cash', 'Get $10 in your PayPal account', 1100)");
    }
}

// Initialize the database and create tables if they don't exist
$db = Database::getInstance();
$db->setupDatabase();
?>
