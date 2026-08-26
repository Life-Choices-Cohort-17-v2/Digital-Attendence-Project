@echo off
title SpySee - Attendance System
color 0A

echo ============================================
echo  🚀 SPYSEE - HYBRID SYSTEM
echo  (Database Login + Google Sheets Attendance)
echo ============================================
echo.

:: ============================================================
:: CHECK PREREQUISITES
:: ============================================================

:: Check if PHP exists
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHP not found! Please install PHP or add it to PATH.
    echo.
    echo    Try: C:\xampp\php\php.exe -S localhost:8000
    echo.
    pause
    exit /b 1
)

:: Check if MySQL is running
echo 🔍 Checking MySQL connection...
php -r "try { new PDO('mysql:host=127.0.0.1;dbname=attendance', 'root', ''); echo '✅ MySQL is running!' . PHP_EOL; } catch (Exception $e) { echo '❌ MySQL is NOT running! Start XAMPP MySQL first.' . PHP_EOL; exit(1); }" 2>nul
if %errorlevel% neq 0 (
    echo.
    echo ⚠️  Please start MySQL in XAMPP Control Panel first!
    echo.
    pause
    exit /b 1
)

:: Check if ngrok exists (optional)
where ngrok >nul 2>nul
if %errorlevel% neq 0 (
    echo ⚠️  ngrok not found (phone access will not work)
    set NGROK_AVAILABLE=0
) else (
    set NGROK_AVAILABLE=1
    echo ✅ ngrok found!
)

echo.

:: ============================================================
:: CHOOSE STARTUP MODE
:: ============================================================

echo ============================================
echo  Select Startup Mode:
echo ============================================
echo.
echo  [1] Quick Start (Local only - no ngrok)
echo  [2] Full Start (With ngrok for phone access)
echo  [3] Clean Start (Clear cache + full start)
echo  [4] Stop all servers
echo.
choice /C 1234 /N /M "Enter your choice (1-4): "

if errorlevel 4 goto STOP
if errorlevel 3 goto CLEAN_START
if errorlevel 2 goto FULL_START
if errorlevel 1 goto QUICK_START

:: ============================================================
:: QUICK START (Local only)
:: ============================================================
:QUICK_START
echo.
echo ============================================
echo  📡 QUICK START - Local only
echo ============================================
echo.

:: Warm cache
echo [1/3] Warming cache...
php -r "require_once 'frontend/data/functions.php'; updateCache();" 2>nul
if %errorlevel% equ 0 (
    echo ✅ Cache ready!
) else (
    echo ⚠️  Cache warmup skipped (first load may be slower)
)

:: Start PHP server on port 8000
echo.
echo [2/3] Starting PHP server on port 8000...
start "SpySee Server" cmd /c "C:/xampp/php/php.exe -S 0.0.0.0:8000 -t frontend/public"

:: Wait for server
timeout /t 2 /nobreak >nul

:: Open browser
echo.
echo [3/3] Opening browser...
start http://localhost:8000/

goto SHOW_INFO

:: ============================================================
:: FULL START (With ngrok)
:: ============================================================
:FULL_START
echo.
echo ============================================
echo  📡 FULL START - With ngrok (Phone access)
echo ============================================
echo.

:: Warm cache
echo [1/4] Warming cache...
php -r "require_once 'frontend/data/functions.php'; updateCache();" 2>nul
if %errorlevel% equ 0 (
    echo ✅ Cache ready!
) else (
    echo ⚠️  Cache warmup skipped (first load may be slower)
)

:: Start PHP server
echo.
echo [2/4] Starting PHP server on port 8000...
start "SpySee Server" cmd /c "C:/xampp/php/php.exe -S 0.0.0.0:8000 -t frontend/public"

:: Wait for server
timeout /t 2 /nobreak >nul

