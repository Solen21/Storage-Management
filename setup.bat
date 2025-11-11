@echo off
setlocal

REM ===== Project base path =====
set BASE_PATH=C:\xampp\htdocs\Store-management

echo Creating PHP project structure in %BASE_PATH%...
echo.

REM --- Main folders ---
mkdir "%BASE_PATH%\public"
mkdir "%BASE_PATH%\public\assets"
mkdir "%BASE_PATH%\public\assets\css"
mkdir "%BASE_PATH%\public\assets\js"
mkdir "%BASE_PATH%\public\assets\images"
mkdir "%BASE_PATH%\public\assets\uploads"

mkdir "%BASE_PATH%\app"
mkdir "%BASE_PATH%\app\Controllers"
mkdir "%BASE_PATH%\app\Models"
mkdir "%BASE_PATH%\app\Views"
mkdir "%BASE_PATH%\app\Core"
mkdir "%BASE_PATH%\app\Helpers"

mkdir "%BASE_PATH%\config"
mkdir "%BASE_PATH%\storage"
mkdir "%BASE_PATH%\storage\logs"
mkdir "%BASE_PATH%\storage\cache"
mkdir "%BASE_PATH%\storage\uploads"

mkdir "%BASE_PATH%\vendor"
mkdir "%BASE_PATH%\tests"

REM --- Sample public files ---
echo ^<?php echo "Welcome to Store Management System"; ?^> > "%BASE_PATH%\public\index.php"
echo ^<?php require '../app/Controllers/AuthController.php'; ?^> > "%BASE_PATH%\public\login.php"
echo ^<?php require '../app/Controllers/DashboardController.php'; ?^> > "%BASE_PATH%\public\dashboard.php"

REM --- Sample Controllers ---
echo ^<?php class AuthController { public function login() { echo 'Login page'; } } ?^> > "%BASE_PATH%\app\Controllers\AuthController.php"
echo ^<?php class DashboardController { public function index() { echo 'Dashboard page'; } } ?^> > "%BASE_PATH%\app\Controllers\DashboardController.php"

REM --- Sample Models ---
echo ^<?php class User { public $table = 'users'; } ?^> > "%BASE_PATH%\app\Models\User.php"
echo ^<?php class Role { public $table = 'roles'; } ?^> > "%BASE_PATH%\app\Models\Role.php"

REM --- Sample Views ---
mkdir "%BASE_PATH%\app\Views\auth"
mkdir "%BASE_PATH%\app\Views\dashboard"
echo ^<h1>Login Page</h1>^> > "%BASE_PATH%\app\Views\auth\login.php"
echo ^<h1>Dashboard</h1>^> > "%BASE_PATH%\app\Views\dashboard\index.php"

REM --- Core files ---
echo ^<?php // App bootstrap file ?^> > "%BASE_PATH%\app\Core\App.php"
echo ^<?php // Base controller ?^> > "%BASE_PATH%\app\Core\Controller.php"
echo ^<?php // Base model ?^> > "%BASE_PATH%\app\Core\Model.php"
echo ^<?php
\$host = 'localhost';
\$db   = 'Stor_management';
\$user = 'root';
\$pass = '';
\$charset = 'utf8mb4';

\$dsn = "mysql:host=\$host;dbname=\$db;charset=\$charset";
\$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    \$pdo = new PDO(\$dsn, \$user, \$pass, \$options);
} catch (PDOException \$e) {
    die("Database connection failed: " . \$e->getMessage());
}
?^> > "%BASE_PATH%\config\database.php"

REM --- Helpers ---
echo ^<?php // Helper functions ?^> > "%BASE_PATH%\app\Helpers\functions.php"

REM --- Environment file ---
echo DB_HOST=localhost > "%BASE_PATH%\.env"
echo DB_NAME=Stor_management >> "%BASE_PATH%\.env"
echo DB_USER=root >> "%BASE_PATH%\.env"
echo DB_PASS= >> "%BASE_PATH%\.env"

REM --- .gitignore ---
echo vendor/ > "%BASE_PATH%\.gitignore"
echo storage/cache/ >> "%BASE_PATH%\.gitignore"
echo storage/logs/ >> "%BASE_PATH%\.gitignore"
echo .env >> "%BASE_PATH%\.gitignore"

REM --- Composer file ---
echo {^
"require": {^
"php": ">=8.0"^
}^
} > "%BASE_PATH%\composer.json"

echo # Store Management System > "%BASE_PATH%\README.md"

echo.
echo Folder structure successfully created!
pause
endlocal
