<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in, otherwise redirect to the login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include the database connection
require_once '../database/connection.php';

$role_name = '';
$role_id = $_SESSION["role_id"] ?? null;

if ($role_id) {
    try {
        // Prepare a select statement to get the role name from the roles table
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $role = $stmt->fetch();
            $role_name = strtolower($role['name']); // Use lowercase for case-insensitive comparison
        }
    } catch (PDOException $e) {
        // Handle potential database errors
        die("ERROR: Could not able to execute $sql. " . $e->getMessage());
    }
}

// Redirect based on the role name
switch ($role_name) {
    case 'admin':
        header("location: ../public/admin.php");
        break;
    case 'manager':
        header("location: ../public/manager.php");
        break;
    case 'worker':
        header("location: ../public/worker.php");
        break;
    default:
        header("location: ../index.php"); // A generic dashboard
        break;
}
exit;
?>