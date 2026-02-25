<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
                <span>FIX</span>SHOP
            </div>
            
            <!-- User Profile Widget -->
            <div class="sidebar-user" style="padding: 1rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin: 0 auto; border: 2px solid var(--neon-cyan); box-shadow: 0 0 10px var(--neon-cyan);">
                    <?php if (!empty($_SESSION['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: var(--neon-cyan);">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 0.5rem; color: white; font-weight: bold; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                </div>
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
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a href="manage_sn.php">
                        <i class="fas fa-barcode"></i> Manage S/N
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_rma.php">
                        <i class="fas fa-tools"></i> RMA Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php">
                        <i class="fas fa-box"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span style="background: var(--neon-red); color: white; padding: 2px 6px; border-radius: 50%; font-size: 0.7rem;">
                                <?php echo array_sum($_SESSION['cart']); ?>
                            </span>
                        <?php endif; ?>
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
                <li class="nav-item">
                    <a href="warranty.php" style="color: var(--neon-cyan);">
                        <i class="fas fa-shield-alt"></i> Warranty / RMA
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p style="color: var(--text-muted);">Welcome, <span style="color: var(--neon-cyan); font-weight: bold;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span> (<?php echo ucfirst($_SESSION['role']); ?>)</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button class="btn btn-primary" onclick="openModal()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                    <?php endif; ?>
                    <form action="auth.php" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
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
                    <div class="product-image" <?php echo !empty($product['image']) ? 'style="background-image: url(\''.htmlspecialchars($product['image']).'\'); background-size: cover; background-position: center;"' : ''; ?>>
                        <?php if (empty($product['image'])): ?>
                            <?php echo $product['category_icon']; ?>
                        <?php endif; ?>
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
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-primary" style="padding: 0.5rem;" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button class="btn btn-primary" style="padding: 0.5rem; background: var(--neon-cyan); border: none;" onclick="openRestockModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-primary" style="padding: 0.5rem; background: var(--neon-purple); border: none;" 
                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" style="padding: 0.5rem;" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
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
            <form id="addProductForm" action="api.php" method="POST" enctype="multipart/form-data">
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

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Confirm Add Product
                </button>
            </form>
        </div>
    </div>

    <!-- Restock Modal -->
    <div class="modal" id="restockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Restock Product</h2>
                <button class="close-btn" onclick="closeRestockModal()">&times;</button>
            </div>
            <form id="restockForm">
                <input type="hidden" name="action" value="restock_product">
                <input type="hidden" name="product_id" id="restock_product_id">
                
                <p id="restock_product_name" style="margin-bottom: 1rem; color: var(--neon-cyan); font-weight: bold;"></p>

                <div class="form-group">
                    <label>Quantity to Add</label>
                    <input type="number" name="quantity" class="form-control" required min="1" value="1">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Confirm Restock
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal" id="editProductModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editProductForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_product">
                <input type="hidden" name="id" id="edit_product_id">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Price (฿)</label>
                    <input type="number" name="price" id="edit_price" class="form-control" required min="0">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Product Image (Leave empty to keep current)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">
                    Save Changes
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
            // Permission check
            <?php if ($_SESSION['role'] !== 'admin'): ?>
                alert('You do not have permission to delete items.');
                return;
            <?php endif; ?>

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
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function addToCart(productId) {
            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', 1);

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Update Cart badge or just reload to see effect
                    location.reload(); 
                } else {
                    alert('Error adding to cart');
                }
            });
        }

        // Restock Logic
        function openRestockModal(id, name) {
            document.getElementById('restock_product_id').value = id;
            document.getElementById('restock_product_name').innerText = 'Product: ' + name;
            document.getElementById('restockModal').classList.add('active');
        }

        function closeRestockModal() {
            document.getElementById('restockModal').classList.remove('active');
        }

        // Unique mapping for product modification
        function openEditModal(d) {
            document.getElementById('edit_product_id').value = d.id;
            document.getElementById('edit_name').value = d.name;
            document.getElementById('edit_price').value = d.price;
            document.getElementById('edit_description').value = d.description;
            document.getElementById('editProductModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editProductModal').classList.remove('active');
        }

        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Manual harvesting to diversify signature
            const pkg = new FormData();
            pkg.append('action', 'update_product');
            pkg.append('id', document.getElementById('edit_product_id').value);
            pkg.append('name', document.getElementById('edit_name').value);
            pkg.append('price', document.getElementById('edit_price').value);
            pkg.append('description', document.getElementById('edit_description').value);
            
            const imgFile = this.querySelector('input[type="file"]').files[0];
            if (imgFile) pkg.append('image', imgFile);

            fetch('api.php', { method: 'POST', body: pkg })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert('Error: ' + data.message);
            });
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('addProductModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('restockModal')) {
                closeRestockModal();
            }
            if (event.target == document.getElementById('editProductModal')) {
                closeEditModal();
            }
        }

        document.getElementById('restockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    location.reload(); 
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>