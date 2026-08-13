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
    <title><?= $title ?? 'Staff Dashboard' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        /* STAFF DASHBOARD - EXACT MATCH TO SCREENSHOT */
        .staff-dashboard-container {
            max-width: 100%;
            padding: 28px 32px;
        }

        /* Hi Sarah header */
        .greeting {
            margin-bottom: 32px;
        }
        .greeting h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 6px;
        }
        .greeting p {
            font-size: 14px;
            color: var(--text);
        }

        /* Status block - matching screenshot exactly */
        .status-block {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 32px;
            width: 100%;
        }
        .offsite-badge {
            display: inline-block;
            background: rgba(168, 201, 122, 0.12);
            color: #A8C97A;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 0.3px;
        }
        .status-block p {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .status-block h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--heading);
            margin-bottom: 24px;
        }
        .scan-btn {
            display: inline-block;
            background: var(--sidebar-blue);
            color: white;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }
        .scan-btn:hover {
            background: var(--sidebar-hover);
        }

        /* Menu items - NO EMOJIS, just text like screenshot */
        .menu-items {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        @media (max-width: 600px) {
            .menu-items {
                grid-template-columns: 1fr;
            }
        }
        .menu-item {
            display: block;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px 16px;
            text-decoration: none;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }
        .menu-item:hover {
            border-color: var(--olive-green);
            transform: translateY(-2px);
            background: var(--olive-green-soft);
        }
        .menu-item h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--heading);
            margin-bottom: 4px;
        }
        .menu-item p {
            font-size: 13px;
            color: var(--text);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal-container {
            background: var(--card-bg);
            border-radius: 24px;
            width: 420px;
            max-width: 95%;
            max-height: none;
            overflow-y: visible;
        }
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--heading);
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text);
        }
        .modal-body {
            padding: 12px 20px;
        }
        .modal-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn-cancel {
            padding: 10px 20px;
            background: #F0F2F5;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-submit {
            padding: 10px 20px;
            background: var(--sidebar-blue);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
        }
        body.dark-mode .btn-cancel {
            background: #334155;
            color: white;
        }

        /* Calendar Grid */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 12px;
        }
        @media (max-width: 480px) {
            .calendar-grid {
                gap: 4px;
            }
            .calendar-day {
                font-size: 12px;
            }
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            padding: 8px;
            color: var(--text);
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            cursor: pointer;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            font-size: 14px;
            transition: 0.2s;
        }
        .calendar-day:hover {
            background: var(--olive-green-soft);
        }
        .calendar-day.selected {
            background: var(--olive-green);
            color: var(--sidebar-blue);
            border-color: var(--olive-green);
        }
        .calendar-day.other-month {
            opacity: 0.3;
        }
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .calendar-nav button {
            padding: 8px 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
        }
        .selected-date-info {
            margin-top: 20px;
            padding: 16px;
            background: var(--olive-green-soft);
            border-radius: 12px;
            text-align: center;
        }

        /* Form Styles */
        .input-group {
            margin-bottom: 10px;
        }
        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--heading);
        }
        .input-group input, .input-group select, .input-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--heading);
            font-family: inherit;
            font-size: 13px;
        }
        .input-group textarea {
            resize: vertical;
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body>

<div x-data="dashboardApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'dashboard'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="staff-dashboard-container">
                <!-- Greeting - EXACT as screenshot -->
                <div class="greeting">
                    <h1>Hi, <?= htmlspecialchars($user['name'] ?? 'User') ?></h1>
                    <p x-text="currentDateFormatted"></p>
                </div>

                <!-- Status Block - EXACT as screenshot (no extra elements) -->
                <div class="status-block">
                    <div class="offsite-badge" x-text="isClockedIn ? 'ONSITE' : 'OFFSITE'" :style="isClockedIn ? 'color: #9CB07A; background: rgba(156, 176, 122, 0.12);' : ''">OFFSITE</div>
                    <p>You are currently</p>
                    <h2 x-text="isClockedIn ? 'Clocked In' : 'Clocked Out'">Clocked Out</h2>
                    <a href="<?= route_url('/scan-qr') ?>" class="scan-btn">Scan QR</a>
                </div>

                <!-- Menu Items - NO EMOJIS, just text like screenshot -->
                <div class="menu-items">
                    <div class="menu-item" @click="openCalendarModal()">
                        <h3>Calendar</h3>
                        <p>View your schedule</p>
                    </div>
                    <div class="menu-item" @click="openLeaveModal()">
                        <h3>Leave Requests</h3>
                        <p>Submit a new request</p>
                    </div>
                    <div class="menu-item" onclick="window.location.href='<?= route_url('/profile') ?>'">
                        <h3>Profile</h3>
                        <p>Manage your account</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Calendar Modal Popup -->
    <div x-show="calendarModalOpen" x-cloak class="modal-overlay" @click.away="calendarModalOpen = false">
        <div class="modal-container">
            <div class="modal-header">
                <h2>Calendar</h2>
                <button class="modal-close" @click="calendarModalOpen = false">✕</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 12px; color: var(--text);">Pick a date to view your schedule.</p>
                <div class="calendar-nav">
                    <button @click="prevMonth()">←</button>
                    <h3 x-text="monthName + ' ' + year"></h3>
                    <button @click="nextMonth()">→</button>
                </div>
                <div class="calendar-grid">
                    <template x-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']">
                        <div class="calendar-day-header" x-text="day"></div>
                    </template>
                    <template x-for="day in calendarDays" :key="day">
                        <div class="calendar-day" :class="{ 'other-month': !day, 'selected': selectedDay === day && day }" @click="day && selectDay(day)">
                            <span x-text="day || ''"></span>
                        </div>
                    </template>
                </div>
                <div class="selected-date-info" x-show="selectedDay">
                    <p><strong>Selected:</strong> <span x-text="selectedDateFormatted"></span></p>
                    <p x-text="selectedStatus"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" @click="calendarModalOpen = false">Close</button>
            </div>
        </div>
    </div>

    <!-- Leave Request Modal Popup -->
    <div x-show="leaveModalOpen" x-cloak class="modal-overlay" @click.away="leaveModalOpen = false">
        <div class="modal-container">
            <div class="modal-header">
                <h2>New Leave Request</h2>
                <button class="modal-close" @click="leaveModalOpen = false">✕</button>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 12px; color: var(--text);">Submit a request for time off.</p>
                <div class="input-group" style="margin-bottom: 12px;">
                    <label>Type</label>
                    <select x-model="leaveRequest.type">
                        <option value="OFFSITE">OFFSITE</option>
                        <option value="Annual leave">Annual leave</option>
                        <option value="Sick leave">Sick leave</option>
                        <option value="Personal leave">Personal leave</option>
                    </select>
                </div>
                <div class="input-group" style="margin-bottom: 12px;">
                    <label>From</label>
                    <input type="date" x-model="leaveRequest.from">
                </div>
                <div class="input-group" style="margin-bottom: 12px;">
                    <label>To</label>
                    <input type="date" x-model="leaveRequest.to">
                </div>
                <div class="input-group" style="margin-bottom: 12px;">
                    <label>Reason (optional)</label>
                    <textarea x-model="leaveRequest.reason" rows="3" placeholder="Add a short note for your manager"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" @click="leaveModalOpen = false">Cancel</button>
                <button class="btn-submit" @click="submitLeaveRequest()">Submit request</button>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardApp() {
    return {
        sidebarOpen: false,
        isClockedIn: false,
        calendarModalOpen: false,
        currentDateObj: new Date(),
        user: { id: '', name: '', email: '' }, // Initialize user object
        selectedDay: null,
        leaveModalOpen: false,
        leaveRequest: {
            type: 'OFFSITE',
            from: '',
            to: '',
            reason: '',
        },
        
        get year() { return this.currentDateObj.getFullYear(); },
        get currentDateFormatted() {
            return new Date().toLocaleDateString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },
        get month() { return this.currentDateObj.getMonth(); },
        get monthName() { return this.currentDateObj.toLocaleString('default', { month: 'long' }); },
        get firstDay() { return new Date(this.year, this.month, 1).getDay(); },
        get daysInMonth() { return new Date(this.year, this.month + 1, 0).getDate(); },
        get calendarDays() {
            const days = [];
            for (let i = 0; i < this.firstDay; i++) days.push(null);
            for (let i = 1; i <= this.daysInMonth; i++) days.push(i);
            return days;
        },
        get selectedDateFormatted() {
            if (!this.selectedDay) return '';
            const date = new Date(this.year, this.month, this.selectedDay);
            return date.toLocaleDateString('en-ZA', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
        },
        get selectedStatus() {
            const status = this.getDayStatus(this.selectedDay);
            if (status === 'present') return 'You were present on this day';
            if (status === 'absent') return 'No clock-in recorded on this day';
            if (status === 'leave') return 'You were on leave this day';
            return 'No attendance data for this day';
        },
        
        async init() {
            window.themeManager.initTheme();
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'User', 'email' => 'user@clockit.app']); ?>;
            this.user = userData;
            await this.updateStatus();
            
            // Poll for status updates every 10 seconds for real-time sync
            setInterval(() => this.updateStatus(), 10000);
        },
        
        async updateStatus() {
            try {
                // Use absolute path for consistency with other API calls
                const response = await fetch('api/onsite-staff.php');
                const data = await response.json();
                const onsiteStaff = data.data || [];
                this.isClockedIn = onsiteStaff.some(s => s.name === this.user.name);
            } catch (err) {
                console.error('Error checking status:', err);
            }
        },
        
        openCalendarModal() {
            this.currentDateObj = new Date();
            this.selectedDay = new Date().getDate();
            this.calendarModalOpen = true;
        },
        
        prevMonth() {
            this.currentDateObj = new Date(this.year, this.month - 1);
            this.selectedDay = null;
        },
        
        nextMonth() {
            this.currentDateObj = new Date(this.year, this.month + 1);
            this.selectedDay = null;
        },
        
        selectDay(day) {
            this.selectedDay = day;
        },
        
        getDayStatus(day) {
            const presentDays = [1, 2, 3, 6, 7, 8, 9, 10, 13, 14, 15, 16, 17, 20, 21, 22, 23, 24, 27, 28, 29];
            const absentDays = [5, 12, 19, 26];
            const leaveDays = [11, 18, 25];
            if (presentDays.includes(day)) return 'present';
            if (absentDays.includes(day)) return 'absent';
            if (leaveDays.includes(day)) return 'leave';
            return null;
        },
        
        openLeaveModal() {
            this.leaveRequest = { type: 'OFFSITE', from: '', to: '', reason: '' };
            this.leaveModalOpen = true;
        },
        
        submitLeaveRequest() {
            if (!this.leaveRequest.from || !this.leaveRequest.to) {
                window.appUtils.showToast('Please select both From and To dates', 'error');
                return;
            }
            window.appUtils.showToast(`Leave request submitted successfully!`, 'success');
            this.leaveModalOpen = false;
        }
    }
}
</script>
</body>
</html>