<?php
require 'db.php';

// Fetch Statistics
try {
    // Total Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total_products = $stmt->fetchColumn();

    // Total Value
    $stmt = $pdo->query("SELECT SUM(price * stock_quantity) FROM products");
    $total_value = $stmt->fetchColumn();

    // Low Stock Items (< 5)
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 5");
    $low_stock_count = $stmt->fetchColumn();

    // Fetch Products with Category Name
    $sql = "SELECT p.*, c.name as category_name, c.icon as category_icon 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();

    // Fetch Categories for Form
    $cat_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $cat_stmt->fetchAll();

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAMESHOP Stock System</title>
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
                    <a href="#" class="active">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-box"></i> Inventory
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1 class="page-title">Dashboard Overview</h1>
                <button class="btn btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Products</div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Total Inventory Value</div>
                    <div class="stat-value" style="color: var(--neon-green)">
                        ฿<?php echo number_format($total_value, 0); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Low Stock Alerts</div>
                    <div class="stat-value" style="color: var(--neon-red)">
                        <?php echo $low_stock_count; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">System Status</div>
                    <div class="stat-value" style="font-size: 1.5rem; color: var(--neon-cyan)">
                        ONLINE <i class="fas fa-wifi" style="font-size: 1rem"></i>
                    </div>
                </div>
            </div>

            <h2 style="margin-bottom: 1.5rem;">Recent Inventory</h2>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php echo $product['category_icon']; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category"><?php echo $product['category_name']; ?></div>
                        <div class="product-name"><?php echo $product['name']; ?></div>
                        
                        <?php 
                        $status_class = 'in-stock';
                        $status_text = 'In Stock';
                        if ($product['stock_quantity'] == 0) {
                            $status_class = 'out-of-stock';
                            $status_text = 'Out of Stock';
                        } elseif ($product['stock_quantity'] < 5) {
                            $status_class = 'low-stock';
                            $status_text = 'Low Stock';
                        }
                        ?>
                        
                        <div style="margin-bottom: 0.5rem;">
                            <span class="stock-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?> (<?php echo $product['stock_quantity']; ?>)
                            </span>
                        </div>

                        <div class="product-meta">
                            <div class="product-price">฿<?php echo number_format($product['price']); ?></div>
                            <button class="btn btn-danger" style="padding: 0.5rem;" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Add Product Modal -->
    <div class="modal" id="addProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Product</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="addProductForm" action="api.php" method="POST">
                <input type="hidden" name="action" value="add_product">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. RTX 4090">
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo $cat['icon'] . ' ' . $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price (฿)</label>
                    <input type="number" name="price" class="form-control" required min="0">
                </div>

                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock_quantity" class="form-control" required min="0">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Confirm Add Product
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addProductModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('addProductModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addProductModal')) {
                closeModal();
            }
        }

        // Handle Form Submit
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });

        function deleteProduct(id) {
            if(confirm('Are you sure you want to delete this item?')) {
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('id', id);

                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting');
                    }
                });
            }
        }
    </script>
</body>
</html>