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
            background: var(--sidebar-blue);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }
        .camera-btn:hover {
            background: var(--sidebar-hover);
        }
        .camera-btn.stop {
            background: #ef4444;
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
        
        .zoom-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-top: 12px;
            padding: 8px;
            background: var(--background);
            border-radius: 12px;
        }
        .zoom-controls button {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            background: var(--card-bg);
            color: var(--heading);
            font-size: 24px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: 0.2s;
            touch-action: manipulation;
        }
        .zoom-controls button:active {
            transform: scale(0.9);
        }
        .zoom-controls .zoom-level {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            min-width: 50px;
            text-align: center;
        }
        
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
        .result-message.info {
            display: block;
            background: #f0f0ed;
            color: var(--muted);
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
                <h1>Scan QR Code</h1>
                <p>Point your camera at the workplace QR code to sign in or out.</p>

                <div class="scanner-card">
                    <div class="scanner-header">
                        <span class="ready-badge" x-show="!scannerActive">● Ready to scan</span>
                        <span class="ready-badge" x-show="scannerActive" style="background: #fef3c7; color: #d97706;" x-cloak>● Scanning...</span>
                        <p>Hold steady and center the QR code in the frame.</p>
                    </div>

                    <div class="camera-section">
                        <button class="camera-btn" @click="startScanner()" x-show="!scannerActive">
                            📷 Open Camera to Scan
                        </button>
                        <button class="camera-btn stop" @click="stopScanner()" x-show="scannerActive" x-cloak>
                            ⏹️ Stop Camera
                        </button>
                        <div id="reader" x-show="scannerActive" x-cloak></div>
                        
                        <div class="zoom-controls" x-show="scannerActive" x-cloak>
                            <button @click="zoomOut()" title="Zoom Out">−</button>
                            <span class="zoom-level" x-text="Math.round(zoomLevel * 100) + '%'"></span>
                            <button @click="zoomIn()" title="Zoom In">+</button>
                        </div>
                    </div>

                    <div class="result-message" :class="resultType" x-show="resultMessage" x-text="resultMessage"></div>
                    
                    <div class="scan-instructions">
                        💡 <strong>Tip:</strong> If the camera won't focus, tap the screen to adjust.
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
        resultMessage: '',
        resultType: '',
        scannerActive: false,
        html5QrCode: null,
        zoomLevel: 1.0,
        isProcessing: false,
        
        async init() {
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Staff', 'email' => 'staff@spysee.app']); ?>;
            this.user = userData;
            window.themeManager.initTheme();
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
                
                const cameraId = { facingMode: "environment" };
                
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
                
                this.applyZoom();
                
            } catch (err) {
                console.error("Camera error:", err);
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
            }
        },

        async stopScanner() {
            if (this.html5QrCode) {
                try {
                    await this.html5QrCode.stop();
                } catch (err) {
                    // ignore
                }
                this.scannerActive = false;
            }
        },
        
        zoomIn() {
            this.zoomLevel = Math.min(this.zoomLevel + 0.1, 3.0);
            this.applyZoom();
        },
        
        zoomOut() {
            this.zoomLevel = Math.max(this.zoomLevel - 0.1, 0.5);
            this.applyZoom();
        },
        
        applyZoom() {
            const video = document.querySelector('#reader video');
            if (video) {
                video.style.transform = `scale(${this.zoomLevel})`;
                video.style.transformOrigin = 'center center';
            }
        },

        async handleScan(decodedText) {
            if (this.isProcessing) return;
            this.isProcessing = true;
            
            console.log('QR Code detected:', decodedText);
            
            try {
                if (decodedText.includes('scan.php?')) {
                    const url = new URL(decodedText);
                    const staffId = url.searchParams.get('staff_id') || this.user.id;
                    const location = url.searchParams.get('location') || 'HQ';
                    const name = url.searchParams.get('name') || this.user.name;
                    
                    const response = await fetch('/scan.php?' + url.searchParams.toString());
                    
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }
                    
                    const result = await this.recordScan(staffId, name, location);
                    if (result && result.success) {
                        this.resultMessage = result.message || '✅ Sign in/out successful!';
                        this.resultType = 'success';
                        this.stopScanner();
                        setTimeout(() => {
                            window.location.href = '/index.php/staff-dashboard';
                        }, 1500);
                        return;
                    }
                }
                
                const result = await this.recordScan(this.user.id, this.user.name, 'HQ');
                if (result && result.success) {
                    this.resultMessage = result.message || '✅ Sign in/out successful!';
                    this.resultType = 'success';
                    this.stopScanner();
                    setTimeout(() => {
                        window.location.href = '/index.php/staff-dashboard';
                    }, 1500);
                    return;
                }
                
            } catch (err) {
                console.error('Error processing scan:', err);
                this.resultMessage = '❌ Failed to process QR code. Please try again.';
                this.resultType = 'error';
            }
            
            this.isProcessing = false;
            
            if (this.resultType === 'success') {
                this.stopScanner();
            }
        },

        async recordScan(staffId, name, location) {
            try {
                const response = await fetch('/api/sign-in', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: staffId,
                        name: name,
                        location: location
                    })
                });
                
                const result = await response.json();
                
                if (result.message === 'Already Signed in') {
                    const outResponse = await fetch('/api/sign-out', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: staffId,
                            name: name,
                            location: location
                        })
                    });
                    return await outResponse.json();
                }
                
                return result;
            } catch (err) {
                console.error('API Error:', err);
                return { success: false, message: 'Connection error' };
            }
        }
    }
}
</script>
</body>
</html>