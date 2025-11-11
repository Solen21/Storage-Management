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
$allowed_roles = ['admin']; // Only admin can add users

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
        error_log("Database error checking role in users/add_backend.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

// Redirect if user's role is not allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to add new users.";
    header("location: ../auth/distribute.php");
    exit;
}

// Process form only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and trim form data
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $user_role_id = trim($_POST['role_id']);
    $phone = trim($_POST['phone']);

    // Basic validation
    if (empty($full_name) || empty($username) || empty($password) || empty($user_role_id)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } else {
        try {
            // Hash the password for security
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (full_name, username, password_hash, role_id, phone) VALUES (:full_name, :username, :password_hash, :role_id, :phone)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':full_name', $full_name, PDO::PARAM_STR);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $user_role_id, PDO::PARAM_INT);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $_SESSION['message'] = "User added successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // 1062 is the MySQL error code for a duplicate entry
                $_SESSION['error'] = "Error: This username already exists.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
    // Redirect back to the add form to display messages
    header("location: add.php");
    exit;
}

// Redirect if not a POST request
header("location: add.php");
exit;
?>