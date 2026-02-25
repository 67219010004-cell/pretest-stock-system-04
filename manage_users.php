<?php
session_start();
require 'db.php';

// Check Admin Permission
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch All Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - GAMESHOP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .user-table th, .user-table td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .user-table th { background: rgba(255,255,255,0.05); color: var(--neon-cyan); }
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .role-admin { background: var(--neon-purple); color: white; }
        .role-customer { background: var(--neon-green); color: black; }
    </style>
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
                <li class="nav-item"><a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="header">
                <h1>User Management</h1>
            </header>

            <div class="card">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 30px; height: 30px; border-radius: 50%; overflow: hidden; background: #333;">
                                        <?php if(!empty($u['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($u['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="display:flex; justify-content:center; align-items:center; height:100%; color: #aaa;"><i class="fas fa-user"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['full_name'] ?? '-'); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($u['email'] ?? ''); ?><br>
                                    <?php echo htmlspecialchars($u['phone'] ?? ''); ?>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-primary" style="padding: 0.4rem 0.8rem;" 
                                    onclick="openEditUserModal(<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <button class="btn btn-danger" style="padding: 0.4rem 0.8rem;" onclick="deleteUser(<?php echo $u['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Edit User Modal -->
    <div class="modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <button class="close-btn" onclick="closeEditUserModal()">&times;</button>
            </div>
            <form id="editUserForm">
                <input type="hidden" name="action" value="admin_update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="edit_username" class="form-control" disabled style="opacity: 0.6;">
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_full_name" class="form-control">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label style="color: var(--neon-purple);">Role</label>
                    <select name="role" id="edit_role" class="form-control" style="border-color: var(--neon-purple);">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; margin-top: 1rem;">
                    <label>Reset Password (Optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave empty to keep current password">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditUserModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role;
            
            document.getElementById('editUserModal').classList.add('active');
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.remove('active');
        }

        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('User updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        async function deleteUser(id) {
            if(!confirm('Are you sure you want to delete this user? This cannot be undone.')) return;

            const formData = new FormData();
            formData.append('action', 'admin_delete_user');
            formData.append('user_id', id);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if(data.success) location.reload();
                else alert('Error: ' + data.message);
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the user.');
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editUserModal')) {
                closeEditUserModal();
            }
        }
    </script>
</body>
</html>