<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'User Management' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }

        .modal-actions {
            display: flex;
            justify-content: space-between;
            padding: 24px;
            border-top: 1px solid var(--border-color);
            gap: 16px;
        }

        .modal-actions button {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-add-user {
            background: var(--sidebar-blue);
            color: #FFFFFF;
        }

        body.dark-mode .btn-add-user {
            background: var(--olive-green);
            color: #093C5D;
        }

        .btn-cancel-user {
            background: #DC2626;
            color: #FFFFFF;
        }

        .btn-cancel-user:hover {
            background: #B91C1C;
        }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>
<div x-data="usersApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'users'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <div>
                        <h1>User Management</h1>
                        <p>Add, edit, or disable accounts. Self-registration is disabled.</p>
                    </div>
                    <button class="btn-primary" @click="showAddModal = true">+ Add User</button>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr><th>NAME</th><th>EMAIL</th><th>EMPLOYEE ID</th><th>ROLE</th><th>STATUS</th><th>ACTIONS</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="user in users" :key="user.id">
                                <tr>
                                    <td x-text="user.name"></td>
                                    <td x-text="user.email"></td>
                                    <td x-text="user.employee_id"></td>
                                    <td><span class="role-badge-admin" x-show="user.role === 'admin'" x-text="user.role"></span><span class="role-badge-staff" x-show="user.role === 'staff'" x-text="user.role"></span></td>
                                    <td><span class="status-badge-active" x-text="user.status"></span></td>
                                    <td class="action-icons"><button @click="editUser(user)">✏️</button><button @click="deleteUser(user.id)">🗑️</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Add User Modal -->
                <div class="modal-overlay" x-show="showAddModal" x-cloak @click.away="showAddModal = false">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3>Add New User</h3>
                            <button class="modal-close" @click="showAddModal = false">✕</button>
                        </div>
                        <div class="modal-body">
                        <div class="input-group"><label>Name</label><input type="text" x-model="newUser.name"></div>
                        <div class="input-group"><label>Email</label><input type="email" x-model="newUser.email"></div>
                        <div class="input-group"><label>Employee ID</label><input type="text" x-model="newUser.employee_id"></div>
                        <div class="input-group"><label>Role</label><select x-model="newUser.role"><option value="staff">Staff</option><option value="admin">Admin</option></select></div>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-add-user" @click="addUser()">Add User</button>
                            <button class="btn-cancel-user" @click="showAddModal = false">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function usersApp() {
    return {
        sidebarOpen: false,
        users: [],
        showAddModal: false,
        newUser: { name: '', email: '', employee_id: '', role: 'staff' },
        
        async init() {
            window.themeManager.initTheme();
            await this.loadUsers();
        },
        
        async loadUsers() {
            const response = await fetch('api/users.php');
            const data = await response.json();
            this.users = data.data || [];
        },
        
        async addUser() {
            if (!this.newUser.name || !this.newUser.email) {
                alert('Please fill in all fields');
                return;
            }
            const newId = 'user_' + Date.now();
            this.users.push({
                id: newId,
                name: this.newUser.name,
                email: this.newUser.email,
                employee_id: this.newUser.employee_id || 'EMP-' + Math.floor(Math.random() * 1000),
                role: this.newUser.role,
                status: 'active'
            });
            this.showAddModal = false;
            this.newUser = { name: '', email: '', employee_id: '', role: 'staff' };
            window.appUtils.showToast('User added successfully!', 'success');
        },
        
        editUser(user) {
            alert(`Edit user: ${user.name} (Demo - full CRUD would be implemented here)`);
        },
        
        deleteUser(id) {
            if (window.confirm('Delete this user?')) { // Use window.confirm for explicit confirmation
                this.users = this.users.filter(u => u.id !== id);
                window.appUtils.showToast('User deleted (Demo)', 'success');
            }
        }
    }
}
</script>
</body>
</html>
