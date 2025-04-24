<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

// Admin user details
$admin_username = 'admin';
$admin_email = 'admin@rewardsapp.com';
$admin_password = 'Admin123!';
$is_admin = 1;

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // First check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindValue(':username', $admin_username);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Admin user already exists. Please use the existing admin account.<br>";
        echo "Username: $admin_username<br>";
        echo "Password: $admin_password<br>";
        exit();
    }
    
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, points) VALUES (:username, :email, :password, :is_admin, 1000)");
    $stmt->bindValue(':username', $admin_username);
    $stmt->bindValue(':email', $admin_email);
    $stmt->bindValue(':password', $hashed_password);
    $stmt->bindValue(':is_admin', $is_admin);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!<br>";
        echo "Username: $admin_username<br>";
        echo "Password: $admin_password<br>";
        echo "You can now login with these credentials and access the admin area.<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to create admin user.";
    }
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage();
}
?>