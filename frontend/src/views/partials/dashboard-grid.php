<?php
/**
 * Staff Dashboard Grid Component
 * Main dashboard display with status, clock, and quick action cards
 */

// Get current status from localStorage or session
$status = $_SESSION['attendance_status'] ?? 'Sign Out';
$location = $_SESSION['attendance_location'] ?? 'OFFSITE';
?>

<section class="dashboard-section" x-data="{ currentTime: '<?php echo date('h:i A'); ?>' }" @init="setInterval(() => { currentTime = new Intl.DateTimeFormat('en-US', { hour: '2-digit', minute: '2-digit', hour12: true }).format(new Date()); }, 1000)">
  
  <!-- Status Card -->
  <div class="status-card">
    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem;">
      <!-- Left Side -->
      <div style="flex: 1;">
        <p class="status-label">Current Status</p>
        <h1 class="status-title"><?php echo htmlspecialchars($status); ?></h1>
        
        <div class="status-badge">
          You are currently <?php echo htmlspecialchars($location); ?>
        </div>

        <p class="status-description">
          No actions yet today.
        </p>
      </div>

      <!-- Right Side - Clock -->
      <div style="margin-top: 1.5rem;">
        <div class="clock-display" x-text="currentTime"></div>
      </div>
    </div>

    <!-- QR Scan Button -->
    <button class="qr-button" @click="window.location.href='<?= route_url('/scan-qr') ?>'" type="button">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="22" height="22">
        <path d="M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm13-2h1v4h-1v-4zm-4 4h4v1h-4v-1zm1-3h1v2h-1v-2z"/>
      </svg>
      Scan QR Code to Sign In
    </button>

    <p class="qr-hint">
      Scan the QR at your site. Seamless tracking, smarter attendance.
    </p>
  </div>

  <!-- Bottom Grid Cards -->
  <div class="dashboard-grid">
    <div class="dashboard-card">
      <div class="dashboard-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
        </svg>
      </div>
      <h3>Calendar</h3>
      <p>See your monthly attendance.</p>
    </div>

    <div class="dashboard-card">
      <div class="dashboard-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-8-6zm4 18H6V4h7v5h5v11z"/>
        </svg>
      </div>
      <h3>Leave Requests</h3>
      <p>Submit and track applications.</p>
    </div>

    <div class="dashboard-card">
      <div class="dashboard-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
        </svg>
      </div>
      <h3>Profile</h3>
      <p>Manage your personal details.</p>
    </div>
  </div>
</section>
