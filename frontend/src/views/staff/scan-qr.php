<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'staff') {
    header('Location: /index.php/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $title ?? 'Scan QR Code' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="/assets/js/app.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .scan-container {
            padding: 28px 32px;
            max-width: 600px;
            margin: 0 auto;
        }
        .scan-container h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 8px;
        }
        .scan-container > p {
            color: var(--text);
            font-size: 14px;
            margin-bottom: 24px;
        }
        .scanner-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            margin-top: 0;
        }
        .scanner-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .ready-badge {
            display: inline-block;
            background: var(--accent-soft);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 16px;
        }
        .scanner-header p {
            color: var(--text);
            font-size: 14px;
        }
        
        .camera-section {
            margin-bottom: 0;
        }
        .camera-btn {
            width: 100%;
            padding: 16px;
            background: var(--accent);
            color: #202020;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }
        .camera-btn:hover {
            background: var(--primary-green-dark);
            color: #f8f8f8;
        }
        .camera-btn.stop {
            background: #ef4444;
            color: white;
        }
        .camera-btn.stop:hover {
            background: #dc2626;
        }
        
        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 15px;
            min-height: 300px;
        }
        #reader video {
            width: 100%;
            height: auto;
            min-height: 300px;
            object-fit: cover;
        }
        
        /* Camera switch button */
        .camera-switch-btn {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .camera-switch-btn:hover {
            border-color: var(--accent);
            color: var(--heading);
        }
        
        /* ===== RESULT OVERLAY ===== */
        .result-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes bounceIn {
            0% { transform: scale(0.5); opacity: 0; }
            60% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); }
        }
        .result-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 40px 48px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            border: 1px solid var(--border-color);
            animation: bounceIn 0.4s ease-out;
        }
        .result-card .icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .result-card .title {
            font-size: 24px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 8px;
        }
        .result-card .subtitle {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 4px;
        }
        .result-card .detail {
            font-size: 13px;
            color: var(--muted);
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }
        .result-card .btn-close-result {
            margin-top: 20px;
            padding: 12px 32px;
            background: var(--accent);
            color: #202020;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 15px;
        }
        .result-card .btn-close-result:hover {
            background: var(--primary-green-dark);
            color: #f8f8f8;
        }
        .result-card.success .icon { color: var(--accent); }
        .result-card.success .title { color: var(--accent); }
        .result-card.error .icon { color: #EF4444; }
        .result-card.error .title { color: #EF4444; }
        
        .result-message {
            margin-top: 16px;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-weight: 500;
            display: none;
        }
        .result-message.success {
            display: block;
            background: var(--accent-soft);
            color: var(--accent);
        }
        .result-message.error {
            display: block;
            background: rgba(220, 38, 38, 0.1);
            color: #DC2626;
        }
        
        .scan-instructions {
            margin-top: 16px;
            padding: 16px;
            background: var(--background);
            border-radius: 12px;
            text-align: center;
            font-size: 13px;
            color: var(--text);
        }
        .scan-instructions strong {
            color: var(--heading);
        }
        
        @media (max-width: 480px) {
            .scan-container { padding: 16px; }
            .scanner-card { padding: 16px; }
            #reader { min-height: 250px; }
            #reader video { min-height: 250px; }
            .result-card { padding: 28px 20px; }
            .result-card .icon { font-size: 48px; }
        }
    </style>
</head>
<body>

<div x-data="scanApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'scan'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="scan-container">
                <h1>📷 Scan QR Code</h1>
                <p>Point your camera at the workplace QR code to sign in or out.</p>

                <div class="scanner-card">
                    <div class="scanner-header">
                        <span class="ready-badge" x-show="!scannerActive">● Ready to scan</span>
                        <span class="ready-badge" x-show="scannerActive" style="background: #fef3c7; color: #d97706;" x-cloak>● Scanning...</span>
                        <p>Hold steady and center the QR code in the frame.</p>
                    </div>

                    <div class="camera-section">
                        <button class="camera-btn" @click="startScanner()" x-show="!scannerActive">
                            📷 Open Camera
                        </button>
                        <button class="camera-btn stop" @click="stopScanner()" x-show="scannerActive" x-cloak>
                            ⏹️ Stop Camera
                        </button>
                        <div id="reader" x-show="scannerActive" x-cloak></div>
                        
                        <!-- Camera Switch Button -->
                        <button class="camera-switch-btn" @click="switchCamera()" x-show="scannerActive" x-cloak>
                            🔄 Switch Camera
                        </button>
                    </div>

                    <div class="result-message" :class="resultType" x-show="resultMessage" x-text="resultMessage"></div>
                    
                    <div class="scan-instructions">
                        💡 <strong>Tip:</strong> Make sure the QR code is well-lit and centered.
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ===== RESULT OVERLAY ===== -->
    <div class="result-overlay" x-show="showResult" x-cloak @click.away="closeResult()">
        <div class="result-card" :class="resultType">
            <div class="icon" x-text="resultType === 'success' ? '✅' : '❌'"></div>
            <div class="title" x-text="resultTitle"></div>
            <div class="subtitle" x-text="resultSubtitle"></div>
            <div class="detail" x-text="resultDetail"></div>
            <button class="btn-close-result" @click="closeResult()">OK, Got it!</button>
        </div>
    </div>
</div>

<script>
function scanApp() {
    return {
        sidebarOpen: false,
        user: { id: '', name: '', email: '' },
        resultMessage: '',
        resultType: '',
        scannerActive: false,
        html5QrCode: null,
        isProcessing: false,
        cameraFacing: 'environment', // 'environment' or 'user'
        // Result overlay
        showResult: false,
        resultTitle: '',
        resultSubtitle: '',
        resultDetail: '',
        
        async init() {
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Staff', 'email' => 'staff@spysee.app']); ?>;
            this.user = userData;
            window.themeManager.initTheme();
            
            // Check for scan result from redirect
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('scan') === 'success') {
                this.showResultOverlay('success', '✅ Scan Successful!', 'You have been signed in/out', 'Redirecting to dashboard...');
                setTimeout(() => {
                    window.history.replaceState({}, document.title, window.location.pathname);
                    this.closeResult();
                }, 3000);
            }
        },

        showResultOverlay(type, title, subtitle, detail) {
            this.resultType = type;
            this.resultTitle = title;
            this.resultSubtitle = subtitle;
            this.resultDetail = detail;
            this.showResult = true;
        },

        closeResult() {
            this.showResult = false;
            if (this.resultType === 'success') {
                window.location.href = '/index.php/staff-dashboard';
            }
        },

        async startScanner() {
            this.scannerActive = true;
            this.resultMessage = '';
            this.resultType = '';
            
            try {
                this.html5QrCode = new Html5Qrcode("reader");
                
                const config = { 
                    fps: 15, 
                    qrbox: { width: 280, height: 280 },
                    aspectRatio: 1.0
                };
                
                // Try environment camera first (rear on phone), fallback to user
                const cameraId = { facingMode: this.cameraFacing };
                
                await this.html5QrCode.start(
                    cameraId,
                    config,
                    (decodedText) => {
                        this.handleScan(decodedText);
                    },
                    (error) => {
                        // Ignore errors during scanning
                    }
                );
                
            } catch (err) {
                console.error("Camera error:", err);
                
                // If environment fails, try user camera
                if (this.cameraFacing === 'environment') {
                    this.cameraFacing = 'user';
                    try {
                        await this.html5QrCode.start(
                            { facingMode: 'user' },
                            { fps: 15, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
                            (decodedText) => { this.handleScan(decodedText); },
                            () => {}
                        );
                        return;
                    } catch (secondErr) {
                        console.error("Both cameras failed:", secondErr);
                    }
                }
                
                this.scannerActive = false;
                let errorMsg = '❌ Camera access denied. ';
                if (err.name === 'NotAllowedError') {
                    errorMsg += 'Please allow camera access in your browser settings.';
                } else if (err.name === 'NotFoundError') {
                    errorMsg += 'No camera found on this device.';
                } else {
                    errorMsg += 'Please try again.';
                }
                this.resultMessage = errorMsg;
                this.resultType = 'error';
                this.showResultOverlay('error', '❌ Camera Error', errorMsg, 'Please allow camera access and try again');
            }
        },

        async switchCamera() {
            // Toggle between front and back camera
            this.cameraFacing = this.cameraFacing === 'environment' ? 'user' : 'environment';
            
            // Restart scanner with new camera
            if (this.html5QrCode) {
                await this.stopScanner();
                setTimeout(() => this.startScanner(), 500);
            }
        },

        async stopScanner() {
            if (this.html5QrCode) {
                try {
                    await this.html5QrCode.stop();
                    this.html5QrCode.clear();
                } catch (err) {
                    // ignore
                }
                this.scannerActive = false;
            }
        },

        async handleScan(decodedText) {
            if (this.isProcessing) return;
            this.isProcessing = true;
            
            console.log('📱 QR Code detected:', decodedText);
            
            try {
                let staffId = this.user.id;
                let location = 'HQ';
                let name = this.user.name;
                
                // Parse QR URL if it's a full URL
                if (decodedText.includes('scan.php?')) {
                    const url = new URL(decodedText);
                    staffId = url.searchParams.get('staff_id') || this.user.id;
                    location = url.searchParams.get('location') || 'HQ';
                    name = url.searchParams.get('name') || this.user.name;
                    
                    // If QR has QR_SCAN, use logged-in user
                    if (staffId === 'QR_SCAN' || staffId === 'QR_Scan') {
                        staffId = this.user.id;
                        name = this.user.name;
                    }
                }
                
                // Record the scan
                const result = await this.recordScan(staffId, name, location);
                
                if (result && result.success) {
                    this.showResultOverlay(
                        'success',
                        '✅ ' + (result.message || 'Scan Successful!'),
                        'You have been ' + (result.status === 'in' ? 'signed in' : 'signed out'),
                        'Staff: ' + name + ' | Location: ' + location
                    );
                    this.stopScanner();
                    setTimeout(() => {
                        this.closeResult();
                    }, 3000);
                } else {
                    this.showResultOverlay(
                        'error',
                        '❌ Scan Failed',
                        result.message || 'Could not process scan',
                        'Please try again'
                    );
                }
                
            } catch (err) {
                console.error('Error processing scan:', err);
                this.showResultOverlay(
                    'error',
                    '❌ Error',
                    'Failed to process QR code',
                    err.message || 'Please try again'
                );
            }
            
            this.isProcessing = false;
        },

        async recordScan(staffId, name, location) {
            try {
                // First check current status
                const statusResponse = await fetch('/index.php/api/onsite-staff?_=' + Date.now());
                const statusData = await statusResponse.json();
                const onsite = statusData.data || [];
                const isClockedIn = onsite.some(s => s.id === staffId || s.staff_id === staffId);
                
                // Determine action
                const action = isClockedIn ? 'sign-out' : 'sign-in';
                const endpoint = action === 'sign-in' ? '/index.php/api/sign-in' : '/index.php/api/sign-out';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: staffId,
                        name: name,
                        location: location
                    })
                });
                
                const result = await response.json();
                return result;
                
            } catch (err) {
                console.error('API Error:', err);
                return { success: false, message: 'Connection error. Please try again.' };
            }
        }
    }
}
</script>

</body>
</html>