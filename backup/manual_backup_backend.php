<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin'];

$user_role_name = '';
if ($role_id) {
    try {
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $user_role_name = strtolower($stmt->fetchColumn());
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to perform this action.";
    header("location: ../auth/distribute.php");
    exit;
}

// Database credentials are available from connection.php: $host, $db, $user, $pass
$backup_dir = __DIR__;
$backup_file = $backup_dir . DIRECTORY_SEPARATOR . 'backup-' . date('Y-m-d_H-i-s') . '.sql';

// Construct the mysqldump command
// Note: Provide the password directly can be a security risk. A more secure method is a .my.cnf file.
// For XAMPP on Windows, this is generally acceptable for local development.

// Define the full path to mysqldump.exe (adjust if your XAMPP is installed elsewhere)
$mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe';

$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s > %s',
    $mysqldump_path,
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($db),
    escapeshellarg($backup_file)
);

@exec($command, $output, $return_var);

if ($return_var === 0 && file_exists($backup_file)) {
    $_SESSION['message'] = "Manual backup created successfully: " . basename($backup_file);
} else {
    $_SESSION['error'] = "Backup failed. Please check server permissions and ensure 'mysqldump' is available in the system's PATH.";
}

header("Location: backup.php");
exit;
?>