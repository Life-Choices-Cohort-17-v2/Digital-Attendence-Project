/**
 * Global utility functions for the SpySee frontend.
 */

// --- Theme Management ---
window.themeManager = {
    initTheme() {
        const isDark = localStorage.getItem('theme') === 'dark' || 
                       (localStorage.getItem('theme') === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.body.classList.toggle('dark-mode', isDark);
        return isDark;
    },
    toggleTheme() {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        return isDark;
    },
    isDark() {
        return document.body.classList.contains('dark-mode');
    }
};

// --- General App Utilities ---
window.appUtils = {
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 500);
        }, 3000);
    }
};