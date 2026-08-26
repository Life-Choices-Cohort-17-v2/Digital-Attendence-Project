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
            --background: #202020;
            --card-bg: #2a2a2a;
            --border-color: #444444;
            --heading: #F8F8F8;
            --text: #999999;
            --muted: #666666;
            --accent: #5DD62C;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            height: 100vh;
            overflow: hidden;
        }

        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* LEFT PANEL (gradient) */
        .login-left {
            flex: 1;
            background: linear-gradient(145deg, #0A0A0A 0%, #008400 40%, #00B000 70%, #8CDB8C 100%);
            padding: 60px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100vh;
            overflow-y: auto;
            position: relative;
        }

        .login-brand h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .eye-watcher {
            width: 64px;
            height: 64px;
            background: #FFFFFF;
            border: 4px solid #131313;
            border-radius: 75% 0;
            transform: scaleY(1) rotate(45deg);
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            user-select: none;
            margin-top: 4px;
        }
        .eye-watcher .pupil {
            width: 24px;
            height: 24px;
            background-color: #131313;
            border-radius: 50%;
            position: absolute;
            transform: rotate(-45deg) translate(0px, 0px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .eye-watcher .pupil::after {
            content: '';
            position: absolute;
            top: 3px;
            right: 3px;
            width: 8px;
            height: 8px;
            background: #8CDB8C;
            border-radius: 50%;
        }
        .eye-watcher.blinking {
            animation: eyeBlink 0.2s ease-in-out;
        }
        @keyframes eyeBlink {
            0%, 100% { transform: scaleY(1) rotate(45deg); }
            50% { transform: scaleY(0.05) rotate(45deg); }
        }
        .eye-watcher.sleeping .pupil {
            transform: rotate(-45deg) translate(0px, 8px) !important;
            transition: transform 0.8s ease-in;
        }
        .eye-watcher.sleeping { opacity: 0.7; }
        .eye-watcher.awake .pupil { transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .eye-watcher.awake { opacity: 1; }

        .login-left h2 {
            font-size: 42px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 24px;
            max-width: 500px;
            margin-top: 20px;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .login-left p {
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 48px;
            max-width: 450px;
            font-size: 16px;
            color: #fff;
        }
        .feature-badges {
            display: flex;
            gap: 40px;
        }
        .badge-item {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
        }
        .badge-item span {
            display: block;
            font-size: 13px;
            font-weight: 400;
            opacity: 0.8;
            margin-top: 4px;
            color: #fff;
        }

        /* RIGHT PANEL (login card) – DARK ONLY */
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
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
            background: #333333;
            color: var(--heading);
            transition: 0.2s;
        }
        .input-group input:focus {
            outline: none;
            border-color: var(--accent);
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
            color: var(--accent);
            text-decoration: none;
        }
        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #202020;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .login-btn:hover {
            background: #337418;
            color: #f8f8f8;
        }

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
            background: rgba(93, 214, 44, 0.2);
            color: var(--accent);
        }
        .role-badge.staff {
            background: rgba(156, 176, 122, 0.2);
            color: #9CB07A;
        }

        .login-footer {
            margin-top: 32px;
            text-align: center;
        }
        .login-footer p {
            font-size: 11px;
            color: var(--muted);
        }

        .error-message {
            background: rgba(220,38,38,0.1);
            color: #DC2626;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }

        [x-cloak] { display: none !important; }

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
            .login-left { display: none; }
            .login-right {
                height: auto;
                min-height: 100vh;
                background: var(--background);
                padding: 40px 20px;
            }
            .login-card {
                padding: 0;
                max-width: 400px;
                margin: 0 auto;
                background: transparent;
                box-shadow: none;
            }
            .login-header h3 { font-size: 24px; }
            .login-header p { font-size: 13px; }
        }

        @media (max-width: 480px) {
            .login-right { padding: 30px 16px; }
            .login-card { padding: 0; }
            .input-group input { padding: 12px 14px; }
            .login-btn { padding: 12px; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
</head>
<body>

<main class="login-page" x-data="{ loading: false }">
    <div class="login-container">
        <div class="login-left">
            <div class="login-brand">
                <h1>SpySee</h1>
                <div class="eye-watcher" id="spyEye" title="SpySee is watching">
                    <div class="pupil" id="eyePupil"></div>
                </div>
            </div>
            <h2>Real-time attendance.<br>Always connected.<br>Built for frontline staff.</h2>
            <p>Fast QR-based sign-in. Live onsite visibility. Seamless Google Sheets sync — wherever your team works.</p>
            <div class="feature-badges">
                <div class="badge-item">&lt;2s <span>sign-in time</span></div>
                <div class="badge-item">100% <span>User friendly</span></div>
                <div class="badge-item">Live <span>Sheets sync</span></div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-card">
                <div class="login-header">
                    <h3>Sign in</h3>
                    <p>Use your Employee ID or email address with your PIN/password.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= route_url('/login') ?>" @submit="loading = true">
                    <div class="input-group">
                        <label>Employee ID or Email</label>
                        <input type="text" name="username" placeholder="e.g. EMP001 or thina@spysee.app" required>
                    </div>
                    <div class="input-group">
                        <label>PIN / Password</label>
                        <input type="password" name="password" placeholder="Enter your PIN or password" required>
                    </div>
                    <button type="submit" class="login-btn" :disabled="loading">
                        <span x-show="!loading">Login</span>
                        <span x-show="loading" x-cloak>Signing in...</span>
                    </button>
                </form>

                <div class="demo-accounts">
                    <p>DEMO ACCOUNTS</p>
                    <div class="demo-items">
                        <div><strong>EMP001</strong> / thina@01 <span class="role-badge staff">Staff</span></div>
                        <div><strong>EMP004</strong> / jose@04 <span class="role-badge admin">Admin</span></div>
                        <div><strong>admin@spysee.app</strong> / admin123 <span class="role-badge admin">Admin</span></div>
                    </div>
                </div>

                <div class="login-footer">
                    <p>© SpySee - Secure attendance for modern teams</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- EYE JAVASCRIPT -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const eye = document.getElementById('spyEye');
        const pupil = document.getElementById('eyePupil');
        if (!eye || !pupil) return;

        let isSleeping = false;
        let sleepTimeout = null;

        function wakeUp() {
            if (isSleeping) {
                isSleeping = false;
                eye.classList.remove('sleeping');
                eye.classList.add('awake');
                pupil.style.transform = 'rotate(-45deg) translate(0px, 0px)';
                clearTimeout(sleepTimeout);
            }
        }

        function goToSleep() {
            if (!isSleeping) {
                isSleeping = true;
                eye.classList.remove('awake');
                eye.classList.add('sleeping');
                pupil.style.transform = 'rotate(-45deg) translate(0px, 8px)';
            }
        }

        document.addEventListener('mousemove', function(e) {
            const rect = eye.getBoundingClientRect();
            const eyeCenterX = rect.left + rect.width / 2;
            const eyeCenterY = rect.top + rect.height / 2;

            const deltaX = e.clientX - eyeCenterX;
            const deltaY = e.clientY - eyeCenterY;
            const angle = Math.atan2(deltaY, deltaX);
            const maxDistance = 8;
            const distance = Math.min(Math.hypot(deltaX, deltaY) / 15, maxDistance);

            const moveX = Math.cos(angle) * distance;
            const moveY = Math.sin(angle) * distance;

            if (!isSleeping) {
                pupil.style.transform = `rotate(-45deg) translate(${moveX}px, ${moveY}px)`;
            }

            wakeUp();
            clearTimeout(sleepTimeout);
            sleepTimeout = setTimeout(goToSleep, 20000);
        });

        eye.addEventListener('click', function() {
            if (this.classList.contains('blinking')) return;

            if (isSleeping) {
                wakeUp();
                clearTimeout(sleepTimeout);
                sleepTimeout = setTimeout(goToSleep, 20000);
            }

            this.classList.add('blinking');
            console.log('👁️ SpySee: Eye blink logged');

            this.addEventListener('animationend', function() {
                this.classList.remove('blinking');
            }, { once: true });
        });

        sleepTimeout = setTimeout(goToSleep, 20000);

        document.addEventListener('keydown', function() {
            wakeUp();
            clearTimeout(sleepTimeout);
            sleepTimeout = setTimeout(goToSleep, 20000);
        });
    });
</script>

</body>
</html>