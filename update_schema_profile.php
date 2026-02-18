<?php
require 'db.php';

try {
    // Add profile_image column to users table
    $sql = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL";
    
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($check->rowCount() == 0) {
        $pdo->exec($sql);
        echo "Column 'profile_image' added to 'users' table.<br>";
    } else {
        echo "Column 'profile_image' already exists.<br>";
    }

    // Create uploads/profiles directory
    if (!file_exists('uploads/profiles')) {
        mkdir('uploads/profiles', 0777, true);
        echo "Directory 'uploads/profiles' created.<br>";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