:: Start ngrok
if %NGROK_AVAILABLE% equ 1 (
    echo.
    echo [3/4] Starting ngrok...
    start "SpySee ngrok" cmd /c "ngrok http 8000"
    timeout /t 3 /nobreak >nul
) else (
    echo.
    echo [3/4] Skipping ngrok (not available)
)

:: Open browser
echo.
echo [4/4] Opening browser...
start http://localhost:8000/

goto SHOW_INFO

:: ============================================================
:: CLEAN START (Clear cache + full start)
:: ============================================================
:CLEAN_START
echo.
echo ============================================
echo  🧹 CLEAN START - Clearing cache
echo ============================================
echo.

:: Clear cache files
echo [1/5] Clearing cache...
if exist "backend\storage\cache\sheets_cache.json" (
    del /q "backend\storage\cache\sheets_cache.json"
    echo ✅ Sheets cache cleared
)
if exist "backend\storage\cache\credentials_cache.json" (
    del /q "backend\storage\cache\credentials_cache.json"
    echo ✅ Credentials cache cleared
)

:: Clear session files
echo [2/5] Clearing sessions...
if exist "frontend\storage\sessions\*" (
    del /q "frontend\storage\sessions\*" 2>nul
    echo ✅ Sessions cleared
)

:: Warm cache
echo [3/5] Warming cache...
php -r "require_once 'frontend/data/functions.php'; updateCache();" 2>nul
if %errorlevel% equ 0 (
    echo ✅ Cache rebuilt!
) else (
    echo ⚠️  Cache warmup skipped (first load may be slower)
)

:: Start PHP server
echo.
echo [4/5] Starting PHP server on port 8000...
start "SpySee Server" cmd /c "C:/xampp/php/php.exe -S 0.0.0.0:8000 -t frontend/public"

:: Wait for server
timeout /t 2 /nobreak >nul

:: Start ngrok if available
if %NGROK_AVAILABLE% equ 1 (
    echo [5/5] Starting ngrok...
    start "SpySee ngrok" cmd /c "ngrok http 8000"
    timeout /t 3 /nobreak >nul
) else (
    echo [5/5] Skipping ngrok
)

:: Open browser
start http://localhost:8000/

goto SHOW_INFO

:: ============================================================
:: STOP ALL SERVERS
:: ============================================================
:STOP
echo.
echo ============================================
echo  🛑 Stopping all servers...
echo ============================================
echo.

taskkill /f /im php.exe 2>nul
if %errorlevel% equ 0 (
    echo ✅ PHP servers stopped
) else (
    echo ℹ️  No PHP servers running
)

taskkill /f /im ngrok.exe 2>nul
if %errorlevel% equ 0 (
    echo ✅ ngrok stopped
) else (
    echo ℹ️  No ngrok running
)

echo.
echo ✅ All stopped!
echo.
pause
exit /b 0

:: ============================================================
:: SHOW INFO
:: ============================================================
:SHOW_INFO

echo.
echo ============================================
echo  ✅ SPYSEE IS RUNNING!
echo ============================================
echo.
echo  🌐 Local:    http://localhost:8000/
if %NGROK_AVAILABLE% equ 1 (
    echo  📱 Phone:   Check ngrok window for URL
    echo  📊 ngrok:   http://127.0.0.1:4040 (Status)
)
echo.
echo ============================================
echo  📋 Quick Actions:
echo    - Login Staff:  EMP001 / thina@01
echo    - Login Admin:  ADMIN_001 / jose@04
echo    - Scan QR:      http://localhost:8000/scan-qr
echo    - QR Terminal:  http://localhost:8000/admin-dashboard/qr
echo.
echo ============================================
echo.
echo  ⚡ Press any key to STOP all servers
echo  ⚡ Or close this window to keep them running
echo.
pause >nul

:: ============================================================
:: CLEAN EXIT
:: ============================================================
echo.
echo 🛑 Stopping all servers...
taskkill /f /im php.exe 2>nul
taskkill /f /im ngrok.exe 2>nul
echo ✅ All stopped!
echo.
pause