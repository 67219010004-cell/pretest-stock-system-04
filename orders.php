<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch Orders
if ($role === 'admin') {
    $sql = "SELECT o.*, u.username, u.full_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    $stmt = $pdo->query($sql);
} else {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - GAMESHOP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="brand">
                <i class="fas fa-microchip"></i>
                <span>GAME</span>SHOP
            </div>
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="index.php">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-box"></i> Orders
                    </a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <h1 class="page-title"><?php echo $role === 'admin' ? 'Manage Orders' : 'My Orders'; ?></h1>
            </header>

            <div class="card">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <?php if ($role === 'admin'): ?><th>User</th><?php endif; ?>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Items</th>
                            <?php if ($role === 'admin'): ?><th>Action</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="stock-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>฿<?php echo number_format($order['total_amount']); ?></td>
                            <td>
                                <?php
                                    // Fetch Items for this order (simple implementation)
                                    // In a real app, optimize this to avoid N+1 query
                                    $item_stmt = $pdo->prepare("SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE order_id = ?");
                                    $item_stmt->execute([$order['id']]);
                                    $items = $item_stmt->fetchAll();
                                    foreach ($items as $item) {
                                        echo "<div>{$item['quantity']}x {$item['name']}</div>";
                                    }
                                ?>
                            </td>
                            <?php if ($role === 'admin'): ?>
                            <td>
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-primary" style="padding: 0.4rem;" onclick="updateStatus(<?php echo $order['id']; ?>, 'shipped')">
                                    Ship
                                </button>
                                <?php endif; ?>
                                <?php if ($order['status'] !== 'cancelled'): ?>
                                <button class="btn btn-danger" style="padding: 0.4rem;" onclick="updateStatus(<?php echo $order['id']; ?>, 'cancelled')">
                                    Cancel
                                </button>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function updateStatus(id, status) {
            if(!confirm('Change order status to ' + status + '?')) return;
            
            const formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('order_id', id);
            formData.append('status', status);

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert('Error: ' + data.message);
            });
        }
    </script>
</body>
</html>
