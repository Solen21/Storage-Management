<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
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
        error_log("Database error checking role in add_backend.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred. Please try again.";
        header("location: ../auth/login.php"); // Redirect to login or an error page
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to add roles.";
    header("location: ../auth/distribute.php"); // Redirect to their dashboard
    exit;
}


// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    // Validate form data
    if (empty($name)) {
        $_SESSION['error'] = 'Role name is required.';
    } else {
        try {
            // Prepare an insert statement
            $sql = "INSERT INTO roles (name, description) VALUES (:name, :description)";
            $stmt = $pdo->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            // Execute the prepared statement
            if ($stmt->execute()) {
                $_SESSION['message'] = "Role added successfully!";
                header("location: add.php"); // Redirect back to the add form or a list page
                exit;
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again later.";
            }

            // Close statement
            unset($stmt);
        } catch (PDOException $e) {
            // Check for duplicate entry
            if ($e->errorInfo[1] == 1062) {
                $_SESSION['error'] = "Error: This role name already exists.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
        // Close connection
    unset($pdo);
    header("location: add.php"); // Redirect back to the add form to display error
    exit;
}

// If not a POST request, just exit (or redirect if needed)
exit;
?>