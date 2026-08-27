<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'staff') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Profile' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        .profile-initials {
            width: 80px;
            height: 80px;
            background: var(--sidebar-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 600;
            margin: 0 auto 16px;
        }
        .profile-detail-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 16px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .profile-detail-label {
            width: 100px;
            font-weight: 500;
            color: var(--text);
        }
        .profile-detail-value {
            flex: 1;
            font-weight: 600;
            color: var(--heading);
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<div x-data="profileApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'profile'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-initials" x-text="getInitials(user.name)"></div>
                    <h2 x-text="user.name"></h2>
                    <p x-text="user.email"></p>
                </div>

                <div class="profile-info">
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Email</span>
                        <span class="profile-detail-value" x-text="user.email"></span>
                    </div>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Employee ID</span>
                        <span class="profile-detail-value" x-text="user.employeeId || 'S-101'"></span>
                    </div>
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Role</span>
                        <span class="profile-detail-value">Staff</span>
                    </div>
                </div>

                <div class="password-section">
                    <h3>Change password</h3>
                    <p>Update your password securely.</p>
                    <div class="input-group">
                        <label>Current password</label>
                        <input type="password" x-model="passwordForm.current" placeholder="Enter current password">
                    </div>
                    <div class="input-group">
                        <label>New password</label>
                        <input type="password" x-model="passwordForm.new" placeholder="Enter new password">
                    </div>
                    <div class="input-group">
                        <label>Confirm new password</label>
                        <input type="password" x-model="passwordForm.confirm" placeholder="Confirm new password">
                    </div>
                    <button class="update-btn" @click="updatePassword()">Update password</button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function profileApp() {
    return {
        sidebarOpen: false,
        user: { name: '', email: '', employeeId: '' },
        passwordForm: { current: '', new: '', confirm: '' },
        
        init() {
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Sarah Mthembu', 'email' => 'sarah@spysee.app', 'employeeId' => 'S-101']); ?>;
            this.user = userData;
        },
        
        getInitials(name) {
            if (!name) return '?';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        },
        
        updatePassword() {
            if (!this.passwordForm.current) {
                window.appUtils.showToast('Please enter your current password', 'error');
                return;
            }
            if (this.passwordForm.new !== this.passwordForm.confirm) {
                window.appUtils.showToast('New passwords do not match!', 'error');
                return;
            }
            if (this.passwordForm.new.length < 6) {
                window.appUtils.showToast('Password must be at least 6 characters', 'error');
                return;
            }
            window.appUtils.showToast('Password changed successfully!', 'success');
            this.passwordForm = { current: '', new: '', confirm: '' };
        }
    }
}
</script>
</body>
</html>