@echo off
:: ===== FIX MYSQLDUMP NOT RECOGNIZED ERROR =====
:: This file adds MySQL (from XAMPP) to your Windows PATH permanently

echo Fixing MySQL path for XAMPP...

:: Change this path if your XAMPP is installed in another drive
set "MYSQL_PATH=C:\xampp\mysql\bin"

:: Check if the path exists
if not exist "%MYSQL_PATH%" (
    echo MySQL path not found! Check your XAMPP folder.
    pause
    exit /b
)

:: Add MySQL path to system PATH permanently
setx PATH "%PATH%;%MYSQL_PATH%" /M

echo.
echo ✅ MySQL path has been added successfully.
echo Close this window and reopen Command Prompt or PowerShell.
echo Then try running:
echo   mysqldump --version
echo to confirm it’s working.
pause
