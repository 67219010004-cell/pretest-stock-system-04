<?php
require 'db.php';

try {
    // Create orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        address_id INT,
        total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        status ENUM('pending', 'shipped', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (address_id) REFERENCES user_addresses(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'orders' created/verified.<br>";

    // Create order_items table
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'order_items' created/verified.<br>";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
