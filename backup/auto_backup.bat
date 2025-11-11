@echo off
REM === Auto Backup Script for MySQL (XAMPP) ===

:: Set MySQL bin path (update if your XAMPP is installed elsewhere)
set "MYSQL_BIN=C:\xampp\mysql\bin"
set PATH=%MYSQL_BIN%;%PATH%

:: Database credentials - set DB_PASS empty if no password
set "DB_USER=root"
set "DB_PASS="        REM <-- leave empty if root has no password
set "DB_NAME=Stor_management"

:: Backup folder path
set "BACKUP_DIR=C:\xampp\htdocs\B2-ceramic\backup"

:: Create folder if not exists
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: File name with timestamp (safe)
for /f "tokens=1-5 delims=/:. " %%a in ("%date% %time%") do (
  set "YY=%%e" & set "MM=%%b" & set "DD=%%c" & set "hh=%%d" & set "mm=%%~4"
)
:: fallback simpler timestamp
set "TS=%date:~10,4%-%date:~4,2%-%date:~7,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%"
set "TS=%TS: =0%"

set "FILE_NAME=%DB_NAME%_%TS%.sql"

echo Creating backup folder and file...
if exist "%BACKUP_DIR%\%FILE_NAME%" del "%BACKUP_DIR%\%FILE_NAME%"

REM Choose command depending on whether DB_PASS is empty
if "%DB_PASS%"=="" (
    mysqldump -u "%DB_USER%" "%DB_NAME%" > "%BACKUP_DIR%\%FILE_NAME%"
) else (
    mysqldump -u "%DB_USER%" -p"%DB_PASS%" "%DB_NAME%" > "%BACKUP_DIR%\%FILE_NAME%"
)

if %ERRORLEVEL%==0 (
    echo Backup completed successfully: "%BACKUP_DIR%\%FILE_NAME%"
) else (
    echo Backup failed. %ERRORLEVEL%
)

pause
