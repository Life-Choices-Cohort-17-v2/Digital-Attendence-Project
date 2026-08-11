@echo off
echo Starting SpySee...

:: Start PHP server
start "PHP Server" cmd /c "C:/xampp/php/php.exe -S localhost:8000 -t frontend/public"

:: Wait 2 seconds
timeout /t 2 /nobreak >nul

:: Start ngrok
start "ngrok" cmd /c "ngrok http 8000"

:: Open browser
start http://localhost:8000/

echo.
echo ✅ SpySee is running!
echo 📱 PC: http://localhost:8000/
echo 📱 Phone: Check ngrok window
echo.
echo Press any key to stop all servers...
pause >nul

:: Kill everything on exit
taskkill /f /im php.exe 2>nul
taskkill /f /im ngrok.exe 2>nul