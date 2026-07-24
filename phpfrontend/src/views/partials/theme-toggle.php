<?php
/**
 * Theme Toggle Component
 * Dark/Light mode toggle switch
 */
?>
<div class="theme-toggle-container" x-data="{ isDark: <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'true' : 'false'; ?> }">
  <button 
    class="theme-toggle-btn" 
    @click="isDark = !isDark; window.themeManager.toggleTheme()"
    :style="{ color: isDark ? '#f8fafc' : '#0f172a' }"
    aria-label="Toggle theme"
    type="button">
  </button>
  
  <div class="theme-toggle-icon" :style="{ color: isDark ? '#f8fafc' : '#0f172a' }">
    <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M21.64 13a1 1 0 0 0-1.05-.14 8 8 0 1 1 .12-11.85 1 1 0 1 0 1.08-1.63A9.99 9.99 0 0 0 12 2h-.5A9.5 9.5 0 0 0 2 11.5a9.52 9.52 0 0 0 7 9.41 1 1 0 0 0 1.15-.66A1 1 0 0 0 21.64 13z"/>
    </svg>
    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
      <path d="M12 6a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v2a1 1 0 0 0 1 1zm9-2h-2a1 1 0 0 0 0 2h2a1 1 0 0 0 0-2zM6 12a6 6 0 1 0 6-6 6 6 0 0 0-6 6zm-4 0a1 1 0 0 0-1-1H1a1 1 0 0 0 0 2h2a1 1 0 0 0 1-1zm.22-7a1 1 0 0 0-1.39 1.47l1.44 1.39a1 1 0 0 0 .73.25 1 1 0 0 0 .72-.31 1 1 0 0 0 0-1.41zM17 8.14a1 1 0 0 0 .69-.28l1.44-1.39A1 1 0 0 0 17.78 5a1 1 0 0 0-.72.31 1 1 0 0 0 0 1.41zM12 19a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1zm5.73-1.73a1 1 0 0 0-1.39 1.41l1.44 1.39a1 1 0 0 0 .72.31 1 1 0 0 0 .67-.25 1 1 0 0 0 0-1.41zM6.27 17.27a1 1 0 0 0-1.41 0 1 1 0 0 0 0 1.41l1.44 1.39a1 1 0 0 0 .72.31 1 1 0 0 0 .67-.25 1 1 0 0 0 0-1.41zM19 12a1 1 0 0 0-1-1h-2a1 1 0 0 0 0 2h2a1 1 0 0 0 1-1z"/>
    </svg>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
