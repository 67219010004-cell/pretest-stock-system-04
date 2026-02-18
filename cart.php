<?php
session_start();
require 'db.php';

// Calculate Totals
$cart = $_SESSION['cart'] ?? [];
$cart_items = [];
$total_price = 0;

if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll();

    foreach ($products as $p) {
        $p['qty'] = $cart[$p['id']];
        $p['subtotal'] = $p['price'] * $p['qty'];
        $total_price += $p['subtotal'];
        $cart_items[] = $p;
    }
}

// Fetch Addresses if logged in
$addresses = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $addresses = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - GAMESHOP</title>
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
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Shopping Cart</h1>
            </header>

            <div class="cart-container card">
                <?php if (empty($cart_items)): ?>
                    <div style="text-align: center; padding: 3rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p>Your cart is empty.</p>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Go Shopping</a>
                    </div>
                <?php else: ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>฿<?php echo number_format($item['price']); ?></td>
                                <td><?php echo $item['qty']; ?></td>
                                <td>฿<?php echo number_format($item['subtotal']); ?></td>
                                <td>
                                    <button class="btn-icon-danger" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                <td style="font-size: 1.2rem; color: var(--neon-green); font-weight: bold;">
                                    ฿<?php echo number_format($total_price); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="checkout-section">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <h2 class="section-title">Checkout</h2>
                            <form id="checkoutForm">
                                <input type="hidden" name="action" value="checkout">
                                <div class="form-group">
                                    <label>Select Shipping Address</label>
                                    <select name="address_id" class="form-control" required>
                                        <option value="">-- Choose Address --</option>
                                        <?php foreach ($addresses as $addr): ?>
                                            <option value="<?php echo $addr['id']; ?>">
                                                <?php echo htmlspecialchars($addr['recipient_name'] . ' - ' . $addr['city']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($addresses)): ?>
                                        <p class="text-muted" style="margin-top: 0.5rem;">
                                            No addresses found. <a href="profile.php" style="color: var(--neon-cyan);">Add one in Profile</a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn btn-primary" <?php echo empty($addresses) ? 'disabled' : ''; ?>>
                                    Confirm Order
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                Please <a href="login.php" style="color: inherit; text-decoration: underline;">Login</a> to checkout.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function removeFromCart(id) {
            const formData = new FormData();
            formData.append('action', 'remove_from_cart');
            formData.append('product_id', id);

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
            });
        }

        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Order Placed Successfully! Order ID: ' + data.order_id);
                    window.location.href = 'orders.php';
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
