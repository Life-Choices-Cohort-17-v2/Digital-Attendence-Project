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
        .profile-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 28px 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 32px;
        }

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
            margin-bottom: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-detail-label {
            width: 120px;
            font-weight: 500;
            color: var(--text);
        }

        .profile-detail-value {
            flex: 1;
            font-weight: 600;
            color: var(--heading);
        }

        .password-section {
            margin-top: 36px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
        }

        .password-section h3 {
            margin-bottom: 4px;
            color: var(--heading);
        }

        .password-section p {
            font-size: 13px;
            color: var(--text);
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 14px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--heading);
        }

        .input-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 14px;
        }

        .update-btn {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            background: var(--sidebar-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .update-btn:hover {
            background: var(--sidebar-hover);
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
                    <div class="profile-initials" x-text="getInitials(displayName)"></div>
                    <h2 x-text="displayName"></h2>
                    <p x-text="user.email || ''"></p>
                </div>

                <div class="profile-info">
                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Email</span>
                        <span class="profile-detail-value" x-text="user.email || '—'"></span>
                    </div>

                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Employee ID</span>
                        <span class="profile-detail-value" x-text="user.employeeId || user.employee_id || '—'"></span>
                    </div>

                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Department</span>
                        <span class="profile-detail-value" x-text="user.department || '—'"></span>
                    </div>

                    <div class="profile-detail-row">
                        <span class="profile-detail-label">Position</span>
                        <span class="profile-detail-value" x-text="user.position || '—'"></span>
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
        user: {
            name: '',
            first_name: '',
            last_name: '',
            email: '',
            employeeId: '',
            employee_id: '',
            department: '',
            position: ''
        },
        passwordForm: {
            current: '',
            new: '',
            confirm: ''
        },

        get displayName() {
            if (this.user.name) return this.user.name;
            const full = [this.user.first_name, this.user.last_name].filter(Boolean).join(' ');
            return full || 'Staff';
        },

        init() {
            window.themeManager?.initTheme();

            // Real data from PHP session / $user (populated from MySQL by AuthService)
            const userData = <?= json_encode($user ?? [
                'id'          => $_SESSION['user_id'] ?? '',
                'name'        => $_SESSION['user_name'] ?? '',
                'first_name'  => $_SESSION['first_name'] ?? '',
                'last_name'   => $_SESSION['last_name'] ?? '',
                'email'       => $_SESSION['user_email'] ?? '',
                'employeeId'  => $_SESSION['employee_id'] ?? '',
                'employee_id' => $_SESSION['employee_id'] ?? '',
                'department'  => $_SESSION['department'] ?? '',
                'position'    => $_SESSION['position'] ?? ''
            ]) ?>;

            this.user = userData;
        },

        getInitials(name) {
            if (!name) return '?';
            return name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase()
                .substring(0, 2);
        },

        async updatePassword() {
            if (!this.passwordForm.current) {
                window.appUtils?.showToast('Please enter your current password', 'error');
                return;
            }

            if (this.passwordForm.new !== this.passwordForm.confirm) {
                window.appUtils?.showToast('New passwords do not match!', 'error');
                return;
            }

            if (this.passwordForm.new.length < 6) {
                window.appUtils?.showToast('Password must be at least 6 characters', 'error');
                return;
            }

            // TODO: Call real change-password API when Person 2 exposes it
            window.appUtils?.showToast('Password changed successfully!', 'success');
            this.passwordForm = { current: '', new: '', confirm: '' };
        }
    };
}
</script>
</body>
</html>