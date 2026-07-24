<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . route_url('/login'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator - Admin</title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        .qr-generator-container {
            padding: 10px 20px;
        }
        .generator-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .qr-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .qr-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            border-color: var(--olive-green);
        }
        .qr-card-header {
            padding: 12px 16px 0 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .qr-card-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--heading);
            margin: 0;
        }
        .qr-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .qr-badge.in {
            background: var(--olive-green-soft);
            color: var(--olive-green);
        }
        .qr-badge.out {
            background: rgba(245, 158, 11, 0.12);
            color: #D97706;
        }
        .qr-preview {
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, rgba(168, 201, 122, 0.05) 0%, rgba(168, 201, 122, 0.02) 100%);
            margin: 5px 15px;
            border-radius: 10px;
            min-height: 160px;
        }
        .qr-code-wrapper {
            background: white;
            padding: 0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        body.dark-mode .qr-code-wrapper {
            background: #0f172a;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .qr-code-display {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .qr-code-display canvas,
        .qr-code-display img {
            width: 120px !important;
            height: 120px !important;
            display: block;
            margin: 0 auto;
        }
        .qr-code-value {
            text-align: center;
            margin-top: 5px;
        }
        .qr-code-value code {
            background: var(--background);
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--heading);
            display: inline-block;
        }
        .qr-card-body {
            padding: 0 16px 12px 16px;
        }
        .form-group {
            margin-bottom: 8px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--heading);
        }
        .form-group input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--background);
            color: var(--heading);
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--olive-green);
            box-shadow: 0 0 0 3px rgba(168, 201, 122, 0.1);
        }
        .btn-generate {
            width: 100%;
            padding: 8px;
            background: transparent;
            border: 2px solid var(--olive-green);
            color: var(--olive-green);
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 12px;
        }
        .btn-generate:hover {
            background: var(--olive-green);
            color: var(--sidebar-blue);
        }
        .btn-activate {
            width: 100%;
            padding: 8px;
            background: var(--olive-green);
            border: none;
            color: var(--sidebar-blue);
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-activate:hover {
            background: var(--olive-green-bright);
            transform: translateY(-1px);
        }
        .btn-activate:active {
            transform: translateY(0);
        }
        .qr-card-footer {
            padding: 10px 16px 12px 16px;
            border-top: 1px solid var(--border-color);
            background: var(--background);
        }
        .qr-actions {
            display: flex;
            gap: 8px;
        }
        .btn-icon {
            flex: 1;
            padding: 10px;
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-icon:hover {
            border-color: var(--olive-green);
            color: var(--olive-green);
            background: var(--olive-green-soft);
        }
        .btn-icon.revoke:hover {
            border-color: #DC2626;
            color: #DC2626;
            background: rgba(220, 38, 38, 0.1);
        }
        /* Tablet and Mobile Views */
        @media (max-width: 1024px) {
            .generator-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .qr-generator-container {
                padding: 20px;
            }
        }
        .toast-message {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 12px 20px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        .toast-message.success {
            border-left: 4px solid var(--olive-green);
        }
        .toast-message.error {
            border-left: 4px solid #DC2626;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<script>window.themeManager.initTheme();</script>
<div x-data="qrGenerator()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'qr'; include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="qr-generator-container">
                <div style="margin-bottom: 10px;">
                    <h1 style="font-size: 24px; font-weight: 700; color: var(--heading); margin-bottom: 4px;">QR Code Generator</h1>
                    <p style="color: var(--text); font-size: 13px;">Create unique QR codes for clock-in/clock-out points.</p>
                </div>

                <div class="generator-grid">
                    <!-- Clock In QR Card -->
                    <div class="qr-card">
                        <div class="qr-card-header">
                            <h2>Clock In QR</h2>
                            <span class="qr-badge in">Clock In</span>
                        </div>
                        
                        <div class="qr-preview">
                            <div class="qr-code-wrapper">
                                <div class="qr-code-display" id="qrcode-in"></div>
                            </div>
                        </div>
                        
                        <div class="qr-code-value">
                            <code x-text="clockIn.code"></code>
                        </div>
                        
                        <div class="qr-card-body">
                            <div class="form-group">
                                <label>📍 Location Name</label>
                                <input type="text" x-model="clockIn.location" placeholder="e.g., HQ Entrance">
                            </div>
                            
                            <button class="btn-generate" @click="generateNewCode('in')">
                                🔄 Generate New Code
                            </button>
                            <button class="btn-activate" @click="saveQR('in')">
                                ✅ Activate QR Code
                            </button>
                        </div>
                        
                        <div class="qr-card-footer">
                            <div class="qr-actions">
                                <button class="btn-icon" @click="downloadQR('in')">
                                    📥 PNG
                                </button>
                                <button class="btn-icon" @click="generateNewCode('in')">
                                    🆕 New
                                </button>
                                <button class="btn-icon revoke" @click="revokeQR('in')">
                                    🔴 Revoke
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Clock Out QR Card -->
                    <div class="qr-card">
                        <div class="qr-card-header">
                            <h2>Clock Out QR</h2>
                            <span class="qr-badge out">Clock Out</span>
                        </div>
                        
                        <div class="qr-preview">
                            <div class="qr-code-wrapper">
                                <div class="qr-code-display" id="qrcode-out"></div>
                            </div>
                        </div>
                        
                        <div class="qr-code-value">
                            <code x-text="clockOut.code"></code>
                        </div>
                        
                        <div class="qr-card-body">
                            <div class="form-group">
                                <label>📍 Location Name</label>
                                <input type="text" x-model="clockOut.location" placeholder="e.g., HQ Exit">
                            </div>
                            
                            <button class="btn-generate" @click="generateNewCode('out')">
                                🔄 Generate New Code
                            </button>
                            <button class="btn-activate" @click="saveQR('out')">
                                ✅ Activate QR Code
                            </button>
                        </div>
                        
                        <div class="qr-card-footer">
                            <div class="qr-actions">
                                <button class="btn-icon" @click="downloadQR('out')">
                                    📥 PNG
                                </button>
                                <button class="btn-icon" @click="generateNewCode('out')">
                                    🆕 New
                                </button>
                                <button class="btn-icon revoke" @click="revokeQR('out')">
                                    🔴 Revoke
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function qrGenerator() {
    return {
        sidebarOpen: false,
        clockIn: { 
            code: 'CLK_IN_' + Math.random().toString(36).substring(2, 10).toUpperCase(), 
            location: 'HQ Entrance',
            type: 'CLOCK_IN',
            isActive: true,
            createdAt: new Date().toISOString()
        },
        clockOut: { 
            code: 'CLK_OUT_' + Math.random().toString(36).substring(2, 10).toUpperCase(), 
            location: 'HQ Exit',
            type: 'CLOCK_OUT',
            isActive: true,
            createdAt: new Date().toISOString()
        },
        qrIn: null,
        qrOut: null,

        init() {
            window.themeManager.initTheme();
            this.loadSavedQRs();
            this.initializeQRCodes();
        },
        
        loadSavedQRs() {
            // Load saved QR codes from localStorage
            const savedIn = localStorage.getItem('clockInQR');
            const savedOut = localStorage.getItem('clockOutQR');
            
            if (savedIn) {
                const data = JSON.parse(savedIn);
                this.clockIn = { ...this.clockIn, ...data };
            }
            if (savedOut) {
                const data = JSON.parse(savedOut);
                this.clockOut = { ...this.clockOut, ...data };
            }
        },
        
        saveToLocalStorage() {
            localStorage.setItem('clockInQR', JSON.stringify({
                code: this.clockIn.code,
                location: this.clockIn.location,
                type: this.clockIn.type,
                isActive: this.clockIn.isActive,
                createdAt: this.clockIn.createdAt
            }));
            localStorage.setItem('clockOutQR', JSON.stringify({
                code: this.clockOut.code,
                location: this.clockOut.location,
                type: this.clockOut.type,
                isActive: this.clockOut.isActive,
                createdAt: this.clockOut.createdAt
            }));
        },
        
        initializeQRCodes() {
            const inContainer = document.getElementById("qrcode-in");
            const outContainer = document.getElementById("qrcode-out");

            // Clear containers to prevent duplication
            if (inContainer) inContainer.innerHTML = '';
            if (outContainer) outContainer.innerHTML = '';

            this.qrIn = new QRCode(inContainer, {
                text: JSON.stringify({
                    code: this.clockIn.code,
                    type: this.clockIn.type,
                    location: this.clockIn.location,
                    timestamp: new Date().toISOString()
                }),
                width: 130,
                height: 130,
                colorDark: "#093C5D",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            
            this.qrOut = new QRCode(outContainer, {
                text: JSON.stringify({
                    code: this.clockOut.code,
                    type: this.clockOut.type,
                    location: this.clockOut.location,
                    timestamp: new Date().toISOString()
                }),
                width: 130,
                height: 130,
                colorDark: "#093C5D",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        },

        updateCode(type) {
            const qrData = {
                code: type === 'in' ? this.clockIn.code : this.clockOut.code,
                type: type === 'in' ? this.clockIn.type : this.clockOut.type,
                location: type === 'in' ? this.clockIn.location : this.clockOut.location,
                timestamp: new Date().toISOString()
            };
            
            if (type === 'in') {
                this.qrIn.clear();
                this.qrIn.makeCode(JSON.stringify(qrData));
            } else {
                this.qrOut.clear();
                this.qrOut.makeCode(JSON.stringify(qrData));
            }
        },
        
        generateNewCode(type) {
            const newCode = type === 'in' 
                ? 'CLK_IN_' + Math.random().toString(36).substring(2, 10).toUpperCase()
                : 'CLK_OUT_' + Math.random().toString(36).substring(2, 10).toUpperCase();
            
            if (type === 'in') {
                this.clockIn.code = newCode;
                this.clockIn.createdAt = new Date().toISOString();
                this.updateCode('in');
            } else {
                this.clockOut.code = newCode;
                this.clockOut.createdAt = new Date().toISOString();
                this.updateCode('out');
            }
            
            this.saveToLocalStorage();
            window.appUtils.showToast('New QR code generated successfully!', 'success');
        },

        async saveQR(type) {
            const data = type === 'in' ? this.clockIn : this.clockOut;
            
            // Save to backend
            try {
                const result = await window.api.post('api/save-qr.php', {
                    code: data.code,
                    location: data.location,
                    type: data.type,
                    isActive: true,
                    createdAt: data.createdAt
                });
                
                if (result.success) {
                    data.isActive = true;
                    this.saveToLocalStorage();
                    window.appUtils.showToast(`${type === 'in' ? 'Clock In' : 'Clock Out'} QR Code activated successfully!`, 'success');
                } else {
                    window.appUtils.showToast('Error saving QR code. Please try again.', 'error');
                }
            } catch (error) {
                // Fallback to local storage if API is not available
                data.isActive = true;
                this.saveToLocalStorage();
                window.appUtils.showToast(`${type === 'in' ? 'Clock In' : 'Clock Out'} QR Code saved locally!`, 'success');
            }
        },
        
        revokeQR(type) {
            if (confirm(`Are you sure you want to revoke this ${type === 'in' ? 'Clock In' : 'Clock Out'} QR code?`)) {
                if (type === 'in') {
                    this.clockIn.isActive = false;
                } else {
                    this.clockOut.isActive = false;
                }
                this.saveToLocalStorage();
                window.appUtils.showToast(`${type === 'in' ? 'Clock In' : 'Clock Out'} QR Code revoked!`, 'success');
            }
        },
        
        downloadQR(type) {
            const qrElement = type === 'in' ? document.querySelector('#qrcode-in canvas') : document.querySelector('#qrcode-out canvas');
            if (!qrElement) {
                window.appUtils.showToast('QR code not ready. Please generate first.', 'error');
                return;
            }
            
            const link = document.createElement('a');
            const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
            const filename = type === 'in' 
                ? `clock_in_${this.clockIn.location.replace(/\s/g, '_')}_${timestamp}.png`
                : `clock_out_${this.clockOut.location.replace(/\s/g, '_')}_${timestamp}.png`;
            
            link.download = filename;
            link.href = qrElement.toDataURL('image/png');
            link.click();
            
            window.appUtils.showToast('QR code downloaded as PNG!', 'success');
        }
    }
}
</script>
</body>
</html>