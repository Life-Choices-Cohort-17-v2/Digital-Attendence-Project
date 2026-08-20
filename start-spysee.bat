@echo off
title SpySee - Attendance System
color 0A

echo ============================================
echo  🚀 SPYSEE - OPTIMIZED START (FIXED)
echo ============================================
echo.

:: Check if PHP exists
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHP not found! Please install PHP or add it to PATH.
    pause
    exit /b 1
)

:: Check if ngrok exists (optional)
where ngrok >nul 2>nul
if %errorlevel% neq 0 (
    echo ⚠️  ngrok not found (phone access may not work)
    set NGROK_AVAILABLE=0
) else (
    set NGROK_AVAILABLE=1
)

:: ============================================================
:: STEP 1: WARM THE CACHE (First load will be fast!)
:: ============================================================
echo [1/4] Warming cache...
php -r "require_once 'frontend/data/functions.php'; updateCache();" 2>nul
if %errorlevel% equ 0 (
    echo ✅ Cache warmed!
) else (
    echo ⚠️  Cache warmup skipped (first load may be slower)
)

:: ============================================================
:: STEP 2: START PHP SERVER WITH ROUTER (FIXED - Binds to ALL interfaces!)
:: ============================================================
echo.
echo [2/4] Starting PHP server on port 8000...

:: Check if router.php exists
if exist "frontend\public\router.php" (
    echo 📡 Using optimized router with gzip compression
    start "SpySee PHP" cmd /c "C:/xampp/php/php.exe -S 0.0.0.0:8000 frontend/public/router.php"
) else (
    echo 📡 Using standard server
    start "SpySee PHP" cmd /c "C:/xampp/php/php.exe -S 0.0.0.0:8000 -t frontend/public"
)

:: Wait for server to start
timeout /t 2 /nobreak >nul

:: ============================================================
:: STEP 3: START NGROK
:: ============================================================
echo.
echo [3/4] Starting ngrok...
if %NGROK_AVAILABLE% equ 1 (
    start "SpySee ngrok" cmd /c "ngrok http 8000"
    timeout /t 2 /nobreak >nul
) else (
    echo ⚠️  ngrok skipped (not found in PATH)
)

:: ============================================================
:: STEP 4: OPEN BROWSER
:: ============================================================
echo.
echo [4/4] Opening browser...
start http://localhost:8000/

echo.
echo ============================================
echo  ✅ SPYSEE IS RUNNING!
echo ============================================
echo  📱 PC:    http://localhost:8000/
if %NGROK_AVAILABLE% equ 1 (
    echo  📱 Phone: Check ngrok window for URL
) else (
    echo  📱 Phone: Use ngrok manually if needed
)
echo ============================================
echo.
echo  ⚡ Press any key to STOP all servers
echo  ⚡ Or close this window to keep them running
echo.
pause >nul

:: ============================================================
:: CLEAN EXIT - Kill all servers
:: ============================================================
echo.
echo 🛑 Stopping all servers...
taskkill /f /im php.exe 2>nul
taskkill /f /im ngrok.exe 2>nul
echo ✅ All stopped!
echo.
pause