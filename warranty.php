<?php
session_start();
require 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warranty Check & RMA</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .warranty-card { max-width: 600px; margin: 3rem auto; padding: 2rem; background: rgba(255, 255, 255, 0.05); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .result-box { margin-top: 1.5rem; padding: 1rem; background: rgba(0, 0, 0, 0.2); border-radius: 8px; display: none; }
        .valid { color: var(--neon-green); }
        .expired { color: var(--neon-red); }
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
                <li class="nav-item"><a href="index.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></li>
                <li class="nav-item"><a href="#" class="active"><i class="fas fa-search"></i> Warranty Check</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                 <h1>Warranty Status & RMA</h1>
            </header>

            <div class="warranty-card">
                <h2 style="margin-bottom: 1.5rem; text-align: center;">Check Your Warranty</h2>
                <form id="checkForm">
                    <input type="hidden" name="action" value="check_warranty">
                    <div class="form-group">
                        <label>Serial Number (S/N)</label>
                        <input type="text" name="serial_number" class="form-control" required placeholder="Enter S/N..." style="text-align: center; font-size: 1.2rem; margin-bottom: 1rem;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                        <i class="fas fa-search"></i> Check Status
                    </button>
                </form>

                <div id="resultBox" class="result-box">
                    <h3 id="productName" style="color: var(--neon-cyan)"></h3>
                    <p>Supplier: <span id="supplierName"></span></p>
                    <p>Warranty End: <span id="warrantyDate"></span> <strong id="warrantyStatus"></strong></p>
                    
                    <div id="rmaSection" style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; display: none;">
                        <div id="activeRmaInfo" style="display: none; color: orange;">
                            <i class="fas fa-exclamation-circle"></i> This item is currently in RMA process.<br>
                            Status: <strong id="rmaStatusText"></strong>
                        </div>
                        
                        <form id="rmaRequestForm" style="display: none;">
                            <h4 style="margin-bottom: 0.5rem; color: var(--neon-red);">Request RMA Claim</h4>
                            <input type="hidden" name="action" value="create_rma">
                            <input type="hidden" name="serial_number" id="rmaSn">
                            <div class="form-group">
                                <label>Issue Description</label>
                                <textarea name="issue_description" class="form-control" required placeholder="Describe the problem..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger" style="width: 100%;">Submit Claim</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('checkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const sn = formData.get('serial_number');

            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                const box = document.getElementById('resultBox');
                if(!data.success) {
                    alert(data.message);
                    box.style.display = 'none';
                    return;
                }
                
                box.style.display = 'block';
                const info = data.data;
                const activeRma = data.active_rma;

                document.getElementById('productName').innerText = info.product_name;
                document.getElementById('supplierName').innerText = info.supplier_name || 'N/A';
                document.getElementById('warrantyDate').innerText = info.warranty_end_date;

                const today = new Date();
                const end = new Date(info.warranty_end_date);
                const statusSpan = document.getElementById('warrantyStatus');
                
                if (end >= today) {
                    statusSpan.innerText = '(Valid)';
                    statusSpan.className = 'valid';
                } else {
                    statusSpan.innerText = '(Expired)';
                    statusSpan.className = 'expired';
                }

                // RMA Logic
                const rmaSec = document.getElementById('rmaSection');
                const rmaForm = document.getElementById('rmaRequestForm');
                const activeInfo = document.getElementById('activeRmaInfo');
                
                rmaSec.style.display = 'block';
                document.getElementById('rmaSn').value = sn;

                if (activeRma) {
                    activeInfo.style.display = 'block';
                    document.getElementById('rmaStatusText').innerText = activeRma.status.toUpperCase();
                    rmaForm.style.display = 'none';
                } else if (end >= today) {
                    activeInfo.style.display = 'none';
                    rmaForm.style.display = 'block';
                } else {
                    activeInfo.style.display = 'none';
                    rmaForm.style.display = 'none';
                    rmaSec.innerHTML += '<p style="color: red; margin-top: 1rem;">Warranty Expired. Cannot claim.</p>';
                }
            });
        });

        document.getElementById('rmaRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if(!confirm('Are you sure you want to submit a warranty claim?')) return;

            const formData = new FormData(this);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('RMA Request Submitted!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
