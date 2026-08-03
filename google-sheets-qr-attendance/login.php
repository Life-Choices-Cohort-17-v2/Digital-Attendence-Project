<?php
require_once 'config.php';
session_start();

// --- Check if already logged in ---
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
        exit;
    } elseif (isStaff()) {
        if (!empty($_SESSION['redirect_after_login'])) {
            $redirectUrl = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirectUrl);
            exit;
        }
        header('Location: index.php');
        exit;
    }
}

$error = '';
$redirectFromScan = isset($_GET['redirect']) && $_GET['redirect'] === 'scan';

// --- Handle login ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // --- 1. Fetch credentials from Google Sheets ---
    $url = APP_SCRIPT_URL . '?action=getCredentials';
    $response = @file_get_contents($url);
    $data = json_decode($response, true);

    if ($data && $data['success']) {
        $staffFound = false;
        $adminFound = false;

        // --- 2. Check Staff list from Sheets ---
        foreach ($data['staff'] as $staff) {
            if ($staff['staff_id'] === $username && $staff['pin'] === $password && $staff['active'] === 'YES') {
                session_destroy();
                session_start();
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'staff';
                $_SESSION['staff_id'] = $staff['staff_id'];
                $_SESSION['staff_name'] = $staff['name'];
                $staffFound = true;
                
                if (!empty($_SESSION['redirect_after_login'])) {
                    $redirectUrl = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirectUrl);
                    exit;
                }
                header('Location: index.php');
                exit;
            }
        }

        // --- 3. Check Admin list from Sheets ---
        foreach ($data['admins'] as $admin) {
            if ($admin['admin_id'] === $username && $admin['password'] === $password) {
                session_destroy();
                session_start();
                $_SESSION['logged_in'] = true;
                $_SESSION['user_type'] = 'admin';
                $_SESSION['staff_id'] = $admin['admin_id'];
                $_SESSION['staff_name'] = $admin['name'];
                $adminFound = true;

                if (!empty($_SESSION['redirect_after_login'])) {
                    $redirectUrl = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirectUrl);
                    exit;
                }
                header('Location: admin.php');
                exit;
            }
        }

        if (!$staffFound && !$adminFound) {
            $error = '❌ Invalid ID or PIN. Please try again.';
        }
    } else {
        $error = '❌ Could not connect to the database (Sheets). Check your config URL.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
<title>Attendance System Login</title>
<style>
    :root {
        --primary: #2f6f4f;
        --primary-light: #e9f4ee;
        --ink: #1b1f23;
        --paper: #fafaf8;
        --line: #dcdcd6;
        --muted: #6b6f76;
        --radius: 16px;
        --shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--paper);
        color: var(--ink);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .container {
        max-width: 420px;
        width: 100%;
        background: #fff;
        border-radius: var(--radius);
        padding: 32px 28px;
        box-shadow: var(--shadow);
        border: 1px solid var(--line);
    }
    .logo { text-align: center; margin-bottom: 24px; }
    .logo h1 { font-size: 28px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
    .logo p { color: var(--muted); font-size: 14px; margin-top: 4px; }
    .qr-badge {
        display: inline-block;
        background: var(--primary-light);
        color: var(--primary);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    .tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 24px;
    }
    .tab-btn {
        padding: 10px;
        border: 2px solid var(--line);
        border-radius: 10px;
        background: transparent;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--muted);
        touch-action: manipulation;
    }
    .tab-btn.active { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
    .tab-btn:active { transform: scale(0.97); }
    
    .login-form { display: block; }
    .login-form.hidden { display: none; }
    
    label { display: block; font-size: 13px; font-weight: 600; color: var(--muted); margin-bottom: 4px; }
    input[type="text"], input[type="password"] {
        width: 100%; padding: 14px 16px; border: 2px solid var(--line); border-radius: 10px;
        font-size: 16px; margin-bottom: 16px; background: var(--paper); transition: border-color 0.2s;
        font-family: inherit;
    }
    input:focus { outline: none; border-color: var(--primary); background: #fff; }
    input.pin-input { text-align: center; letter-spacing: 8px; font-size: 20px; }
    
    .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: opacity 0.15s, transform 0.1s; touch-action: manipulation; }
    .btn:active { transform: scale(0.97); }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { opacity: 0.9; }
    
    .error {
        background: #fbeceb; color: #a3432f; padding: 12px 16px; border-radius: 10px;
        font-size: 14px; margin-bottom: 16px; text-align: center;
    }
    
    .demo-hint { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--line); font-size: 12px; color: var(--muted); text-align: center; line-height: 1.6; }
    .demo-hint code { background: var(--paper); padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
</style>
</head>
<body>
<div class="container">
    <div class="logo">
        <h1>⏱️ Attendance</h1>
        <p>Digital Time Attendance System</p>
        <?php if ($redirectFromScan): ?>
            <div class="qr-badge">📷 QR Scan Detected - Please Login</div>
        <?php endif; ?>
    </div>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="tabs">
        <button class="tab-btn active" data-tab="staff" onclick="switchTab('staff')">👤 Staff</button>
        <button class="tab-btn" data-tab="admin" onclick="switchTab('admin')">🔐 Admin</button>
    </div>
    
    <!-- Staff Login -->
    <form method="POST" class="login-form" id="staffForm">
        <label for="staff_id">Staff ID</label>
        <input type="text" id="staff_id" name="username" placeholder="e.g. STF-001" autocomplete="off" required>
        
        <label for="staff_pin">PIN <span style="color:var(--muted);font-weight:400;font-size:12px;">(required)</span></label>
        <input type="password" id="staff_pin" name="password" class="pin-input" placeholder="• • • •" maxlength="4" inputmode="numeric" required autofocus>
        
        <button type="submit" class="btn btn-primary">🔑 Sign In as Staff</button>
    </form>
    
    <!-- Admin Login -->
    <form method="POST" class="login-form hidden" id="adminForm">
        <label for="admin_user">Admin ID</label>
        <input type="text" id="admin_user" name="username" placeholder="ADMIN_001" autocomplete="off" required>
        
        <label for="admin_pass">Password</label>
        <input type="password" id="admin_pass" name="password" placeholder="Enter admin password" required>
        
        <button type="submit" class="btn btn-primary">🔐 Sign In as Admin</button>
    </form>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tab));
    document.getElementById('staffForm').classList.toggle('hidden', tab !== 'staff');
    document.getElementById('adminForm').classList.toggle('hidden', tab !== 'admin');
    if (tab === 'staff') document.getElementById('staff_pin').focus();
    else document.getElementById('admin_pass').focus();
}

document.getElementById('staff_pin').addEventListener('input', function(e) {
    if (this.value.length === 4) this.form.submit();
});

const urlParams = new URLSearchParams(window.location.search);
const tabParam = urlParams.get('tab');
if (tabParam === 'admin') switchTab('admin');
</script>
</body>
</html>