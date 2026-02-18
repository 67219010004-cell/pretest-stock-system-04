<?php
require 'db.php';

try {
    // 1. Create suppliers table
    $sql = "CREATE TABLE IF NOT EXISTS suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        contact_info TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'suppliers' created/verified.<br>";

    // 2. Create product_serials table
    $sql = "CREATE TABLE IF NOT EXISTS product_serials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        supplier_id INT,
        serial_number VARCHAR(255) NOT NULL UNIQUE,
        order_id INT,
        status ENUM('available', 'sold', 'rma', 'lost') DEFAULT 'available',
        warranty_end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'product_serials' created/verified.<br>";

    // 3. Create rma_cases table
    $sql = "CREATE TABLE IF NOT EXISTS rma_cases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        serial_id INT NOT NULL,
        user_id INT,
        issue_description TEXT,
        status ENUM('received', 'checking', 'vendor_claim', 'returning', 'done', 'rejected') DEFAULT 'received',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (serial_id) REFERENCES product_serials(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'rma_cases' created/verified.<br>";

    // 4. Seed some initial suppliers if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM suppliers");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO suppliers (name, contact_info) VALUES 
            ('Synnex', 'Call Center: 1251'), 
            ('Ingram Micro', 'Tel: 02-012-2222'),
            ('Ascenti', 'Facebook: Ascenti Resources')");
        echo "Seeded initial suppliers.<br>";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
