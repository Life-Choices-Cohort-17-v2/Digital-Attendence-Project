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
        .scan-container { padding: 28px 32px; max-width: 600px; margin: 0 auto; }
        .scan-container h1 { font-size: 28px; font-weight: 700; color: var(--heading); margin-bottom: 8px; }
        .scan-container > p { color: var(--text); font-size: 14px; margin-bottom: 24px; }
        .scanner-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 24px; }
        .scanner-header { text-align: center; margin-bottom: 20px; }
        .ready-badge { display: inline-block; background: var(--accent-soft); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-bottom: 16px; }
        .scanner-header p { color: var(--text); font-size: 14px; }
        .camera-btn { width: 100%; padding: 16px; background: var(--accent); color: #202020; border: none; border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer; transition: 0.2s; }
        .camera-btn:hover { background: var(--primary-green-dark); color: #f8f8f8; }
        .camera-btn.stop { background: #ef4444; color: white; }
        .camera-btn.stop:hover { background: #dc2626; }
        #reader { width: 100%; border-radius: 12px; overflow: hidden; margin-top: 15px; min-height: 300px; }
        #reader video { width: 100%; height: auto; min-height: 300px; object-fit: cover; }
        .camera-switch-btn { width: 100%; padding: 10px; margin-top: 10px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; color: var(--text); font-size: 14px; cursor: pointer; }
        .camera-switch-btn:hover { border-color: var(--accent); color: var(--heading); }
        .result-overlay { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.75); backdrop-filter: blur(8px); }
        .result-card { background: var(--card-bg); border-radius: 24px; padding: 40px 48px; max-width: 420px; width: 90%; text-align: center; border: 1px solid var(--border-color); }
        .result-card .icon { font-size: 64px; margin-bottom: 16px; }
        .result-card .title { font-size: 24px; font-weight: 700; color: var(--heading); margin-bottom: 8px; }
        .result-card .subtitle { font-size: 14px; color: var(--text); margin-bottom: 4px; }
        .result-card .detail { font-size: 13px; color: var(--muted); margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); }
        .result-card .btn-close-result { margin-top: 20px; padding: 12px 32px; background: var(--accent); color: #202020; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .result-card .btn-close-result:hover { background: var(--primary-green-dark); color: #f8f8f8; }
        .result-card.success .icon { color: var(--accent); }
        .result-card.success .title { color: var(--accent); }
        .result-card.error .icon { color: #EF4444; }
        .result-card.error .title { color: #EF4444; }
        .scan-instructions { margin-top: 16px; padding: 16px; background: var(--background); border-radius: 12px; text-align: center; font-size: 13px; color: var(--text); }
        .scan-instructions strong { color: var(--heading); }
        @media (max-width: 480px) {
            .scan-container { padding: 16px; }
            .scanner-card { padding: 16px; }
            #reader { min-height: 250px; }
            #reader video { min-height: 250px; }
            .result-card { padding: 28px 20px; }
            .result-card .icon { font-size: 48px; }
        }

        .ui-icon {
        width: 28px;
        height: 28px;
        display: inline-block;
        vertical-align: -5px;
        margin-right: 8px;
        }

        .button-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .status-icon {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
        }

        .inline-icon {
            width: 16px;
            height: 16px;
            display: inline-block;
            vertical-align: -3px;
            margin-right: 4px;
        }

        .ready-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .camera-btn,
        .camera-switch-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .scan-instructions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .result-card .icon svg {
            width: 64px;
            height: 64px;
        }

        .result-card.success .icon svg {
            color: var(--accent);
        }

        .result-card.error .icon svg {
            color: #EF4444;
        }

    </style>
</head>
<body>

<div x-data="scanApp()" x-init="init()" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'scan'; include __DIR__ . '/staff-sidebar.php'; ?>
        <main class="main-content">
            <?php include __DIR__ . '/../partials/top-nav.php'; ?>
            <div class="scan-container">
                <h1>
                    <svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                        <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                        <path d="M8 8h8v8H8z"></path>
                    </svg>
                    Scan QR Code
                </h1>
                <p>Point your camera at the workplace QR code to sign in or out.</p>
                <div class="scanner-card">
                    <div class="scanner-header">
                        <span class="ready-badge" x-show="!scannerActive">
                        <svg class="status-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="12" cy="12" r="5"></circle>
                        </svg>
                        Ready to scan
                        </span>
                        <span
                        class="ready-badge"
                        x-show="scannerActive && !_scanDetected"
                        style="background: #fef3c7; color: #d97706;"
                        x-cloak
                        >
                        <svg class="status-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <circle cx="12" cy="12" r="5"></circle>
                        </svg>
                        Scanning...
                        </span>
                        <span
                        class="ready-badge"
                        x-show="scannerActive && _scanDetected"
                        style="background: var(--accent-soft); color: var(--accent);"
                        x-cloak
                        >
                        <svg class="status-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 12l4 4L19 6"></path>
                        </svg>
                        Scan captured
                        </span>
                        <p x-show="!_scanDetected">Hold steady and center the QR code in the frame.</p>
                        <p x-show="_scanDetected" style="color: var(--accent);" x-cloak>✓ Scan captured! Redirecting...</p>
                    </div>
                    <div class="camera-section">
                        <button class="camera-btn" @click="startScanner()" x-show="!scannerActive && !_scanDetected">
                        <svg class="button-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 7h4l2-3h4l2 3h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"></path>
                            <circle cx="12" cy="13" r="3"></circle>
                        </svg>
                        Open Camera
                        </button>
                        <button class="camera-btn stop" @click="stopScanner()" x-show="scannerActive" x-cloak>
                        <svg class="button-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="6" y="6" width="12" height="12" rx="2"></rect>
                        </svg>
                        Stop Camera
                        </button>
                        <div id="reader" x-show="scannerActive" x-cloak></div>
                        <button class="camera-switch-btn" @click="switchCamera()" x-show="scannerActive" x-cloak>
                        <svg class="button-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17 2l4 4-4 4"></path>
                            <path d="M3 12V9a3 3 0 0 1 3-3h15"></path>
                            <path d="M7 22l-4-4 4-4"></path>
                            <path d="M21 12v3a3 3 0 0 1-3 3H3"></path>
                        </svg>
                        Switch Camera
                        </button>
                    </div>
                    <div class="scan-instructions">
                    <svg class="inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M9 18h6"></path>
                        <path d="M10 22h4"></path>
                        <path d="M8.5 14.5a6 6 0 1 1 7 0c-.8.6-1.5 1.5-1.5 2.5h-4c0-1-.7-1.9-1.5-2.5z"></path>
                    </svg>

                    <strong>Tip:</strong>
                    Make sure the QR code is well-lit and centered.
                    </div>
                </div>
            </div>
        </main>
    </div>

                <div class="result-overlay" x-show="showResult" x-cloak @click.away="closeResult()">
                    <div class="result-card" :class="resultType">
                        <div class="icon">
                <svg
                    x-show="resultType === 'success'"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M8 12l2.5 2.5L16 9"></path>
                </svg>

                <svg
                    x-show="resultType !== 'success'"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M9 9l6 6"></path>
                    <path d="M15 9l-6 6"></path>
                </svg>
            </div>
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
        scannerActive: false,
        html5QrCode: null,
        isProcessing: false,
        cameraFacing: 'environment',
        showResult: false,
        resultType: '',
        resultTitle: '',
        resultSubtitle: '',
        resultDetail: '',
        
        // 🛡️ SCAN LOCK
        _scanDetected: false,
        _lastScanTime: 0,
        _lockKey: 'spysee_scan_lock',
        _redirecting: false,
        _scanId: null,
        
        async init() {
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Staff', 'email' => 'staff@spysee.app']); ?>;
            this.user = userData;
            window.themeManager.initTheme();
            
            const lockData = localStorage.getItem(this._lockKey);
            if (lockData) {
                try {
                    const parsed = JSON.parse(lockData);
                    if (Date.now() - parsed.timestamp < 5000) {
                        this._scanDetected = true;
                        console.log('🔒 Scan lock active (from localStorage)');
                    } else {
                        localStorage.removeItem(this._lockKey);
                    }
                } catch(e) {
                    localStorage.removeItem(this._lockKey);
                }
            }
            
            if (new URLSearchParams(window.location.search).get('scan') === 'success') {
                localStorage.removeItem(this._lockKey);
                this.showResultOverlay('success', '✅ Scan Successful!', 'You have been signed in/out', 'Redirecting...');
                setTimeout(() => { 
                    window.history.replaceState({}, document.title, window.location.pathname); 
                    this.closeResult(); 
                }, 3000);
                return;
            }
            
            if (!lockData) {
                this._scanDetected = false;
                this._redirecting = false;
                this._lastScanTime = 0;
                this._scanId = null;
                this.isProcessing = false;
            }
            
            window.addEventListener('beforeunload', () => {
                if (this.html5QrCode) {
                    try { this.html5QrCode.stop(); this.html5QrCode.clear(); } catch(e) {}
                }
            });
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
                localStorage.removeItem(this._lockKey);
                window.location.href = '/index.php/staff-dashboard';
            }
        },

        async startScanner() {
            console.log('📷 startScanner() called');
            
            const lockData = localStorage.getItem(this._lockKey);
            if (lockData) {
                try {
                    const parsed = JSON.parse(lockData);
                    if (Date.now() - parsed.timestamp < 5000) {
                        this._scanDetected = true;
                        console.log('🔒 Scan lock active, cannot start scanner');
                        return;
                    }
                } catch(e) {}
            }
            
            if (this._scanDetected || this._redirecting) return;
            this.scannerActive = true;
            this._scanDetected = false;
            this._lastScanTime = 0;
            this._scanId = null;
            this.isProcessing = false;
            
            try {
                console.log('📷 Creating HTML5QRCode instance...');
                this.html5QrCode = new Html5Qrcode("reader");
                
                await this.html5QrCode.start(
                    { facingMode: this.cameraFacing },
                    { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
                    (decodedText) => {
                        console.log('📱 Scanner callback fired at:', Date.now());
                        
                        // 🛡️ IMMEDIATE CHECK - prevents ANY second detection
                        if (this.isProcessing || this._scanDetected || this._redirecting) {
                            console.log('⛔ Already processing, ignoring scan');
                            return;
                        }
                        
                        // 🛡️ IMMEDIATELY lock - BEFORE anything else
                        this.isProcessing = true;
                        this._scanDetected = true;
                        
                        // 🛡️ Generate unique scan ID
                        this._scanId = Date.now().toString(36) + Math.random().toString(36).substring(2, 6);
                        
                        console.log('✅ Scan accepted! scanId:', this._scanId);
                        
                        // 🛡️ Call handleScan - this will redirect
                        this.handleScan(decodedText);
                    },
                    (error) => {
                        // Ignore
                    }
                );
                console.log('📷 Scanner started successfully');
            } catch (err) {
                console.error('❌ Scanner error:', err);
                this.scannerActive = false;
                if (this.cameraFacing === 'environment') {
                    this.cameraFacing = 'user';
                    try {
                        await this.html5QrCode.start(
                            { facingMode: 'user' },
                            { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
                            (decodedText) => {
                                if (this.isProcessing || this._scanDetected || this._redirecting) {
                                    console.log('⛔ Already processing, ignoring scan');
                                    return;
                                }
                                this.isProcessing = true;
                                this._scanDetected = true;
                                this._scanId = Date.now().toString(36) + Math.random().toString(36).substring(2, 6);
                                console.log('✅ Scan accepted! scanId:', this._scanId);
                                this.handleScan(decodedText);
                            },
                            (error) => {}
                        );
                        return;
                    } catch(e) {
                        console.error('❌ Backup camera error:', e);
                    }
                }
                this.showResultOverlay('error', '❌ Camera Error', 'Camera access denied. Please allow camera access.', '');
            }
        },

        async switchCamera() {
            this.cameraFacing = this.cameraFacing === 'environment' ? 'user' : 'environment';
            if (this.html5QrCode) {
                await this.stopScanner();
                setTimeout(() => this.startScanner(), 500);
            }
        },

        async stopScanner() {
            console.log('⏹️ stopScanner() called');
            if (this.html5QrCode) {
                try { 
                    await this.html5QrCode.stop(); 
                    this.html5QrCode.clear(); 
                    this.html5QrCode = null;
                } catch(e) {
                    console.log('Scanner stop error:', e);
                }
                this.scannerActive = false;
            }
        },

        // 🛡️ CRITICAL: handleScan with INSTANT redirect
        async handleScan(decodedText) {
            console.log('📱 handleScan() called with:', decodedText);
            
            // 🛡️ GUARD: If already redirecting, stop
            if (this._redirecting) {
                console.log('⛔ Already redirecting, returning');
                return;
            }
            
            // 🛡️ Set redirecting flag IMMEDIATELY
            this._redirecting = true;
            
            console.log('📱 Processing scan, scanId:', this._scanId);
            
            const scanId = this._scanId || Date.now().toString(36) + Math.random().toString(36).substring(2, 6);
            
            let staffId = this.user.id;
            let location = 'HQ';
            let name = this.user.name;
            
            if (decodedText.includes('scan.php?')) {
                try {
                    const url = new URL(decodedText);
                    staffId = url.searchParams.get('staff_id') || this.user.id;
                    location = url.searchParams.get('location') || 'HQ';
                    name = url.searchParams.get('name') || this.user.name;
                    if (staffId === 'QR_SCAN' || staffId === 'QR_Scan') {
                        staffId = this.user.id;
                        name = this.user.name;
                    }
                } catch(e) {
                    console.warn('URL parsing failed:', e);
                }
            }
            
            const scanUrl = '/scan.php?' + new URLSearchParams({
                scan_id: scanId,
                token: Math.random().toString(36).substring(2, 10),
                expires: new Date(Date.now() + 30000).toISOString(),
                staff_id: staffId,
                name: name,
                method: 'QR',
                location: location
            });
            
            console.log('🚀 Redirecting to:', scanUrl);
            
            // 🛡️ KILL THE SCANNER IMMEDIATELY
            try {
                if (this.html5QrCode) {
                    await this.html5QrCode.stop();
                    this.html5QrCode.clear();
                    this.html5QrCode = null;
                }
            } catch(e) {
                console.log('Scanner cleanup error:', e);
            }
            this.scannerActive = false;
            
            // 🛡️ Store in localStorage
            localStorage.setItem(this._lockKey, JSON.stringify({
                timestamp: Date.now(),
                scanned: true,
                scanId: scanId,
                processed: true
            }));
            
            console.log('✅ Redirecting NOW...');
            
            // 🛡️ USE window.location.replace - INSTANT, no back button issues
            window.location.replace(scanUrl);
        }
    }
}
</script>

</body>
</html>