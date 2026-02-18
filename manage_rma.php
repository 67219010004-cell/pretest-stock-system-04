<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch RMA Cases
$sql = "SELECT r.*, ps.serial_number, p.name as product_name, u.username as customer_name, s.name as supplier_name
        FROM rma_cases r
        JOIN product_serials ps ON r.serial_id = ps.id
        JOIN products p ON ps.product_id = p.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN suppliers s ON ps.supplier_id = s.id
        ORDER BY r.created_at DESC";
$rmas = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage RMA Cases</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rma-card { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; margin-bottom: 1rem; padding: 1rem; }
        .rma-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem; margin-bottom: 0.5rem; }
        .rma-status { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }
        .status-received { background: #3498db; color: white; }
        .status-checking { background: #f1c40f; color: black; }
        .status-vendor_claim { background: #e67e22; color: white; }
        .status-returning { background: #9b59b6; color: white; }
        .status-done { background: #2ecc71; color: white; }
        .status-rejected { background: #e74c3c; color: white; }
        .workflow-btn { margin-right: 5px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <nav class="sidebar">
            <div class="brand">
                <i class="fas fa-microchip"></i>
                <span>GAME</span>SHOP
            </div>
            <ul class="nav-links">
                <li class="nav-item"><a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-box"></i> Orders</a></li>
                <li class="nav-item"><a href="manage_sn.php"><i class="fas fa-barcode"></i> Manage S/N</a></li>
                <li class="nav-item"><a href="#" class="active"><i class="fas fa-tools"></i> RMA Cases</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <h1>RMA Management</h1>
            </header>

            <?php if (empty($rmas)): ?>
                <p class="text-muted">No active RMA cases.</p>
            <?php else: ?>
                <?php foreach ($rmas as $rma): ?>
                <div class="rma-card">
                    <div class="rma-header">
                        <div>
                            <strong style="color: var(--neon-cyan); font-size: 1.1rem;">#RMA-<?php echo $rma['id']; ?></strong>
                            <span style="margin-left: 10px; color: var(--text-muted);"><?php echo $rma['created_at']; ?></span>
                        </div>
                        <span class="rma-status status-<?php echo $rma['status']; ?>">
                            <?php echo str_replace('_', ' ', $rma['status']); ?>
                        </span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <p><strong>Product:</strong> <?php echo $rma['product_name']; ?></p>
                            <p><strong>S/N:</strong> <code style="background: #333; padding: 2px 4px;"><?php echo $rma['serial_number']; ?></code></p>
                            <p><strong>Customer:</strong> <?php echo $rma['customer_name'] ?? 'Guest'; ?></p>
                            <p><strong>Supplier:</strong> <?php echo $rma['supplier_name']; ?></p>
                        </div>
                        <div>
                            <p><strong>Issue:</strong></p>
                            <p style="background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 4px;"><?php echo nl2br(htmlspecialchars($rma['issue_description'])); ?></p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <strong>Update Status:</strong><br>
                        <?php 
                        $statuses = ['received', 'checking', 'vendor_claim', 'returning', 'done', 'rejected'];
                        foreach ($statuses as $s): 
                        ?>
                            <button class="btn btn-primary workflow-btn" onclick="updateRma(<?php echo $rma['id']; ?>, '<?php echo $s; ?>')" 
                                <?php echo ($rma['status'] === $s) ? 'disabled style="opacity: 0.5"' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function updateRma(id, status) {
            if(!confirm('Update status to ' + status + '?')) return;

            const formData = new FormData();
            formData.append('action', 'update_rma_status');
            formData.append('rma_id', id);
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
