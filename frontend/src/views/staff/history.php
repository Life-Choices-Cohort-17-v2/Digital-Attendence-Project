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
    <title><?= $title ?? 'Attendance History' ?></title>
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= asset_url('js/app.js') ?>"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body>

<div x-data="historyApp()" x-init="init()" @keydown.escape="sidebarOpen = false" x-cloak>
    <div class="app-layout">
        <?php $activePage = 'history'; include __DIR__ . '/staff-sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (file_exists(__DIR__ . '/../partials/top-nav.php')) include __DIR__ . '/../partials/top-nav.php'; ?>
            
            <div class="page-content">
                <h1>Attendance History</h1>
                <p>Your past sign-in and sign-out activity.</p>

                <div class="history-filters">
                    <input type="text" x-model="searchQuery" placeholder="Search by date...">
                    <select x-model="filterType">
                        <option value="all">All Types</option>
                        <option value="sign-in">Sign In</option>
                        <option value="sign-out">Sign Out</option>
                    </select>
                </div>

                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr><th>DATE</th><th>Sign In</th><th>Sign Out</th><th>TOTAL HOURS</th><th>SYNC</th></tr>
                        </thead>
                        <tbody>
                            <template x-for="day in groupedRecords" :key="day.date">
                                <tr>
                                    <td x-text="day.date"></td>
                                    <td x-text="day.clockIn || '--:--'"></td>
                                    <td x-text="day.clockOut || '--:--'"></td>
                                    <td x-text="day.totalHours || '--'"></td>
                                    <td><span class="sync-badge synced">Synced</span></td>
                                </tr>
                            </template>
                            <tr x-show="groupedRecords.length === 0">
                                <td colspan="5" class="empty-row">No records found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function historyApp() {
    return {
        sidebarOpen: false,
        user: { id: '', name: '', email: '' },
        records: [],
        searchQuery: '',
        filterType: 'all',
        
        get filteredRecords() {
            let filtered = this.records;
            if (this.filterType !== 'all') filtered = filtered.filter(r => r.type === this.filterType);
            if (this.searchQuery) filtered = filtered.filter(r => r.date.includes(this.searchQuery));
            return filtered;
        },
        
        get groupedRecords() {
            const grouped = {};
            this.filteredRecords.forEach(record => {
                if (!grouped[record.date]) grouped[record.date] = { date: record.date, clockIn: null, clockOut: null };
                if (record.type === 'sign-in') grouped[record.date].clockIn = record.time;
                else grouped[record.date].clockOut = record.time;
            });
            const result = Object.values(grouped);
            result.forEach(day => {
                if (day.clockIn && day.clockOut) {
                    const inTime = new Date(`2000/01/01 ${day.clockIn}`);
                    const outTime = new Date(`2000/01/01 ${day.clockOut}`);
                    const diff = (outTime - inTime) / (1000 * 60 * 60);
                    day.totalHours = diff.toFixed(1);
                }
            });
            return result;
        },
        
        async init() {
            window.themeManager.initTheme();
            const userData = <?php echo json_encode($user ?? ['id' => 'staff-001', 'name' => 'Staff']); ?>;
            this.user = userData;
            await this.loadHistory();
        },
        
        async loadHistory() {
            const response = await fetch('api/user-history.php?user_id=' + this.user.id);
            const data = await response.json();
            const history = data.data || [];
            this.records = history.map(r => ({
                id: r.id,
                date: r.date,
                time: new Date(r.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                type: r.type,
                location: r.location
            }));
        }
    }
}
</script>
</body>
</html>