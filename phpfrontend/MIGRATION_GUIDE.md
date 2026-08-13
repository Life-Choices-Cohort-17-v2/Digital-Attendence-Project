# Clock-It Frontend Migration - PHP/Alpine.js/Bootstrap

## Overview

This document outlines the complete migration of the Clock-It frontend from React/TypeScript with Tailwind CSS to PHP with Alpine.js and Bootstrap. The application maintains the same functionality and user experience while using a traditional PHP server-side approach.

## File Structure

```
phpfrontend/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css (Bootstrap customization + color variables)
│   │   └── js/
│   │       └── utilities.js (Alpine.js helpers and utilities)
│   ├── index.php (Main router/entry point)
│   └── login.php (Login page)
├── src/
│   └── views/
│       ├── login.php
│       ├── 404.php
│       ├── admin/
│       │   ├── dashboard.php (Admin overview with stats)
│       │   ├── users.php (User management)
│       │   ├── attendance.php (Attendance log)
│       │   ├── qr.php (QR code management)
│       │   └── settings.php (Admin settings)
│       ├── staff/
│       │   ├── dashboard.php (Staff home/dashboard)
│       │   ├── scan-qr.php (QR scanner)
│       │   ├── history.php (Attendance history)
│       │   ├── profile.php (User profile)
│       │   └── calendar.php (Attendance calendar)
│       ├── layouts/ (Layout templates)
│       └── partials/ (Reusable components)
│           ├── header.php
│           ├── sidebar.php
│           ├── dashboard-grid.php
│           └── theme-toggle.php
```

## Technology Stack

### Frontend
- **PHP** (Server-side rendering)
- **Alpine.js 3.x** (Lightweight JavaScript framework for interactivity)
- **Bootstrap 5.3** (Responsive CSS framework)
- **Custom CSS** (Color scheme, dark mode support)

### Libraries
- **html5-qrcode** (QR code scanning)
- **qrcode.js** (QR code generation)

## Color Scheme

Maintains the original Clock-It branding:

```css
--primary-navy: #093C5D
--secondary-blue: #3B7597
--accent-success: #9CB07A (olive green)
--light-bg: #F5F5F5
--dark-bg: #081a2f
--dark-text: #eff6ff
```

## Key Features by Page

### Admin Pages

#### Dashboard (`admin/dashboard.php`)
- Overview statistics (total employees, present/absent counts)
- Recent activity log
- Quick navigation to other admin panels
- Responsive card layout using Bootstrap grid

#### User Management (`admin/users.php`)
- Search and filter users by name/email
- User role assignment (admin/staff)
- Status management
- Add/edit/delete user operations
- Alpine.js-powered search filtering

#### Attendance Log (`admin/attendance.php`)
- View all attendance records
- Filter by type (clock-in/clock-out)
- Search by employee/location
- Sort by date/time
- Export to CSV (UI ready)
- Paginated table view

#### QR Code Generator (`admin/qr.php`)
- Create new QR codes
- Display active QR codes in grid
- Download/duplicate/revoke QR codes
- QR type and location management
- Expiration tracking

#### Settings (`admin/settings.php`)
- General configuration
- Data retention policies
- Location-based settings
- Google Sheets integration toggle
- Company settings

### Staff Pages

#### Dashboard (`staff/dashboard.php`)
- Current attendance status display
- Digital clock (real-time update)
- Quick action buttons (QR scan)
- Dashboard cards with navigation (Calendar, Leave, Profile)
- Responsive layout

#### Scan QR (`staff/scan-qr.php`)
- Real-time camera QR scanner
- Manual code entry fallback
- Demo buttons for testing
- Success/error messaging
- Support for CLOCK_IN and CLOCK_OUT codes
- Offline capability with local storage sync

#### Attendance History (`staff/history.php`)
- Sortable table columns
- Search and filter functionality
- Type filter (clock-in/clock-out)
- Pagination with dynamic page count
- Badge indicators for scan type
- Date and time formatting

#### Calendar (`staff/calendar.php`)
- Interactive monthly calendar
- Color-coded day status
  - Green: Present
  - Red: Absent
  - Yellow: On Leave
  - Light: Pending
- Legend and statistics sidebar
- Month navigation
- Summary statistics

#### Profile (`staff/profile.php`)
- User information display
- Edit mode for profile updates
- Change password functionality
- Profile picture upload (UI ready)
- Account status and login history
- Role and department information

## Component Architecture

### Shared Partials

#### Header (`partials/header.php`)
```php
<?php include 'partials/header.php'; ?>
```
Displays top navigation bar with branding and user greeting.

#### Sidebar (`partials/sidebar.php`)
```php
<?php include 'partials/sidebar.php'; ?>
```
Main navigation menu with links to all pages. Uses Bootstrap and Alpine.js for responsive sidebar.

#### Dashboard Grid (`partials/dashboard-grid.php`)
```php
<?php include 'partials/dashboard-grid.php'; ?>
```
Staff dashboard with status card, clock, and action cards.

#### Theme Toggle (`partials/theme-toggle.php`)
```php
<?php include 'partials/theme-toggle.php'; ?>
```
Dark/light mode toggle button with smooth transitions.

## Alpine.js Data Management

The application uses Alpine.js for state management without requiring a separate store:

```javascript
// Example data structure
x-data="{
    currentTime: '<?php echo date('h:i A'); ?>',
    isDarkMode: <?php echo isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'true' : 'false'; ?>,
    users: [],
    
    get filteredUsers() {
        return this.users.filter(u => u.name.includes(this.searchQuery));
    }
}"
```

