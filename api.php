<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Check if user is logged in for protected actions
$public_actions = ['add_to_cart', 'remove_from_cart', 'clear_cart', 'get_cart']; // Cart can be partially public (session based), but let's keep it simple
if (!isset($_SESSION['user_id'])) {
    // some actions might need login
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Cart Actions (Session based, no DB yet)
    if ($action === 'add_to_cart') {
        $product_id = $_POST['product_id'];
        $quantity = (int)$_POST['quantity']; // In this simple version, usually 1

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }

        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'])]);
    }
    elseif ($action === 'remove_from_cart') {
        $product_id = $_POST['product_id'];
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'checkout') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login to checkout']);
            exit();
        }

        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit();
        }

        try {
            $pdo->beginTransaction();

            $user_id = $_SESSION['user_id'];
            $address_id = $_POST['address_id'];
            $cart = $_SESSION['cart'];
            $total_amount = 0;

            // Verify Stock and Calculate Total
            foreach ($cart as $pid => $qty) {
                $stmt = $pdo->prepare("SELECT price, stock_quantity FROM products WHERE id = ? FOR UPDATE");
                $stmt->execute([$pid]);
                $product = $stmt->fetch();

                if (!$product) {
                    throw new Exception("Product ID $pid not found");
                }
                if ($product['stock_quantity'] < $qty) {
                    throw new Exception("Insufficient stock for Product ID $pid");
                }
                $total_amount += $product['price'] * $qty;
            }

            // Create Order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, address_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $address_id, $total_amount]);
            $order_id = $pdo->lastInsertId();

            // Create Order Items and Update Stock/Serials
            foreach ($cart as $pid => $qty) {
                // Get Price
                $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $price = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $pid, $qty, $price]);

                // Update Stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$qty, $pid]);

                // Assign Serial Numbers (FIFO)
                // Limit by qty
                $stmt = $pdo->prepare("SELECT id FROM product_serials WHERE product_id = ? AND status = 'available' ORDER BY created_at ASC LIMIT ?");
                $stmt->bindValue(1, $pid, PDO::PARAM_INT);
                $stmt->bindValue(2, $qty, PDO::PARAM_INT);
                $stmt->execute();
                $serial_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (count($serial_ids) < $qty) {
                    // This creates a discrepancy if we rely solely on S/N count vs stock_count. 
                    // For now, we assume if stock_quantity existed, S/N SHOULD exist if we enforce it.
                    // But if migrating from old system, some stock might not have S/N.
                    // We will just assign what we can.
                }

                if (!empty($serial_ids)) {
                    $inQuery = implode(',', array_fill(0, count($serial_ids), '?'));
                    $updateSql = "UPDATE product_serials SET status = 'sold', order_id = ? WHERE id IN ($inQuery)";
                    $params = array_merge([$order_id], $serial_ids);
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute($params);
                }
            }

            $pdo->commit();
            $_SESSION['cart'] = []; // Clear Cart
            echo json_encode(['success' => true, 'order_id' => $order_id]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'update_order_status') {
         if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
         }
         try {
             $order_id = $_POST['order_id'];
             $status = $_POST['status'];
             $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
             $stmt->execute([$status, $order_id]);
             echo json_encode(['success' => true]);
         } catch (Exception $e) {
             echo json_encode(['success' => false, 'message' => $e->getMessage()]);
         }
    }
    elseif ($action === 'add_supplier') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $name = $_POST['name'];
            $contact = $_POST['contact_info'];
            
            $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_info) VALUES (?, ?)");
            $stmt->execute([$name, $contact]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'get_suppliers') {
        try {
            $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name");
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'suppliers' => $suppliers]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'restock_product') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $pdo->beginTransaction();

            $product_id = $_POST['product_id'];
            
            // Check if S/N restock (from manage_sn.php) or Simple restock (from index.php)
            if (isset($_POST['serials'])) {
                $supplier_id = $_POST['supplier_id'];
                $serials = json_decode($_POST['serials'], true);
                
                if (!is_array($serials) || empty($serials)) {
                    throw new Exception("No serial numbers provided");
                }
                
                $quantity = count($serials);
                $warranty_date = date('Y-m-d', strtotime('+1 year'));

                $stmt = $pdo->prepare("INSERT INTO product_serials (product_id, supplier_id, serial_number, status, warranty_end_date) VALUES (?, ?, ?, 'available', ?)");
                
                foreach ($serials as $sn) {
                    if (empty(trim($sn))) continue;
                    $stmt->execute([$product_id, $supplier_id, trim($sn), $warranty_date]);
                }
            } elseif (isset($_POST['quantity'])) {
                $quantity = (int)$_POST['quantity'];
            }

            // 2. Update Stock Quantity
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            // Check for duplicate entry error specifically
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 echo json_encode(['success' => false, 'message' => 'Duplicate Serial Number detected.']);
            } else {
                 echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
    elseif ($action === 'update_product') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $price = $_POST['price'];
            $description = $_POST['description'];
            
            $imageSql = "";
            $params = [$name, $price, $description];

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imageSql = ", image = ?";
                    $params[] = $targetFile;
                }
            }

            $params[] = $id;

            $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, description = ? $imageSql WHERE id = ?");
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    elseif ($action === 'add_product') {
        if ($_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $stock = $_POST['stock_quantity'];
            $desc = $_POST['description'];
            $imagePath = null;

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = $targetFile;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock_quantity, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category_id, $price, $stock, $desc, $imagePath]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    elseif ($action === 'delete_product') {
        try {
            // Permission check
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception("Unauthorized");
            }
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'update_profile') {
        if (!isset($_SESSION['user_id'])) {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $user_id = $_SESSION['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            
            $imageSql = "";
            $params = [$full_name, $email, $phone];

            // Handle Profile Image Upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/profiles/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $filename = 'user_' . $user_id . '_' . uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                    $imageSql = ", profile_image = ?";
                    $params[] = $targetFile;
                    $_SESSION['profile_image'] = $targetFile; // Update Session immediately
                }
            }

            $params[] = $user_id;

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? $imageSql WHERE id = ?");
            $stmt->execute($params);
            
            $_SESSION['full_name'] = $full_name; // Update session
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'add_address') {
        try {
            $user_id = $_SESSION['user_id'];
            $recipient = $_POST['recipient_name'];
            $address = $_POST['address_line'];
            $city = $_POST['city'];
            $postal = $_POST['postal_code'];
            $phone = $_POST['phone'];

            $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, recipient_name, address_line, city, postal_code, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $recipient, $address, $city, $postal, $phone]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'delete_address') {
        try {
            $user_id = $_SESSION['user_id'];
            $address_id = $_POST['id'];

            // Ensure address belongs to user
            $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$address_id, $user_id]);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'check_warranty') {
        try {
            $sn = $_POST['serial_number'];
            $stmt = $pdo->prepare("
                SELECT ps.*, p.name as product_name, p.category_id, s.name as supplier_name 
                FROM product_serials ps
                JOIN products p ON ps.product_id = p.id
                LEFT JOIN suppliers s ON ps.supplier_id = s.id
                WHERE ps.serial_number = ?
            ");
            $stmt->execute([$sn]);
            $serial = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$serial) {
                echo json_encode(['success' => false, 'message' => 'Serial Number not found.']);
                exit();
            }

            // Check if active RMA exists
            $stmt = $pdo->prepare("SELECT * FROM rma_cases WHERE serial_id = ? AND status != 'done' AND status != 'rejected'");
            $stmt->execute([$serial['id']]);
            $active_rma = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $serial, 'active_rma' => $active_rma]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'create_rma') {
        try {
            $serial_number = $_POST['serial_number'];
            $issue = $_POST['issue_description'];
            $user_id = $_SESSION['user_id'] ?? null; // Optional if guest

            // Get Serial ID
            $stmt = $pdo->prepare("SELECT id, status FROM product_serials WHERE serial_number = ?");
            $stmt->execute([$serial_number]);
            $serial = $stmt->fetch();

            if (!$serial) {
                throw new Exception("Invalid Serial Number");
            }

            // Check if already in RMA
            if ($serial['status'] === 'rma') {
                 throw new Exception("This item is already in RMA process.");
            }

            $pdo->beginTransaction();

            // Create Case
            $stmt = $pdo->prepare("INSERT INTO rma_cases (serial_id, user_id, issue_description) VALUES (?, ?, ?)");
            $stmt->execute([$serial['id'], $user_id, $issue]);

            // Update Serial Status
            $stmt = $pdo->prepare("UPDATE product_serials SET status = 'rma' WHERE id = ?");
            $stmt->execute([$serial['id']]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'update_rma_status') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $rma_id = $_POST['rma_id'];
            $status = $_POST['status'];
            
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE rma_cases SET status = ? WHERE id = ?");
            $stmt->execute([$status, $rma_id]);

            // If done or rejected, release serial status? 
            // If done (replaced/repaired), maybe status becomes 'sold' (back to user) or 'available' (if refunded/restocked - complex logic).
            // For now, let's assume 'done' means returned to customer -> 'sold'.
            if ($status === 'done' || $status === 'rejected') {
                $stmt = $pdo->prepare("SELECT serial_id FROM rma_cases WHERE id = ?");
                $stmt->execute([$rma_id]);
                $sid = $stmt->fetchColumn();

                // Set back to sold (assuming it belongs to a user)
                $stmt = $pdo->prepare("UPDATE product_serials SET status = 'sold' WHERE id = ?");
                $stmt->execute([$sid]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'admin_update_user') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $target_id = $_POST['user_id'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $role = $_POST['role'];
            
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?";
            $params = [$full_name, $email, $phone, $role];

            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $target_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    elseif ($action === 'admin_delete_user') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
             echo json_encode(['success' => false, 'message' => 'Unauthorized']);
             exit();
        }
        try {
            $id = $_POST['user_id'];
            if ($id == $_SESSION['user_id']) {
                throw new Exception("Cannot delete yourself.");
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
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
