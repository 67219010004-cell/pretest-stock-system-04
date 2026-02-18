<?php
require 'db.php';

try {
    // Add image column to products table if it doesn't exist
    $sql = "ALTER TABLE products ADD COLUMN image VARCHAR(255) NULL AFTER description";
    // Check if column exists first to avoid error
    $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'image'");
    if ($check->rowCount() == 0) {
        $pdo->exec($sql);
        echo "Column 'image' added to 'products' table.<br>";
    } else {
        echo "Column 'image' already exists.<br>";
    }

    // Create uploads directory
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "Directory 'uploads' created.<br>";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
