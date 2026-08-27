```php
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
        /* ============================================================
           PROFILE PAGE
           ============================================================ */

        .profile-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 32px;
            box-sizing: border-box;
        }

        .profile-header {
            width: 100%;
            text-align: center;
            margin-bottom: 32px;
        }

        .profile-detail-section {
            width: 100%;
            margin-bottom: 24px;
        }

        .profile-detail-section h3 {
            margin-bottom: 12px;
        }

        .profile-detail-container {
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            box-sizing: border-box;
        }

        .profile-detail-row {
            display: grid;
            grid-template-columns: 120px minmax(0, 1fr);
            align-items: center;
            column-gap: 20px;
            width: 100%;
            padding: 12px 0;
            box-sizing: border-box;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-detail-row:last-child {
            border-bottom: none;
        }

        .profile-detail-label {
            min-width: 0;
            font-weight: 500;
            color: var(--text);
        }

        .profile-detail-value {
            min-width: 0;
            font-weight: 600;
            color: var(--heading);
            overflow-wrap: break-word;
        }

        /* ============================================================
           CHANGE PASSWORD
           This is intentionally inside .profile-container so it
           shares the exact same width as Profile Details.
           ============================================================ */

        .password-section {
            width: 100%;
            margin: 0 0 24px;
            padding: 24px;
            box-sizing: border-box;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
        }

        .password-section h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 8px;
        }

        .password-section p {
            color: var(--text);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .password-section .input-group {
            width: 100%;
            margin-bottom: 14px;
            box-sizing: border-box;
        }

        .password-section .input-group label {
            display: block;
            margin-bottom: 6px;
        }

        .password-section .input-group input {
            display: block;
            width: 100%;
            max-width: none;
            box-sizing: border-box;
            padding: 10px 12px;
            font-size: 14px;
        }

        .password-section .update-btn {
            display: block;
            width: 100%;
            max-width: none;
            box-sizing: border-box;
        }

        [x-cloak] {
            display: none !important;
        }

        /* ============================================================
           MOBILE
           ============================================================ */

        @media (max-width: 600px) {
            .profile-container {
                max-width: 100%;
                padding: 24px 16px;
            }

            .profile-detail-container,
            .password-section {
                padding: 20px;
            }

            .profile-detail-row {
                grid-template-columns: 100px minmax(0, 1fr);
                column-gap: 12px;
            }
        }
    </style>
</head>

<body>

<div
    x-data="profileApp()"
    x-init="init()"
    @keydown.escape="sidebarOpen = false"
    x-cloak
>
    <div class="app-layout">

        <?php
        $activePage = 'profile';
        include __DIR__ . '/staff-sidebar.php';
        ?>

        <main class="main-content">

            <?php
            if (file_exists(__DIR__ . '/../partials/top-nav.php')) {
                include __DIR__ . '/../partials/top-nav.php';
            }
            ?>

            <!-- ONE shared container for the entire profile page -->
            <div class="profile-container">

                <!-- Profile header -->
                <div class="profile-header">
                    <div
                        class="profile-initials"
                        x-text="getInitials(user.name)"
                    ></div>

                    <h2 x-text="user.name"></h2>
                    <p x-text="user.email"></p>
                </div>


                <!-- Profile details -->
                <div class="profile-detail-section">

                    <div class="profile-detail-container">

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Name</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.name"
                            ></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Email</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.email"
                            ></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Employee ID</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.employeeId || 'EMP001'"
                            ></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Role</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.role || 'Staff'"
                            ></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Department</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.department || 'Not assigned'"
                            ></span>
                        </div>

                        <div class="profile-detail-row">
                            <span class="profile-detail-label">Position</span>
                            <span
                                class="profile-detail-value"
                                x-text="user.position || 'Not assigned'"
                            ></span>
                        </div>

                    </div>
                </div>


                <!-- Change password -->
                <!-- IMPORTANT: This remains INSIDE profile-container -->
                <div class="password-section">

                    <h3>Change password</h3>

                    <p>Update your password securely.</p>

                    <div class="input-group">
                        <label>Current password</label>

                        <input
                            type="password"
                            x-model="passwordForm.current"
                            placeholder="Enter current password"
                        >
                    </div>

                    <div class="input-group">
                        <label>New password</label>

                        <input
                            type="password"
                            x-model="passwordForm.new"
                            placeholder="Enter new password"
                        >
                    </div>

                    <div class="input-group">
                        <label>Confirm new password</label>

                        <input
                            type="password"
                            x-model="passwordForm.confirm"
                            placeholder="Confirm new password"
                        >
                    </div>

                    <button
                        class="update-btn"
                        @click="updatePassword()"
                    >
                        Update password
                    </button>

                </div>

            </div>
            <!-- END profile-container -->

        </main>

    </div>
</div>


<script>
function profileApp() {
    return {
        sidebarOpen: false,

        user: {
            name: '',
            email: '',
            employeeId: '',
            role: '',
            department: '',
            position: ''
        },

        passwordForm: {
            current: '',
            new: '',
            confirm: ''
        },

        init() {
            const userData = <?php
                echo json_encode(
                    $user ?? [
                        'id' => 'staff-001',
                        'name' => 'Sarah Mthembu',
                        'email' => 'sarah@spysee.app',
                        'employeeId' => 'S-101',
                        'role' => 'staff',
                        'department' => '',
                        'position' => ''
                    ]
                );
            ?>;

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
                window.appUtils.showToast(
                    'Please enter your current password',
                    'error'
                );
                return;
            }

            if (this.passwordForm.new !== this.passwordForm.confirm) {
                window.appUtils.showToast(
                    'New passwords do not match!',
                    'error'
                );
                return;
            }

            if (this.passwordForm.new.length < 6) {
                window.appUtils.showToast(
                    'Password must be at least 6 characters',
                    'error'
                );
                return;
            }

            if (this.passwordForm.current === this.passwordForm.new) {
                window.appUtils.showToast(
                    'New password must be different from the current password',
                    'error'
                );
                return;
            }

            try {

                const response = await fetch(
                    <?= json_encode(route_url('/index.php/api/profile/password')) ?>,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            current_password: this.passwordForm.current,
                            new_password: this.passwordForm.new
                        })
                    }
                );

                const data = await response.json();

                if (!data.success) {
                    window.appUtils.showToast(
                        data.message || 'Unable to update password',
                        'error'
                    );
                    return;
                }

                window.appUtils.showToast(
                    'Password changed successfully!',
                    'success'
                );

                this.passwordForm = {
                    current: '',
                    new: '',
                    confirm: ''
                };

            } catch (error) {

                console.error(
                    'Error updating password:',
                    error
                );

                window.appUtils.showToast(
                    'Unable to update password',
                    'error'
                );
            }
        }
    }
}
</script>

</body>
</html>