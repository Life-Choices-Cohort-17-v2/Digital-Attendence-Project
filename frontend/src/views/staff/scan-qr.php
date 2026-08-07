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
    <title><?= $title ?? 'Scan QR Code' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        .demo-qr-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            margin-top: 24px;
        }
        .demo-qr-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--heading);
        }
        .demo-qr-grid {
            display: flex;
            justify-content: center;
            padding: 10px 0;
        }
        .demo-qr-item {
            max-width: 200px; /* Limit width of the card */
            margin: 0 auto; /* Center the card */
            text-align: center;
            padding: 20px; /* Ensure padding is applied */
            background: var(--card-bg); /* Use CSS variable for consistency */
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            aspect-ratio: 1 / 1; /* Make the card square */
            display: flex; /* Use flex to center content vertically */
            flex-direction: column;
            justify-content: center;
        }
        .demo-qr-item p {
            margin-top: 12px;
            font-weight: 500;
            color: var(--heading);
        }
        .camera-section {
            margin-bottom: 24px;
        }
        .camera-btn {
            width: 100%;
            padding: 16px;
            background: var(--sidebar-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .demo-buttons {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }
        .demo-sign-in, .demo-sign-out {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .demo-sign-in {
            background: rgba(156, 176, 122, 0.12);
            color: #728C47;
        }
        .demo-sign-out {
            background: rgba(245, 158, 11, 0.12);
            color: #D97706;
        }
        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 15px;
        }
        /* Adjust QR code size for mobile */
        #qr-display canvas, #qr-display img {
            width: 120px !important; /* Smaller size for mobile */
            height: 120px !important;
            margin: 0 auto; /* Center the QR code */
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<div x-data="scanApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'scan'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="scan-container">
                <h1>Scan QR Code</h1>
                <p>Point your camera at the workplace QR code to Sign In or out.</p>

                <div class="scanner-card">
                    <div class="scanner-header">
                        <span class="ready-badge">● Ready to scan</span>
                        <p>Fast camera recognition. Your activity syncs instantly.</p>
                    </div>

                    <!-- Camera Section -->
                    <div class="camera-section">
                        <button class="camera-btn" @click="startScanner()" x-show="!scannerActive">
                            📷 Open Camera to Scan
                        </button>
                        <button class="camera-btn" style="background: #ef4444;" @click="stopScanner()" x-show="scannerActive" x-cloak>
                            Stop Camera
                        </button>
                        <div id="reader" x-show="scannerActive" x-cloak></div>
                    </div>

                    <!-- Demo QR Codes Section -->
                    <div class="demo-qr-section">
                        <div class="demo-qr-title">Demo QR Codes</div>
                        <div class="demo-qr-grid">
                            <div class="demo-qr-item">
                                <div id="qr-display"></div>
                                <p x-text="currentDemoType === 'sign-in' ? 'Sign In QR' : 'Sign Out QR'"></p>
                            </div>
                        </div>
                        <div class="demo-buttons">
                            <button class="demo-sign-in" @click="demoClockIn()">✅ Demo: Sign In</button>
                            <button class="demo-sign-out" @click="demoClockOut()">⏹️ Demo: Sign Out</button>
                        </div>
                    </div>

                    <div class="manual-input">
                        <p>Or enter code manually:</p>
                        <div class="manual-row">
                            <input type="text" x-model="manualCode" placeholder="SIGN_IN or SIGN_OUT">
                        </div>
                        <button class="qr-button-styled" style="width: 100%; margin-top: 12px;" @click="manualSubmit()">Submit</button>
                    </div>

                    <div class="result-message" x-show="resultMessage" :class="resultType">
                        <p x-text="resultMessage"></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function scanApp() {
    return {
        sidebarOpen: false,
        user: { id: '', name: '', email: '' },
        manualCode: '',
        resultMessage: '',
        resultType: '',
        scannerActive: false,
        html5QrCode: null,
        activeQRText: 'sign_in', // Default fallback
        currentDemoType: 'sign-in',
        
        async init() {
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Staff', 'email' => 'staff@spysee.app']); ?>;
            this.user = userData;
            
            // Initial fetch of the active QR code from admin side
            await this.fetchActiveQR();
            
            // Refresh the QR code every 30 seconds to stay in sync with admin
            setInterval(() => this.fetchActiveQR(), 30000);
        },

        async fetchActiveQR() {
            try {
                const response = await fetch('api/active-qr.php');
                const data = await response.json();
                if (data.success && data.code) {
                    this.activeQRText = data.code;
                    this.renderQR();
                }
            } catch (err) {
                console.error('Failed to sync QR with server:', err);
                this.renderQR(); // Fallback to current state
            }
        },

        renderQR() {
            const container = document.getElementById("qr-display");
            container.innerHTML = '';
            if (typeof QRCode !== 'undefined') {
                new QRCode(container, {
                    text: this.activeQRText,
                    width: 120, // Adjusted for mobile view
                    height: 120, // Adjusted for mobile view
                    colorDark: "#093C5D",
                    colorLight: "#ffffff"
                });
            }
        },

        async startScanner() {
            this.scannerActive = true;
            this.html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };

            try {
                await this.html5QrCode.start(
                    { facingMode: "environment" }, 
                    config, 
                    (decodedText) => {
                        this.manualCode = decodedText;
                        this.manualSubmit();
                        this.stopScanner();
                    }
                );
            } catch (err) {
                console.error("Camera error:", err);
                this.scannerActive = false;
            }
        },

        async stopScanner() {
            if (this.html5QrCode) {
                await this.html5QrCode.stop();
                this.scannerActive = false;
            }
        },
        
        async demoClockIn() {
            const result = await window.qrUtils.recordScan('sign-in', this.user.id);
            if (result && result.success) {
                this.activeQRText = 'sign_in';
                this.resultMessage = `✅ Signed in successfully at ${new Date().toLocaleTimeString()}`;
                this.resultType = 'success';
            } else if (result) {
                this.resultMessage = result.message;
                this.resultType = 'error';
            }
            this.currentDemoType = 'sign-in';
            this.renderQR();
            setTimeout(() => { this.resultMessage = ''; }, 3000);
        },
        
        async demoClockOut() {
            const result = await window.qrUtils.recordScan('sign-out', this.user.id);
            if (result && result.success) {
                this.activeQRText = 'sign_out';
                this.resultMessage = `⏹️ Sign Out successfully at ${new Date().toLocaleTimeString()}`;
                this.resultType = 'success';
            } else if (result) {
                this.resultMessage = result.message;
                this.resultType = 'error';
            }
            this.currentDemoType = 'sign-out';
            this.renderQR();
            setTimeout(() => { this.resultMessage = ''; }, 3000);
        },
        
        async manualSubmit() {
            const type = window.qrUtils.getScanType(this.manualCode);
            if (!type) {
                this.resultMessage = 'Invalid code. Use SIGN_IN or SIGN_OUT';
                this.resultType = 'error';
                setTimeout(() => { this.resultMessage = ''; }, 3000);
                return;
            }
            const result = await window.qrUtils.recordScan(type, this.user.id);
            if (result && result.success) {
                this.resultMessage = `${type === 'sign-in' ? 'Signed in' : 'Sign Out'} successfully!`;
                this.resultType = 'success';
            } else if (result) {
                this.resultMessage = result.message;
                this.resultType = 'error';
            }
            this.manualCode = '';
            setTimeout(() => { this.resultMessage = ''; }, 3000);
        }
    }
}
</script>
</body>
</html>
