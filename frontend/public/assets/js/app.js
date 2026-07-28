/**app.js */

// Theme Manager
window.themeManager = {
    _applyTheme(isDark) {
        if (isDark) {
            document.body.classList.add('dark-mode');
            document.body.style.setProperty('--background', '#0f172a');
            document.body.style.setProperty('--card-bg', '#1e293b');
            document.body.style.setProperty('--border-color', '#334155');
            document.body.style.setProperty('--heading', '#f1f5f9');
            document.body.style.setProperty('--text', '#94a3b8');
        } else {
            document.body.classList.remove('dark-mode');
            document.body.style.setProperty('--background', '#E5E7EB');
            document.body.style.setProperty('--card-bg', '#FFFFFF');
            document.body.style.setProperty('--border-color', '#D1D5DB');
            document.body.style.setProperty('--heading', '#093C5D');
            document.body.style.setProperty('--text', '#5C6B7A');
        }
    },

    initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        this._applyTheme(savedTheme === 'dark');
    },
    
    toggleTheme() {
        const isDark = document.body.classList.toggle('dark-mode');
        const theme = isDark ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        this._applyTheme(isDark);
        return isDark;
    },

    isDark() {
        return document.body.classList.contains('dark-mode');
    }
};

// Helper functions
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

// App Utilities (including global toast)
window.appUtils = {
    showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast-message');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-message ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
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

// QR Utils
window.qrUtils = {
    getScanType(code) {
        const upperCode = code.toUpperCase().trim();
        if (upperCode === 'sign_in' || upperCode === 'CLOCKIN') return 'sign-in';
        if (upperCode === 'sign_out' || upperCode === 'CLOCKOUT') return 'sign-out';
        return null;
    },
    
    async recordScan(type, userId, location = 'Office') {
        const endpoint = type === 'sign-in' ? 'api/sign-in.php' : 'api/sign-out.php';
        try {
            const result = await window.api.post(endpoint, { type: type, user_id: userId, location: location });
            // window.api.post now handles showing error toasts
            return result;
        } catch (error) {
            console.error('Error recording scan:', error);
            window.appUtils.showToast('Failed to record. Please check your connection.', 'error');
            return null;
        }
    }
};