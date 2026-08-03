# Google Sheets + QR Attendance Module

Self-contained prototype for QR-based clock-in/out with Google Sheets as the
data store. This is Dominic's working build for the `dominic/gsheets/qr`
branch — dropped in here as-is (no logic changes) so it lives inside the
main `Clock-It` repo and can be reviewed / wired up alongside the rest of
the team's work.

## Files

| File              | Purpose                                                                 |
|--------------------|--------------------------------------------------------------------------|
| `config.php`       | Shared config + helpers: Apps Script URL, session helpers, cache read/write, `sendToGoogleSheets()`, `fetchSheetsData()` |
| `login.php`        | Staff/Admin login screen — checks credentials against the Google Sheet via `?action=getCredentials` |
| `admin.php`        | Admin dashboard — displays the rotating QR code staff scan to clock in/out, plus a live staff status list |
| `scan.php`         | Handles a QR scan: validates token expiry, flips staff status in the local cache instantly, then pushes the update to Google Sheets in the background |
| `index.php`        | Staff-facing app shell (main entry point once logged in) |
| `test_sheet.php`   | Diagnostic page — tests the Apps Script connection, a test write, and the local cache |

## Running it standalone

```bash
cd google-sheets-qr-attendance
php -S localhost:8000
```

Then open `http://localhost:8000/login.php`.

If you're testing QR scans on a phone (not `localhost`), the QR code needs
a publicly reachable URL — `admin.php` currently falls back to a hardcoded
ngrok URL for local dev. Replace it with your own ngrok URL, or update the
logic once this is deployed somewhere with a real domain.

## Configuration

Update `APP_SCRIPT_URL` in `config.php` with your deployed Google Apps
Script Web App URL. No other credentials are hardcoded — everything
(staff PINs, admin passwords, attendance rows) lives in the Google Sheet
itself and is read/written through that Apps Script endpoint.

The module writes a local cache file (`.sheets_cache.json`) next to these
files so clock-in/out feels instant instead of waiting on a round trip to
Google Sheets on every scan. It's git-ignored (see root `.gitignore`).

## How this relates to `backend/`

`backend/` is the team's MVC-style PHP application (namespaced classes,
PDO/MySQL as the source of truth, Google Sheets used only for reporting —
see `backend/README.md`). This module currently works differently: it uses
the Google Sheet itself as the primary data store and PHP sessions for
auth, with no MySQL involved.

The natural long-term home for this logic once the two get merged:

- `sendToGoogleSheets()` / `fetchSheetsData()` in `config.php` →
  `backend/src/services/GoogleSheetsService.php` (currently an empty stub)
- Google Apps Script URL / sheet config →
  `backend/src/config/GoogleSheets.php` (currently an empty stub)
- Login logic in `login.php` → `backend/src/controllers/AuthController.php`
  (currently an empty stub)
- Staff status dashboard in `admin.php` →
  `backend/src/controllers/DashboardController.php` (currently an empty
  stub)

That merge means deciding whether MySQL becomes the source of truth (per
the documented backend architecture) with Sheets demoted to a reporting
sink, or whether Sheets stays authoritative for now. That's a team/architecture
call, so it wasn't made here — this module is kept working and untouched
in the meantime.
