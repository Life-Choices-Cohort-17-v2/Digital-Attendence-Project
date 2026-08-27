<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | SpySee</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal-container {
            background: var(--card-bg);
            border-radius: 24px;
            width: 450px;
            max-width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--border-color);
        }
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--heading);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text);
        }
        .modal-close:hover {
            color: var(--accent);
        }
        .modal-body {
            padding: 24px;
        }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn-submit {
            padding: 10px 24px;
            background: var(--accent);
            color: #202020;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-submit:hover {
            background: var(--primary-green-dark);
            color: #f8f8f8;
        }
        .btn-cancel {
            padding: 10px 24px;
            background: var(--border-color);
            color: var(--heading);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-cancel:hover {
            background: #555;
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
                        <p>Add, edit, or disable staff accounts.</p>
                    </div>
                    <button class="btn-primary" @click="showAddModal = true">+ Add User</button>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>EMPLOYEE ID</th>
                                <th>ROLE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="user in users" :key="user.id">
                                <tr>
                                    <td x-text="user.name"></td>
                                    <td x-text="user.email"></td>
                                    <td x-text="user.employee_id"></td>
                                    <td>
                                        <span class="role-badge-admin" x-show="user.role === 'admin'" x-text="user.role"></span>
                                        <span class="role-badge-staff" x-show="user.role === 'staff'" x-text="user.role"></span>
                                    </td>
                                    <td>
                                        <span class="status-badge-active" x-text="user.status"></span>
                                    </td>
                                    <td class="action-icons">
                                        <button @click="editUser(user)" title="Edit">✏️</button>
                                        <button @click="deleteUser(user.id)" title="Delete">🗑️</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="users.length === 0">
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                    No users found.
                                </td>
                            </tr>
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
                            <div class="input-group">
                                <label>Name</label>
                                <input type="text" x-model="newUser.name" placeholder="Full name">
                            </div>
                            <div class="input-group">
                                <label>Email</label>
                                <input type="email" x-model="newUser.email" placeholder="email@company.com">
                            </div>
                            <div class="input-group">
                                <label>Employee ID</label>
                                <input type="text" x-model="newUser.employee_id" placeholder="e.g. STF-001">
                            </div>
                            <div class="input-group">
                                <label>Role</label>
                                <select x-model="newUser.role">
                                    <option value="staff">Staff</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-cancel" @click="showAddModal = false">Cancel</button>
                            <button class="btn-submit" @click="addUser()">Add User</button>
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
            try {
                const response = await fetch('/api/users');
                const data = await response.json();
                this.users = data.data || [];
            } catch (err) {
                console.error('Error loading users:', err);
                window.appUtils.showToast('Failed to load users', 'error');
            }
        },
        
        async addUser() {
            if (!this.newUser.name || !this.newUser.email) {
                window.appUtils.showToast('Please fill in all fields', 'error');
                return;
            }
            
            const newUser = {
                id: 'user_' + Date.now(),
                name: this.newUser.name,
                email: this.newUser.email,
                employee_id: this.newUser.employee_id || 'EMP-' + String(Math.floor(Math.random() * 1000)).padStart(3, '0'),
                role: this.newUser.role,
                status: 'active'
            };
            
            this.users.push(newUser);
            this.showAddModal = false;
            this.newUser = { name: '', email: '', employee_id: '', role: 'staff' };
            window.appUtils.showToast('User added successfully!', 'success');
        },
        
        editUser(user) {
            window.appUtils.showToast('Edit user: ' + user.name + ' (Coming soon)', 'info');
        },
        
        deleteUser(id) {
            if (confirm('Delete this user?')) {
                this.users = this.users.filter(u => u.id !== id);
                window.appUtils.showToast('User deleted', 'success');
            }
        }
    }
}
</script>

</body>
</html>