<?php
/**
 * Header Component
 * Main page header with branding and welcome message
 */
?>
<header class="app-header">
  <h2>SpySee</h2>
  <div class="welcome-text">
    Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
  </div>
</header>
