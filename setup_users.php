<?php
require 'db.php';

try {
    // Create Users Table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'customer') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Users table created successfully.<br>";

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['Tanawat']);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert Admin
        $username = 'Tanawat';
        $password = '123456789';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'admin';

        $insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $insert->execute([$username, $hashed_password, $role]);
        echo "Admin user 'Tanawat' created successfully.<br>";
    } else {
        echo "Admin user 'Tanawat' already exists.<br>";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
