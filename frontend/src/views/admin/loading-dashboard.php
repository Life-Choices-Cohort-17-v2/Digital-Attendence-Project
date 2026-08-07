<?php
/**
 * Post-login loading screen that redirects to the admin dashboard.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Entering Hub...' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ================================================================
           BLACK & GREEN DARK THEME – matches the rest of the app
           Background: #202020 (Onyx-like)
           Eye: same as login page (white, black border, green catchlight)
           ================================================================ */
        :root {
            --bg: #202020;              /* dark background */
            --text-light: #F8F8F8;      /* headings */
            --text-muted: #999999;      /* sub-text */
            --eye-white: #FFFFFF;
            --eye-border: #131313;      /* Onyx */
            --eye-pupil: #131313;
            --eye-catchlight: #8CDB8C;  /* light green from gradient */
            --shadow-color: rgba(0, 0, 0, 0.5);
        }
        body {
            background-color: var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            transition: opacity 0.4s ease-out;
        }
        body.fade-out { opacity: 0; }
        .loader-container { text-align: center; }
        .eye-loader {
            width: 72px;
            height: 72px;
            background: var(--eye-white);
            border: 4px solid var(--eye-border);  /* black outline */
            border-radius: 75% 0;
            transform: scaleY(1) rotate(45deg);
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 12px 30px var(--shadow-color);
            user-select: none;
        }
        .eye-loader .pupil {
            width: 28px;
            height: 28px;
            background-color: var(--eye-pupil);
            border-radius: 50%;
            position: absolute;
            transform: rotate(-45deg) translate(0px, 0px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .eye-loader .pupil::after {
            content: '';
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--eye-catchlight);
            border-radius: 50%;
        }
        .eye-loader.blinking { animation: eyeBlink 0.2s ease-in-out; }
        @keyframes eyeBlink {
            0%, 100% { transform: scaleY(1) rotate(45deg); }
            50% { transform: scaleY(0.05) rotate(45deg); }
        }
        .status-text {
            color: var(--text-light);
            font-weight: 600;
            font-size: 1rem;
            margin-top: 24px;
            letter-spacing: -0.2px;
        }
        .sub-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="loader-container">
    <div class="eye-loader" id="eyeLoader">
        <div class="pupil" id="eyePupil"></div>
    </div>
    <div class="status-text" id="statusTitle">Authenticating...</div>
    <div class="sub-text" id="statusSub">Setting up your workplace session</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pupil = document.getElementById('eyePupil');
        const eye = document.getElementById('eyeLoader');
        const title = document.getElementById('statusTitle');
        const sub = document.getElementById('statusSub');

        const look = (x, y) => {
            pupil.style.transform = `rotate(-45deg) translate(${x}px, ${y}px)`;
        };

        // --- ANIMATION TIMELINE --- //
        
        // 1. Look Up-Left (400ms)
        setTimeout(() => { look(-8, -5); }, 400);

        // 2. Look Down-Right (1000ms)
        setTimeout(() => { look(9, 4); }, 1000);

        // 3. Look Down-Left (1600ms)
        setTimeout(() => { look(-6, 6); }, 1600);

        // 4. Return to Center & Update Text (2200ms)
        setTimeout(() => {
            look(0, 0);
            title.textContent = "Welcome back!";
            sub.textContent = "Opening dashboard...";
        }, 2200);

        // 5. Trigger Vertical Blink (2600ms)
        setTimeout(() => { eye.classList.add('blinking'); }, 2600);

        // 6. Fade Out & Redirect to Admin Dashboard (3000ms)
        setTimeout(() => {
            document.body.classList.add('fade-out');
            setTimeout(() => {
                window.location.href = '<?= route_url('/admin-dashboard') ?>';
            }, 400);
        }, 3000);
    });
</script>

</body>
</html>