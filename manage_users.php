<?php
session_start();
require 'db.php';
define('GAMESHOP_ADMIN', true);

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
        .hidden-noise { display: none !important; visibility: hidden; }
        /* Interface Overrides */
        .sys-pop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; pointer-events: none; transition: 0.3s; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); }
        .sys-pop.active { opacity: 1; pointer-events: all; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const registry = {
                ui: () => document.getElementById('panel_interface_ref'),
                form: () => document.getElementById('data_sync_form_pkg')
            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('.action-edit-trigger');
                if (!trigger) return;

                const overlay = registry.ui();
                if (overlay) {
                    const d = trigger.dataset;
                    // Individual assignments with unique naming
                    document.getElementById('ref_field_00').value = d.uid || '';
                    document.getElementById('ref_field_01').value = d.uname || '';
                    document.getElementById('ref_field_02').value = d.fname || '';
                    document.getElementById('ref_field_03').value = d.uemail || '';
                    document.getElementById('ref_field_04').value = d.uphone || '';
                    document.getElementById('ref_field_05').value = d.urole || '';
                    
                    overlay.classList.add('active');
                }
            });

            const sub = registry.form();
            if (sub) {
                sub.onsubmit = async function(e) {
                    e.preventDefault();
                    
                    // Manually harvesting data to ensure decoupled inputs are captured
                    const payload = new FormData();
                    payload.append('action', 'admin_update_user');
                    payload.append('user_id', document.getElementById('ref_field_00').value);
                    payload.append('full_name', document.getElementById('ref_field_02').value);
                    payload.append('email', document.getElementById('ref_field_03').value);
                    payload.append('phone', document.getElementById('ref_field_04').value);
                    payload.append('role', document.getElementById('ref_field_05').value);
                    
                    const passInput = document.querySelector('input[name="password"]');
                    if (passInput) payload.append('password', passInput.value);
                    
                    try {
                        const r = await fetch('api.php', { method: 'POST', body: payload });
                        const res = await r.json();
                        if (res.success) {
                            alert('Update verified.');
                            location.reload();
                        } else alert('Issue: ' + res.message);
                    } catch (err) { console.error('UX Error', err); }
                };
            }

            window.addEventListener('mousedown', (e) => {
                const ov = registry.ui();
                if (e.target === ov) ov.classList.remove('active');
            });
        });

        async function deleteUser(id) {
            if(!confirm('Delete this record?')) return;
            const pkg = new FormData();
            pkg.append('action', 'admin_delete_user');
            pkg.append('user_id', id);
            const r = await fetch('api.php', { method: 'POST', body: pkg });
            const d = await r.json();
            if(d.success) location.reload();
        }
    </script>
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
                                <button class="btn btn-primary action-edit-trigger" 
                                    data-uid="<?php echo $u['id']; ?>"
                                    data-uname="<?php echo htmlspecialchars($u['username']); ?>"
                                    data-fname="<?php echo htmlspecialchars($u['full_name'] ?? ''); ?>"
                                    data-uemail="<?php echo htmlspecialchars($u['email'] ?? ''); ?>"
                                    data-uphone="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>"
                                    data-urole="<?php echo htmlspecialchars($u['role']); ?>">
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

    <?php include 'user_modifier_form.php'; ?>
    <script>
        function terminateEditor() {
            const el = document.getElementById('panel_interface_ref');
            if (el) el.classList.remove('active');
        }
    </script>
</body>
</html>