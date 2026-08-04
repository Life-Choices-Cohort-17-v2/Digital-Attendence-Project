/*Will's Login Page */
<?php
// 1. Core Session Init & Router Config must happen at the absolute top
if (session_status() !== PHP_SESSION_ACTIVE) { 
    session_start(); 
}

$error = '';

// 2. Intercept and process incoming form data ONLY when submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check input against your provided demo environment credentials
    if ($identifier === 'staff@clockit.app' && $password === 'password123') {
        
        $_SESSION['user_id'] = 101; 
        $_SESSION['user_name'] = 'Alex Mercer'; 
        $_SESSION['user_email'] = 'staff@clockit.app';
        $_SESSION['employee_id'] = 'EMP-2026-09'; 
        $_SESSION['attendance_status'] = 'Clocked Out';    
        $_SESSION['attendance_location'] = 'OFFSITE';      

        // Route Staff to the animated loader screen
        header('Location: ' . route_url('/staff/loading.php'));
        exit;

    } elseif ($identifier === 'admin@clockit.app' && $password === 'admin123') {
        
        $_SESSION['user_id'] = 102; 
        $_SESSION['user_name'] = 'Sarah Connor'; 
        $_SESSION['user_email'] = 'admin@clockit.app';
        $_SESSION['employee_id'] = 'ADM-2026-01'; 
        $_SESSION['attendance_status'] = 'Clocked In';    
        $_SESSION['attendance_location'] = 'HEADQUARTERS';      

        // Route Admin directly to the admin dashboard
        header('Location: ' . route_url('/admin/dashboard.php'));
        exit;

    } elseif ($identifier === 'ceo@clockit.app' && $password === 'ceo123') {
        
        $_SESSION['user_id'] = 1; 
        $_SESSION['user_name'] = 'Will Mxabanisi'; 
        $_SESSION['user_email'] = 'ceo@clockit.app';
        $_SESSION['employee_id'] = 'CEO-2026-01'; 
        $_SESSION['attendance_status'] = 'Clocked In';    
        $_SESSION['attendance_location'] = 'OFFSITE';      

        // Route CEO to the animated loader screen
        header('Location: ' . route_url('/staff/loading.php'));
        exit;

    } else {
        // Fallback flag if credentials fail processing rules
        $error = "Authentication failed. Invalid email, ID, or password.";
    }
}

