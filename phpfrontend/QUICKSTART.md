# Clock-It Frontend - Quick Start Guide

## 🚀 Getting Started

This is the new PHP-based frontend for Clock-It. All React/TypeScript code has been converted to PHP with Alpine.js and Bootstrap.

## Prerequisites

- PHP 7.4 or higher
- Apache or Nginx web server
- XAMPP, WAMP, or LAMP stack recommended
- Modern web browser (Chrome, Firefox, Safari, Edge)

## File Locations

```
c:\xampp\htdocs\Clock-It\
├── phpfrontend/
│   ├── public/
│   │   ├── index.php ← Start here
│   │   ├── login.php
│   │   └── assets/
│   │       ├── css/style.css
│   │       └── js/utilities.js
│   ├── src/views/
│   │   ├── admin/
│   │   │   ├── dashboard.php
│   │   │   ├── users.php
│   │   │   ├── attendance.php
│   │   │   ├── qr.php
│   │   │   └── settings.php
│   │   ├── staff/
│   │   │   ├── dashboard.php
│   │   │   ├── scan-qr.php
│   │   │   ├── history.php
│   │   │   ├── profile.php
│   │   │   └── calendar.php
│   │   └── partials/
│   │       ├── header.php
│   │       ├── sidebar.php
│   │       ├── dashboard-grid.php
│   │       └── theme-toggle.php
│   ├── MIGRATION_GUIDE.md
│   └── MIGRATION_SUMMARY.md ← You are here
```

## Running the Application

### Option 1: Using XAMPP

1. Place project in `C:\xampp\htdocs\Clock-It\`
2. Start Apache in XAMPP Control Panel
3. Navigate to: `http://localhost/Clock-It/phpfrontend/public/`

### Option 2: Using PHP Built-in Server

```bash
cd c:\xampp\htdocs\Clock-It\phpfrontend\public
php -S localhost:8000
```

Navigate to: `http://localhost:8000`

### Option 3: Using Apache Virtual Host

Configure vhost in `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName clock-it.local
    DocumentRoot "C:\xampp\htdocs\Clock-It\phpfrontend\public"
    <Directory "C:\xampp\htdocs\Clock-It\phpfrontend\public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add to `hosts` file:
```
127.0.0.1 clock-it.local
```

Navigate to: `http://clock-it.local`

## Page Structure

### Staff Pages

All staff pages include the same layout:

```
┌─────────────────────────────────┐
│ Header (Clock-It + User Name)   │
├──────────┬──────────────────────┤
│          │                      │
│ Sidebar  │  Page Content        │
│ (Nav)    │  (Main area)         │
│          │                      │
└──────────┴──────────────────────┘
```

### Admin Pages

Similar layout with admin-specific sidebar navigation:

```
┌─ Menu ─────────────────────────┐
│ ☰ Admin Dashboard              │
├──────────┬──────────────────────┤
│ Admin    │  Dashboard Stats     │
│ Sidebar  │  Recent Activity     │
│ Menu     │  Management Tools    │
└──────────┴──────────────────────┘
```

## Available Pages

### For Staff Members

| Page | URL | Features |
|------|-----|----------|
| Dashboard | `/staff/dashboard.php` | Clock status, quick actions |
| Scan QR | `/staff/scan-qr.php` | QR scanner, demo buttons |
| History | `/staff/history.php` | Attendance log, filtering, sorting |
| Calendar | `/staff/calendar.php` | Monthly view, stats |
| Profile | `/staff/profile.php` | User info, password change |

### For Administrators

| Page | URL | Features |
|------|-----|----------|
| Dashboard | `/admin/dashboard.php` | Stats, recent activity |
| Users | `/admin/users.php` | User management, search |
| Attendance Log | `/admin/attendance.php` | All attendance records |
| QR Generator | `/admin/qr.php` | Create, manage QR codes |
| Settings | `/admin/settings.php` | Config, integrations |

## Features

### ✅ Implemented

- [x] Responsive design (mobile, tablet, desktop)
- [x] Dark/Light mode toggle (saved to localStorage)
- [x] Staff dashboard with real-time clock
- [x] QR code scanner (camera)
- [x] Manual QR code entry fallback
- [x] Attendance history with filtering
- [x] Monthly calendar view
- [x] User profile management
- [x] Admin dashboard with statistics
- [x] User management interface
- [x] Attendance log viewing
- [x] QR code management
- [x] Settings configuration
- [x] Search and filtering
- [x] Sorting functionality
- [x] Pagination
- [x] Bootstrap styling
- [x] Alpine.js interactivity
- [x] SVG icons throughout

### 🔄 Ready for Backend Integration

- [ ] Authentication API
- [ ] Database connection
- [ ] API endpoints
- [ ] User CRUD operations
- [ ] Attendance record storage
- [ ] QR code generation backend
- [ ] Settings persistence

## Dark Mode

The application supports dark mode:

1. Click the theme toggle button (moon/sun icon)
2. Theme preference is saved to localStorage
3. Persists across sessions
4. System preference detection on first visit

CSS Variables:
- Primary Navy: `#093C5D`
- Secondary Blue: `#3B7597`
- Accent Green: `#9CB07A`
- Light BG: `#F5F5F5`
- Dark BG: `#081a2f`

## QR Code Scanner

The QR scanner on `/staff/scan-qr.php`:

- Uses device camera
- Supports both front and rear cameras
- Accepts: `CLOCK_IN` or `CLOCK_OUT` codes
- Demo buttons for testing without QR code
- Manual entry fallback
- Results saved to localStorage

### For Testing

Use demo buttons on the Scan QR page to test without actual QR codes.