## JavaScript Utilities (`assets/js/utilities.js`)

Provides helper functions for:

- **Theme Management**: `window.themeManager.toggleTheme()`
- **Time Utilities**: `window.timeUtils.getCurrentTime()`
- **QR Code**: `window.qrUtils.recordScan()`, `window.qrUtils.isValidQRCode()`
- **SVG Icons**: `window.icons.dashboard()`, etc.
- **Clock Updates**: `window.startClockUpdate(elementId)`

## CSS Customization (`assets/css/style.css`)

Provides:
- Bootstrap variable overrides
- Custom color scheme
- Dark mode support (via `.dark-mode` class)
- Component-specific styling
- Responsive breakpoints
- Smooth transitions

## Data Persistence

### localStorage
- User theme preference: `localStorage.getItem('theme')`
- Attendance events: `localStorage.getItem('attendanceEvents')`
- Session data: Various user-specific data

### Session
- User authentication: `$_SESSION['user_id']`
- User information: `$_SESSION['user_name']`, `$_SESSION['user_email']`
- Admin access: `$_SESSION['user_role']`

## Development Guidelines

### Adding a New Page

1. Create PHP file in appropriate directory (`admin/` or `staff/`)
2. Include required partials:
   ```php
   <?php include 'partials/sidebar.php'; ?>
   <?php include 'partials/header.php'; ?>
   ```
3. Wrap content in `dashboard-section` class
4. Initialize Alpine.js data with page-specific state
5. Use Bootstrap grid system for layouts

### Styling

Use Bootstrap classes first, then supplement with custom CSS:
```html
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Title</h5>
    </div>
    <div class="card-body">
        Content
    </div>
</div>
```

### Interactive Elements

Use Alpine.js for interactivity:
```html
<button 
    class="btn btn-primary" 
    @click="handleClick()" 
    :disabled="!isValid"
    type="button"
>
    Click Me
</button>
```

## Tailwind to Bootstrap Mapping

| Tailwind | Bootstrap |
|----------|-----------|
| `text-center` | `text-center` |
| `flex gap-4` | `d-flex gap-4` |
| `grid grid-cols-3` | `row` with `col` divs |
| `bg-white` | `bg-white` |
| `shadow-sm` | `shadow-sm` |
| `rounded-lg` | `rounded` |
| `hidden` | `d-none` |
| `flex items-center` | `d-flex align-items-center` |

## Dark Mode Support

Dark mode is implemented with:
1. CSS variables that respond to `.dark-mode` class on `<body>`
2. localStorage persistence of theme preference
3. Alpine.js state management: `window.themeManager.isDark()`
4. Automatic system preference detection

## QR Code Implementation

Replaced React's html5-qrcode usage with CDN version:
```javascript
const scanner = new Html5Qrcode('reader');
scanner.start(
    { facingMode: 'environment' },
    { fps: 10, qrbox: { width: 220, height: 220 } },
    (decodedText) => handleScan(decodedText),
    () => {}
);
```

## Performance Optimizations

1. Minimal JavaScript - only Alpine.js for interactivity
2. CSS bundled in single file
3. Server-side rendering - no build step needed
4. Lazy loading images and assets
5. Bootstrap minified CSS/JS from CDN
6. Custom CSS uses CSS variables for theming

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari 14+, Chrome Mobile)

## Migration Checklist

- [x] Shared components converted to PHP
- [x] Admin pages migrated
- [x] Staff pages migrated
- [x] Tailwind CSS replaced with Bootstrap
- [x] React/TypeScript logic migrated to Alpine.js
- [x] Dark mode functionality preserved
- [x] Responsive design maintained
- [x] QR code scanning implemented
- [x] Time utilities preserved
- [x] Color scheme maintained
- [x] Local storage functionality preserved

## Known Differences from React Version

1. No real-time data sync - refresh needed for updates (can be enhanced with AJAX)
2. No build process - directly serve from server
3. Session-based authentication instead of JWT
4. Server-side form validation recommended
5. No WebSocket support (can be added with Socket.io if needed)

## Next Steps

1. Implement backend API endpoints for:
   - User management
   - Attendance records
   - QR code generation/management
   - Authentication

2. Add PHP database layer:
   - User table integration
   - Attendance logging
   - QR code storage

3. Implement AJAX for:
   - Search results
   - Real-time updates
   - Form submissions

4. Add PHP security:
   - CSRF protection
   - Input validation
   - SQL injection prevention
   - XSS protection

## Support & Troubleshooting

### Alpine.js Not Working
- Ensure CDN script is loaded: `<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>`
- Check browser console for errors

### Bootstrap Not Styled
- Verify Bootstrap CSS is loaded before custom CSS
- Check file paths are correct

### QR Scanner Issues
- Check camera permissions are granted
- Ensure HTTPS in production
- Fall back to manual code entry

### Dark Mode Not Persisting
- Check localStorage is enabled
- Verify theme cookie is being set
- Check CSS variables are applied

## Resources

- Bootstrap 5 Docs: https://getbootstrap.com/docs/5.3/
- Alpine.js Docs: https://alpinejs.dev/
- html5-qrcode: https://github.com/mebjas/html5-qrcode
- PHP Official Docs: https://www.php.net/

---

**Migration Completed**: May 29, 2026

All React components successfully converted to PHP with Alpine.js and Bootstrap. Application maintains original functionality and design while using server-side rendering approach.
