<?php
if (session_status() !== PHP_SESSION_ACTIVE) { 
    session_start(); 
}

// Security Check: Redirect back to login if no active session exists
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . route_url('/index.php'));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clock-It | Entering Hub...</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --deep-navy: #093C5D;
            --mid-blue: #3B7597;
            --olive-green: #9CB07A;
            --light-gray: #F5F5F5;
            --black: #000000;
        }

        body {
            background-color: var(--light-gray);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
            transition: opacity 0.4s ease-out;
        }

        body.fade-out {
            opacity: 0;
        }

        .loader-container {
            text-align: center;
        }

        /* Scaled-up Centered Eye Watcher Loader */
        .eye-loader {
            width: 72px;
            height: 72px;
            background: #FFFFFF;
            border: 7px solid var(--black);
            border-radius: 75% 0;
            transform: scaleY(1) rotate(45deg);
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(9, 60, 93, 0.12);
            user-select: none;
        }

        .eye-loader .pupil {
            width: 28px;
            height: 28px;
            background-color: var(--black);
            border-radius: 50%;
            position: absolute;
            transform: rotate(-45deg) translate(0px, 0px);
            transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* Catchlight Accent */
        .eye-loader .pupil::after {
            content: '';
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--olive-green);
            border-radius: 50%;
        }

        /* True Vertical Blink Animation */
        .eye-loader.blinking {
            animation: eyeBlink 0.2s ease-in-out;
        }

        @keyframes eyeBlink {
            0%, 100% { 
                transform: scaleY(1) rotate(45deg); 
            }
            50% { 
                transform: scaleY(0.05) rotate(45deg); 
            }
        }

        /* Status Messaging */
        .status-text {
            color: var(--deep-navy);
            font-weight: 600;
            font-size: 1rem;
            margin-top: 24px;
            letter-spacing: -0.2px;
        }

        .sub-text {
            color: #707070;
            font-size: 0.85rem;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="loader-container">
    <!-- Centered Animated Eye Loader -->
    <div class="eye-loader" id="eyeLoader">
        <div class="pupil" id="eyePupil"></div>
    </div>

    <!-- Dynamic Status Labels -->
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
        setTimeout(() => {
            look(-8, -5);
        }, 400);

        // 2. Look Down-Right (1000ms)
        setTimeout(() => {
            look(9, 4);
        }, 1000);

        // 3. Look Down-Left (1600ms)
        setTimeout(() => {
            look(-6, 6);
        }, 1600);

        // 4. Return to Center & Update Text (2200ms)
        setTimeout(() => {
            look(0, 0);
            title.textContent = "Welcome back!";
            sub.textContent = "Opening dashboard...";
        }, 2200);

        // 5. Trigger Vertical Blink (2600ms)
        setTimeout(() => {
            eye.classList.add('blinking');
        }, 2600);

        // 6. Fade Out & Redirect to Staff Dashboard (3000ms)
        setTimeout(() => {
            document.body.classList.add('fade-out');
            setTimeout(() => {
                window.location.href = '<?= route_url('/staff-dashboard') ?>';
            }, 400);
        }, 3000);
    });
</script>

</body>
</html>