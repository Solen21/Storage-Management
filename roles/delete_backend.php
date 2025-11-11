<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

// Include the database connection file
require_once '../database/connection.php';

$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin', 'manager']; // Define allowed roles

$user_role_name = '';
if ($role_id) {
    try {
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $role = $stmt->fetch();
            $user_role_name = strtolower($role['name']);
        }
    } catch (PDOException $e) {
        error_log("Database error checking role in delete_backend.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/login.php");
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to delete roles.";
    header("location: ../auth/distribute.php");
    exit;
}

// Check if the form has been submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid role ID for deletion.';
    } else {
        try {
            $sql = "DELETE FROM roles WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['message'] = "Role deleted successfully!";
        } catch (PDOException $e) {
            // Check for foreign key constraint violation (MySQL error code 1451)
            if ($e->errorInfo[1] == 1451) {
                $_SESSION['error'] = "Cannot delete this role because it is currently assigned to one or more users.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Redirect to the main dashboard or a role list page
header("location: ../auth/distribute.php");
exit;
?>