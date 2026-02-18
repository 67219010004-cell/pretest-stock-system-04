<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Addresses
$addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY created_at DESC");
$addr_stmt->execute([$user_id]);
$addresses = $addr_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - GAMESHOP</title>
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
            
            <!-- User Profile Widget -->
            <div class="sidebar-user" style="padding: 1rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem;">
                <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; margin: 0 auto; border: 2px solid var(--neon-cyan); box-shadow: 0 0 10px var(--neon-cyan);">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; color: var(--neon-cyan);">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 0.5rem; color: white; font-weight: bold; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>
                </div>
            </div>

            <ul class="nav-links">
                <li class="nav-item">
                    <a href="index.php">
                        <i class="fas fa-th-large"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-user"></i> Profile
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <h1 class="page-title">My Profile</h1>
                <div style="display: flex; gap: 1rem;">
                    <form action="auth.php" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </header>

            <div class="profile-container">
                <!-- User Info Form -->
                <section class="card profile-card">
                    <h2 class="section-title"><i class="fas fa-id-card"></i> Personal Information</h2>
                    <form id="profileForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <div style="width: 150px; height: 150px; border-radius: 50%; overflow: hidden; margin: 0 auto; border: 3px solid var(--neon-cyan); box-shadow: 0 0 15px var(--neon-cyan);">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--neon-cyan);">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <label for="profileUpload" class="btn btn-sm btn-primary" style="margin-top: 1rem; cursor: pointer;">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                            <input type="file" name="profile_image" id="profileUpload" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity: 0.7">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled style="opacity: 0.7">
                            </div>
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter email">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter phone number">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </section>

                <!-- Address Section (Customers Only usually, but let's allow admins too for testing) -->
                <section class="card address-card" style="margin-top: 2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
                        <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Shipping Addresses</h2>
                        <button class="btn btn-primary" onclick="openAddressModal()">
                            <i class="fas fa-plus"></i> Add Address
                        </button>
                    </div>

                    <div class="address-grid">
                        <?php if (count($addresses) > 0): ?>
                            <?php foreach ($addresses as $addr): ?>
                            <div class="address-item">
                                <div class="addr-header">
                                    <strong><?php echo htmlspecialchars($addr['recipient_name']); ?></strong>
                                    <button class="btn-icon-danger" onclick="deleteAddress(<?php echo $addr['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <p><?php echo htmlspecialchars($addr['address_line']); ?></p>
                                <p><?php echo htmlspecialchars($addr['city'] . ', ' . $addr['postal_code']); ?></p>
                                <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($addr['phone']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No addresses saved yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Add Address Modal -->
    <div class="modal" id="addressModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Address</h2>
                <button class="close-btn" onclick="closeAddressModal()">&times;</button>
            </div>
            <form id="addressForm">
                <input type="hidden" name="action" value="add_address">
                <div class="form-group">
                    <label>Recipient Name</label>
                    <input type="text" name="recipient_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Address Line</label>
                    <textarea name="address_line" class="form-control" rows="2" required></textarea>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%">Save Address</button>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Find the image element to update - searching relatively or by assumptions
                    // Since I added the img tag code above, I know where it is.
                    // But for simplicity, let's just alert/log or assume user sees it after save.
                    // Actually let's make it better.
                    const imgContainer = input.parentElement.querySelector('div');
                    let img = imgContainer.querySelector('img');
                    if (!img) {
                         imgContainer.innerHTML = '<img style="width: 100%; height: 100%; object-fit: cover;">';
                         img = imgContainer.querySelector('img');
                         imgContainer.style.background = 'transparent';
                    }
                    img.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Profile Update
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        // Address Modal
        function openAddressModal() {
            document.getElementById('addressModal').classList.add('active');
        }
        function closeAddressModal() {
            document.getElementById('addressModal').classList.remove('active');
        }

        // Add Address
        document.getElementById('addressForm').addEventListener('submit', function(e) {
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

        // Delete Address
        function deleteAddress(id) {
            if(confirm('Delete this address?')) {
                const formData = new FormData();
                formData.append('action', 'delete_address');
                formData.append('id', id);
                fetch('api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('addressModal')) {
                closeAddressModal();
            }
        }
    </script>
</body>
</html>
