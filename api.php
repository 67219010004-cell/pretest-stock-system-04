<?php
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        try {
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $stock = $_POST['stock_quantity'];
            $desc = $_POST['description'];

            $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $price, $stock, $desc]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    elseif ($action === 'delete_product') {
        try {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
