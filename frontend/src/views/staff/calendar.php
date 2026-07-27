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
    <title><?= $title ?? 'Calendar' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>
        [x-cloak] { display: none !important; }
        
        .calendar-container {
            padding: 24px;
            max-width: 100%;
            margin: 0 auto;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            margin-bottom: 8px;
        }

        .calendar-weekdays div {
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: var(--text);
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        @media (max-width: 600px) {
            .calendar-container { padding: 16px; }
            .calendar-weekdays div span.full { display: none; }
            .calendar-weekdays div span.short { display: inline; }
        }

        @media (min-width: 601px) {
            .calendar-weekdays div span.short { display: none; }
        }

        .month-nav {
            transition: 0.2s;
        }
    </style>
</head>
<body>

<div x-data="calendarApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'calendar'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="calendar-container">
                <div class="calendar-header">
                    <button class="month-nav" @click="prevMonth()">←</button>
                    <h2 x-text="monthName + ' ' + year"></h2>
                    <button class="month-nav" @click="nextMonth()">→</button>
                </div>

                <div class="calendar-weekdays">
                    <div><span class="full">Sun</span><span class="short">S</span></div>
                    <div><span class="full">Mon</span><span class="short">M</span></div>
                    <div><span class="full">Tue</span><span class="short">T</span></div>
                    <div><span class="full">Wed</span><span class="short">W</span></div>
                    <div><span class="full">Thu</span><span class="short">T</span></div>
                    <div><span class="full">Fri</span><span class="short">F</span></div>
                    <div><span class="full">Sat</span><span class="short">S</span></div>
                </div>

                <div class="calendar-days">
                    <template x-for="day in calendarDays" :key="day">
                        <div class="calendar-day" :class="{ 'other-month': !day, 'present': day && getDayStatus(day) === 'present', 'absent': day && getDayStatus(day) === 'absent', 'leave': day && getDayStatus(day) === 'leave', 'selected': selectedDay === day }" @click="day && selectDay(day)">
                            <span x-text="day || ''"></span>
                        </div>
                    </template>
                </div>

                <div class="selected-info" x-show="selectedDay">
                    <h3>Selected: <span x-text="selectedDateFormatted"></span></h3>
                    <p x-text="selectedStatus"></p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function calendarApp() {
    return {
        sidebarOpen: false,
        currentDate: new Date(),
        selectedDay: null,
        
        get year() { return this.currentDate.getFullYear(); },
        get month() { return this.currentDate.getMonth(); },
        get monthName() { return this.currentDate.toLocaleString('default', { month: 'long' }); },
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
            if (status === 'present') return '✅ You were present on this day';
            if (status === 'absent') return '❌ No clock-in recorded on this day';
            if (status === 'leave') return '📝 You were on leave this day';
            return '📅 No attendance data for this day';
        },
        
        init() {
            window.themeManager.initTheme();
        },
        
        prevMonth() { this.currentDate = new Date(this.year, this.month - 1); this.selectedDay = null; },
        nextMonth() { this.currentDate = new Date(this.year, this.month + 1); this.selectedDay = null; },
        selectDay(day) { this.selectedDay = day; },
        
        getDayStatus(day) {
            const presentDays = [1, 2, 3, 6, 7, 8, 9, 10, 13, 14, 15, 16, 17, 20, 21, 22, 23, 24, 27, 28, 29];
            const absentDays = [5, 12, 19, 26];
            const leaveDays = [11, 18, 25];
            if (presentDays.includes(day)) return 'present';
            if (absentDays.includes(day)) return 'absent';
            if (leaveDays.includes(day)) return 'leave';
            return null;
        }
    }
}
</script>
</body>
</html>