## State Management

Uses Alpine.js with localStorage:

```javascript
// Theme persistence
localStorage.getItem('theme') // 'dark' or 'light'

// Attendance events
localStorage.getItem('attendanceEvents') // JSON array

// User data
localStorage.getItem('user_' + employeeId)
```

## Customization

### Colors

Edit `/public/assets/css/style.css`:

```css
:root {
    --primary-navy: #093C5D;      /* Change this */
    --secondary-blue: #3B7597;    /* Or this */
    --accent-success: #9CB07A;    /* Or this */
}
```

### Fonts

Modify in `style.css`:

```css
body {
    font-family: 'Your Font', sans-serif;
}
```

### Add New Page

1. Create `filename.php` in `staff/` or `admin/`
2. Include partials:
```php
<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>
```
3. Add content in main section
4. Update sidebar navigation links

## Troubleshooting

### QR Scanner Not Working
- Grant camera permissions
- Use HTTPS (required in production)
- Test with demo buttons first
- Check browser console for errors

### Dark Mode Not Persisting
- Verify localStorage is enabled
- Check if cookies/storage is blocked
- Try clearing cache and reload

### Styling Issues
- Refresh page (clear cache)
- Check Bootstrap CSS is loaded
- Verify file paths in `<link>` tags

### Alpine.js Not Responding
- Check CDN connection
- Verify `defer` on script tag
- Open browser console for errors
- Check x-data syntax

### Sidebar Not Showing
- Verify PHP include paths
- Check file exists in `partials/`
- Look for PHP errors in console

## Technology Stack

**Frontend Framework**: Alpine.js 3.x
**CSS Framework**: Bootstrap 5.3
**Backend**: PHP 7.4+
**QR Scanning**: html5-qrcode
**Icons**: Inline SVG
**State**: Alpine.js data stores + localStorage
**Server**: Apache/Nginx with PHP

## Browser Compatibility

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Mobile Safari (iOS 14+)
- ✅ Chrome Mobile

## Next Steps

### Phase 1: Backend Setup
1. Create `/api/` endpoints
2. Set up database schema
3. Implement authentication
4. Create database connection layer

### Phase 2: Integration
1. Connect frontend to API
2. Replace mock data with API calls
3. Implement form submissions
4. Add error handling

### Phase 3: Testing
1. Unit tests
2. Integration tests
3. User acceptance testing
4. Performance testing

### Phase 4: Deployment
1. Configure production server
2. Set up SSL/HTTPS
3. Database migrations
4. Launch

## API Integration Example

Replace mock data in Alpine.js:

**Before:**
```javascript
x-data="{
    users: [
        { id: 1, name: 'John', email: 'john@example.com' },
        ...
    ]
}"
```

**After:**
```javascript
x-data="{
    users: [],
    async loadUsers() {
        const res = await fetch('/api/users');
        this.users = await res.json();
    }
}" 
@init="loadUsers()"
```

## Security Considerations

### Before Production

- [ ] Implement CSRF protection
- [ ] Add input validation
- [ ] Use parameterized SQL queries
- [ ] Hash passwords
- [ ] Implement rate limiting
- [ ] Add CORS headers
- [ ] Use HTTPS only
- [ ] Set secure session cookies
- [ ] Validate file uploads
- [ ] Sanitize user input

## Performance Tips

1. **Lazy load images** with `loading="lazy"`
2. **Minify CSS/JS** in production
3. **Enable gzip compression** on server
4. **Cache API responses** with localStorage
5. **Use CDN** for Bootstrap and Alpine.js
6. **Optimize database queries**
7. **Add pagination** for large lists

## Support & Documentation

- **MIGRATION_GUIDE.md** - Detailed migration documentation
- **MIGRATION_SUMMARY.md** - High-level overview
- **Alpine.js Docs**: https://alpinejs.dev/
- **Bootstrap Docs**: https://getbootstrap.com/docs/5.3/
- **PHP Docs**: https://www.php.net/manual/

## Key Files to Know

| File | Purpose |
|------|---------|
| `public/assets/css/style.css` | All styling, colors, dark mode |
| `public/assets/js/utilities.js` | Helper functions, utilities |
| `src/views/partials/header.php` | Header component |
| `src/views/partials/sidebar.php` | Navigation sidebar |
| `src/views/partials/dashboard-grid.php` | Dashboard layout |
| `src/views/partials/theme-toggle.php` | Dark mode toggle |

## Development Tips

1. Use browser DevTools to inspect Alpine.js data
2. Check localStorage in DevTools Application tab
3. Test mobile view with DevTools device toolbar
4. Use `x-show` for debugging (vs `x-if`)
5. Keep Alpine.js data structures simple

## Common Questions

**Q: How do I add a new feature?**
A: Add Alpine.js method to `x-data`, implement backend API, connect with fetch

**Q: How do I change colors?**
A: Edit CSS variables in `public/assets/css/style.css`

**Q: Can I use jQuery?**
A: Yes, but Alpine.js is preferred for new code

**Q: How do I handle forms?**
A: Use form tag with Alpine.js submit handler

**Q: How do I add authentication?**
A: Implement login.php with backend API, store session/token

---

## Quick Links

- 📖 [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Full technical documentation
- 📊 [MIGRATION_SUMMARY.md](MIGRATION_SUMMARY.md) - What was converted
- 🎨 [Color Scheme Reference](MIGRATION_GUIDE.md#color-scheme)
- 🚀 [Getting Started](#getting-started)

---

**Ready to start?** Navigate to any staff page or admin page to see the application in action!

Start with `/staff/dashboard.php` for the main staff interface.
