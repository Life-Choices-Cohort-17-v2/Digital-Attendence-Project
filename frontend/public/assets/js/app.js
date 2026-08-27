/** app.js – Dark mode only */

// Theme Manager – always dark
window.themeManager = {
    _applyTheme() {
        // Always apply dark mode
        document.body.classList.add('dark-mode');
        document.body.style.setProperty('--background', '#202020');
        document.body.style.setProperty('--card-bg', '#2a2a2a');
        document.body.style.setProperty('--border-color', '#444444');
        document.body.style.setProperty('--heading', '#F8F8F8');
        document.body.style.setProperty('--text', '#999999');
    },
    initTheme() {
        // Always set dark theme
        this._applyTheme();
    }
};

// Helpers
window.getInitials = function(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
};

window.formatTime = function(dateString) {
    if (!dateString) return '--:--';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit', hour12: false });
};

window.formatDate = function(dateString) {
    if (!dateString) return '--';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-ZA', { day: '2-digit', month: 'short' });
};

window.formatDateTime = function(dateString) {
    if (!dateString) return '--';
    const date = new Date(dateString);
    return date.toLocaleString('en-ZA');
};

window.getWeekday = function() {
    return new Date().toLocaleDateString('en-ZA', { weekday: 'long' });
};

// App Utilities (Toast)
window.appUtils = {
    showToast(message, type = 'success') {
        const existing = document.querySelector('.toast-message');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.className = `toast-message ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
};

// API Helper
window.api = {
    async get(endpoint) {
        const response = await fetch(endpoint);
        return response.json();
    },
    async post(endpoint, data) {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!result.success && result.message) window.appUtils.showToast(result.message, 'error');
        return result;
    }
};

// QR Utilities
window.qrUtils = {
    getScanType(code) {
        const upper = code.toUpperCase().trim();
        if (upper === 'SIGN_IN' || upper === 'CLOCKIN') return 'sign-in';
        if (upper === 'SIGN_OUT' || upper === 'CLOCKOUT') return 'sign-out';
        return null;
    },
    async recordScan(type, userId, location = 'Office') {
        const endpoint = type === 'sign-in' ? 'api/sign-in.php' : 'api/sign-out.php';
        try {
            const result = await window.api.post(endpoint, { type, user_id: userId, location });
            return result;
        } catch (error) {
            console.error('Error recording scan:', error);
            window.appUtils.showToast('Failed to record. Check connection.', 'error');
            return null;
        }
    }
};