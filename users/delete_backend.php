<?php
// Start the session
session_start();

// Security check: Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

// Include database connection
require_once '../database/connection.php';

// Security check: Must be an admin
$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin']; // Only admin can delete users

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
        error_log("Database error checking role in users/delete_backend.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

// Redirect if user's role is not allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to delete users.";
    header("location: ../auth/distribute.php");
    exit;
}

// Process form only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid user ID for deletion.';
    } elseif ($id == $_SESSION['id']) {
        // Final backend check to prevent self-deletion
        $_SESSION['error'] = "Operation failed: You cannot delete your own account.";
    } else {
        try {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['message'] = "User deleted successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error during deletion: " . $e->getMessage();
        }
    }
}

// Redirect to the main dashboard or a user list page
header("location: ../auth/distribute.php");
exit;
?>

