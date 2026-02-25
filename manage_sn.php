<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch Suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

// Fetch Products
$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage S/N & Suppliers</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .card { background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: var(--neon-cyan); }
        textarea { width: 100%; background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.1); color: white; padding: 0.5rem; border-radius: 4px; }
        .sn-input { height: 150px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Re-use Sidebar if possible, or simple back link -->
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
                    <a href="index.php">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php">
                        <i class="fas fa-box"></i> Inventory
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a href="manage_sn.php" class="active">
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
                    </a>
                </li>
                <li class="nav-item">
                    <a href="warranty.php" style="color: var(--neon-cyan);">
                        <i class="fas fa-shield-alt"></i> Warranty / RMA
                    </a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <h1>Serial Number & Supplier Management</h1>
            </header>

            <div class="grid">
                <!-- Add Supplier -->
                <div class="card">
                    <h2><i class="fas fa-truck"></i> Add Supplier</h2>
                    <form id="addSupplierForm">
                        <input type="hidden" name="action" value="add_supplier">
                        <div class="form-group">
                            <label>Supplier Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Synnex">
                        </div>
                        <div class="form-group">
                            <label>Contact Info</label>
                            <input type="text" name="contact_info" class="form-control" placeholder="Phone, Email, etc.">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </form>
                    
                    <h3 style="margin-top: 2rem;">Existing Suppliers</h3>
                    <ul style="list-style: none; margin-top: 1rem;">
                        <?php foreach ($suppliers as $s): ?>
                            <li style="padding: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <strong><?php echo htmlspecialchars($s['name']); ?></strong> 
                                <span class="text-muted text-sm"><?php echo htmlspecialchars($s['contact_info']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Restock with S/N -->
                <div class="card">
                    <h2><i class="fas fa-boxes"></i> Restock with S/N</h2>
                    <form id="restockSNForm">
                        <input type="hidden" name="action" value="restock_product">
                        
                        <div class="form-group">
                            <label>Product</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">-- Select Product --</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value="">-- Select Supplier --</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Serial Numbers (One per line)</label>
                            <textarea name="serials_text" class="sn-input" required placeholder="SN12345
SN67890
..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="background: var(--neon-green);">
                            Restock & Scan S/N
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('addSupplierForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) location.reload();
                else alert(data.message);
            });
        });

        document.getElementById('restockSNForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'restock_product');
            formData.append('product_id', this.product_id.value);
            formData.append('supplier_id', this.supplier_id.value);
            
            // Convert newline separated text to array
            const text = this.serials_text.value;
            const serials = text.split('\n').filter(line => line.trim() !== '');
            formData.append('serials', JSON.stringify(serials));

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Restock Successful!');
                    location.reload();
                }
                else alert('Error: ' + data.message);
            });
        });
    </script>
</body>
</html>
