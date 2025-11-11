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
        error_log("Database error checking role in edit_backend.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/login.php");
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to edit roles.";
    header("location: ../auth/distribute.php");
    exit;
}

// Check if the form has been submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Validate form data
    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid role ID.';
    } elseif (empty($name)) {
        $_SESSION['error'] = 'Role name is required.';
    } else {
        try {
            $sql = "UPDATE roles SET name = :name, description = :description WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Role updated successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again later.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $_SESSION['error'] = "Error: This role name already exists.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
    // Redirect back to the edit page to show messages
    header("location: edit.php?id=" . $id);
    exit;
}

// If not a POST request, redirect away
header("location: ../auth/distribute.php");
exit;
?>