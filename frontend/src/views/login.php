<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SpySee - Login' ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sidebar-blue: #093C5D;
            --olive-green: #9CB07A;
            --background: #FFFFFF; /* Changed to white for the login section background */
            --card-bg: #FFFFFF;
            --border-color: #E8EDF2;
            --heading: #093C5D;
            --text: #5C6B7A;
            --muted: #94A3B8;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            height: 100vh;
            overflow: hidden;
        }

        /* Login Container - Full screen on desktop */
        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* Left Side - Full height blue section (Desktop only) */
        .login-left {
            flex: 1;
            background: var(--sidebar-blue);
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100vh;
            overflow-y: auto;
        }

        .login-brand h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 80px;
        }

        .login-left h2 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            max-width: 500px;
        }

        .login-left p {
            opacity: 0.8;
            line-height: 1.6;
            margin-bottom: 48px;
            max-width: 450px;
            font-size: 16px;
        }

        .feature-badges {
            display: flex;
            gap: 40px;
        }

        .badge-item {
            font-size: 28px;
            font-weight: 700;
        }

        .badge-item span {
            display: block;
            font-size: 13px;
            font-weight: 400;
            opacity: 0.7;
            margin-top: 4px;
        }

        /* Right Side - Login Form */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
            height: 100vh;
            overflow-y: auto;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
            padding: 48px;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .login-header {
            margin-bottom: 32px;
        }

        .login-header h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 8px;
        }

        .login-header p {
            color: var(--text);
            font-size: 14px;
        }

        /* Form Styles */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--heading);
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 14px;
            background: var(--card-bg);
            color: var(--heading);
            transition: 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--olive-green);
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text);
            cursor: pointer;
        }

        .checkbox input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 13px;
            color: var(--olive-green);
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--sidebar-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .login-btn:hover {
            background: #1a5270;
        }

        /* Demo Accounts */
        .demo-accounts {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        .demo-accounts p {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 12px;
            font-weight: 500;
        }

        .demo-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .demo-items div {
            font-size: 12px;
            color: var(--text);
        }

        .demo-items strong {
            color: var(--heading);
        }

        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin-left: 8px;
            font-weight: 500;
        }

        .role-badge.admin {
            background: rgba(9, 60, 93, 0.1);
            color: var(--sidebar-blue);
        }

        .role-badge.staff {
            background: rgba(156, 176, 122, 0.12);
            color: var(--olive-green);
        }

        /* Footer */
        .login-footer {
            margin-top: 32px;
            text-align: center;
        }

        .login-footer p {
            font-size: 11px;
            color: var(--muted);
        }

        /* Error Message */
        .error-message {
            background: rgba(220, 38, 38, 0.1);
            color: #DC2626;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        /* Dark Mode Variables Override for Login */
        body.dark-mode {
            --background: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --heading: #f1f5f9;
            --text: #94a3b8;
            --muted: #64748b;
            --sidebar-blue: #1e3a5f;
        }

        [x-cloak] { display: none !important; }

        /* Theme Toggle specifically for login page */
        .login-theme-toggle {
            position: absolute;
            top: 24px;
            right: 24px;
            z-index: 1000;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .login-theme-toggle:hover {
            border-color: var(--olive-green);
            transform: translateY(-2px);
        }

        /* ============================================
           MOBILE & TABLET - Hide blue section
        ============================================ */
        @media (max-width: 900px) {
            body {
                overflow: auto;
                height: auto;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--background);
            }

            .login-container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
                background: var(--card-bg);
            }

            /* Hide blue section on mobile/tablet */
            .login-left {
                display: none;
            }

            .login-right {
                height: auto;
                min-height: 100vh;
                background: var(--card-bg);
                padding: 40px 20px;
            }

            .login-card {
                padding: 0;
                max-width: 400px;
                margin: 0 auto;
            }

            .login-header h3 {
                font-size: 24px;
            }

            .login-header p {
                font-size: 13px;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .login-right {
                padding: 30px 16px;
            }

            .login-card {
                padding: 0;
            }

            .input-group input {
                padding: 12px 14px;
            }

            .login-btn {
                padding: 12px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body>

<script>window.themeManager.initTheme();</script>

<main class="login-page" x-data="{ loading: false }">
    <!-- Theme Toggle -->
    <button class="login-theme-toggle" x-data="{ dark: window.themeManager.isDark() }" @click="dark = window.themeManager.toggleTheme()" type="button" title="Toggle Theme">
        <svg x-show="!dark" class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#093C5D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
        <svg x-show="dark" class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#9CB07A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 20px; height: 20px;" x-cloak>
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
    </button>

    <div class="login-container">
        <!-- Left Side - Full height blue section (DESKTOP ONLY - hidden on mobile/tablet) -->
        <div class="login-left">
            <div class="login-brand">
                <h1>SpySee</h1>
            </div>
            <h2>Real-time attendance.<br>Always connected.<br>Built for frontline staff.</h2>
            <p>Fast QR-based sign-in. Live onsite visibility. Seamless Google Sheets sync — wherever your team works.</p>
            <div class="feature-badges">
                <div class="badge-item">&lt;2s <span>sign-in time</span></div>
                <div class="badge-item">100% <span>User friendly</span></div>
                <div class="badge-item">Live <span>Sheets sync</span></div>
            </div>
        </div>

        <!-- Right Side - Login Form (Full width on mobile) -->
        <div class="login-right">
            <div class="login-card">
                <div class="login-header">
                    <h3>Sign in</h3>
                    <p>Use the credentials provided by your administrator.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= route_url('/auth/login') ?>" @submit="loading = true">
                    <div class="input-group">
                        <label>Email</label>
                        <input type="text" name="identifier" required placeholder="sarah@spysee.app">
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••">
                    </div>
                    <div class="login-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember"> Remember me
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    <button type="submit" name="login" class="login-btn" :disabled="loading">
                        <span x-show="!loading">Login</span>
                        <span x-show="loading" x-cloak>Signing in...</span>
                    </button>
                </form>

                <div class="demo-accounts">
                    <p>DEMO ACCOUNTS</p>
                    <div class="demo-items">
                        <div><strong>admin@spysee.app</strong> / password <span class="role-badge admin">Admin</span></div>
                        <div><strong>sarah@spysee.app</strong> / password <span class="role-badge staff">Staff</span></div>
                    </div>
                </div>

                <div class="login-footer">
                    <p>© SpySee - Secure attendance for modern teams</p>
                </div>
            </div>
        </div>
    </div>
</main>

</body>
</html>