// 3. Start layout output buffering after your edge conditional routing logic
ob_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clock-It | Access Hub</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Hex Code Palette Mapping */
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
        }

        /* Outer Floating Card Container */
        .auth-card {
            background-color: #FFFFFF;
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(9, 60, 93, 0.06);
            border: none;
            width: 100%;
            max-width: 1024px;
        }

        /* Left Side Inset Banner */
        .gradient-panel {
            background: 
                /* Grain Texture Layer */
                url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.80' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.07'/%3E%3C/svg%3E"),
                /* Deep Navy Gradient Layer */
                linear-gradient(135deg, var(--mid-blue) 0%, var(--deep-navy) 100%);
            border-radius: 24px;
            min-height: 560px;
        }

        /* Form Customization */
        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--deep-navy);
            margin-bottom: 6px;
        }

        .form-control {
            border: 1px solid #E0E0E0;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--mid-blue);
            box-shadow: 0 0 0 3px rgba(59, 117, 151, 0.15);
        }

        /* Primary Styled Action Button */
        .btn-olive {
            background-color: var(--olive-green);
            color: #FFFFFF;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 12px;
            transition: background-color 0.2s ease;
        }

        .btn-olive:hover, .btn-olive:focus {
            background-color: #899c6b;
            color: #FFFFFF;
        }

        /* Horizontal Divider Accent */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #A0A0A0;
            font-size: 0.8rem;
            margin: 24px 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #EAEAEA;
        }

        .divider:not(:empty)::before { margin-right: .5em; }
        .divider:not(:empty)::after { margin-left: .5em; }

        /* Social OAuth Row Layout */
        .btn-social {
            background-color: #EAEAEA;
            border: none;
            border-radius: 10px;
            padding: 10px;
            transition: background-color 0.2s;
            color: var(--deep-navy);
        }

        .btn-social:hover {
            background-color: #DFDFDF;
        }

        /* Bold Minimalist Eye Logo Styles */
        .eye-watcher {
            width: 44px;
            height: 44px;
            background: #FFFFFF;
            border: 5px solid var(--black);
            border-radius: 75% 0;
            transform: scaleY(1) rotate(45deg);
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            user-select: none;
        }

        .eye-watcher .pupil {
            width: 18px;
            height: 18px;
            background-color: var(--black);
            border-radius: 50%;
            position: absolute;
            transform: rotate(-45deg) translate(0px, 0px);
            transition: transform 0.05s ease-out;
        }

        /* Catchlight Accent */
        .eye-watcher .pupil::after {
            content: '';
            position: absolute;
            top: 3px;
            right: 3px;
            width: 5px;
            height: 5px;
            background: var(--olive-green);
            border-radius: 50%;
        }

        /* Blink Animation */
        .eye-watcher.blinking {
            animation: eyeBlink 0.3s ease-in-out;
        }

        @keyframes eyeBlink {
            0%, 100% { 
                transform: scaleY(1) rotate(45deg); 
            }
            50% { 
                transform: scaleY(0.05) rotate(45deg); 
            }
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3 p-md-4">

<div class="auth-card p-3">
    <div class="row g-4 match-height">
        
        <!-- Left Side Gradient Panel -->
        <div class="col-lg-5 d-none d-lg-block">
            <div class="gradient-panel p-5 d-flex flex-column justify-content-between text-white h-100">
                <div>
                    <!-- Interactive Bold Eye (Single Eye Logo) -->
                    <div class="eye-watcher" title="Clock-It is watching">
                        <div class="pupil"></div>
                    </div>
                </div>
                <div>
                    <span class="text-white-50 small d-block mb-1">Good to see you!</span>
                    <h2 class="fw-bold lh-base" style="font-size: 1.9rem;">Step inside your Clock-It hub and let's get things moving today.</h2>
                </div>
            </div>
        </div>

        <!-- Right Side Form Panel -->
        <div class="col-lg-7 d-flex align-items-center justify-content-center py-4 py-md-5">
            <div class="w-100 px-2 px-md-5" style="max-width: 460px;">

                <div class="mb-4">
                    <h2 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;">Welcome to Clock-It</h2>
                    <p class="text-muted small">Sign in with your email or employee ID to access your dashboard.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 px-3 small rounded-3" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" x-data="{ showPassword: false, isLoading: false }" @submit="isLoading = true">
                    
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Your email or ID</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" placeholder="staff@clockit.app" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="position-relative">
                            <input :type="showPassword ? 'text' : 'password'" class="form-control pe-5" id="password" name="password" placeholder="••••••••••••" required>
                            <button class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted px-3" type="button" @click="showPassword = !showPassword" aria-label="Toggle password visibility">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-olive w-100 d-flex align-items-center justify-content-center" :disabled="isLoading">
                        <span x-show="!isLoading">Get Started</span>
                        <span x-show="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" x-cloak></span>
                        <span x-show="isLoading" x-cloak>Verifying...</span>
                    </button>

                    <div class="divider">or continue with</div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <button type="button" class="btn btn-social w-100" aria-label="Sign in with Microsoft"><i class="bi bi-microsoft"></i></button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-social w-100" aria-label="Sign in with Google"><i class="bi bi-google"></i></button>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <span class="text-muted small">Don't have an account? <a href="#" class="text-decoration-none fw-semibold" style="color: var(--mid-blue);">Sign up</a></span>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const eyes = document.querySelectorAll('.eye-watcher');

        // Track cursor movement
        document.addEventListener('mousemove', (e) => {
            eyes.forEach(eye => {
                const pupil = eye.querySelector('.pupil');
                if (!pupil) return;

                const rect = eye.getBoundingClientRect();
                const eyeCenterX = rect.left + rect.width / 2;
                const eyeCenterY = rect.top + rect.height / 2;

                const deltaX = e.clientX - eyeCenterX;
                const deltaY = e.clientY - eyeCenterY;
                const angle = Math.atan2(deltaY, deltaX);

                const maxDistance = 6;
                const distance = Math.min(Math.hypot(deltaX, deltaY) / 15, maxDistance);

                const moveX = Math.cos(angle) * distance;
                const moveY = Math.sin(angle) * distance;

                pupil.style.transform = `rotate(-45deg) translate(${moveX}px, ${moveY}px)`;
            });
        });

        // Add click-to-blink listener
        eyes.forEach(eye => {
            eye.addEventListener('click', () => {
                if (eye.classList.contains('blinking')) return;

                eye.classList.add('blinking');
                console.log('⚠️ OMNITRACK: Employee manual surveillance check logged.');

                eye.addEventListener('animationend', () => {
                    eye.classList.remove('blinking');
                }, { once: true });
            });
        });
    });
</script>

</body>
</html>
<?php 
$content = ob_get_clean(); 
require __DIR__ . '/layouts/app.php'; 
?>
/*Will's Login Page */