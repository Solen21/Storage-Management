<?php
session_start();

// Security check: Must be logged in to perform this action.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: login.php");
    exit;
}

require_once '../database/connection.php';

// Security check: Only an admin can impersonate.
$current_user_role_id = $_SESSION["role_id"] ?? null;
$is_admin = false;
if ($current_user_role_id) {
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = :role_id");
    $stmt->execute([':role_id' => $current_user_role_id]);
    $role = $stmt->fetch();
    if ($role && strtolower($role['name']) === 'admin') {
        $is_admin = true;
    }
}

if (!$is_admin) {
    $_SESSION['error'] = "You do not have permission to perform this action.";
    header("location: distribute.php");
    exit;
}

// Get the target user ID from the URL
$target_user_id = $_GET['id'] ?? null;

if (!$target_user_id || !is_numeric($target_user_id)) {
    $_SESSION['error'] = "Invalid user ID specified for impersonation.";
    header("location: ../view/users.php");
    exit;
}

// Prevent admin from impersonating themselves
if ($target_user_id == $_SESSION['id']) {
    $_SESSION['error'] = "You cannot impersonate yourself.";
    header("location: ../view/users.php");
    exit;
}

// Fetch the target user's details
try {
    $stmt = $pdo->prepare("SELECT id, username, role_id FROM users WHERE id = :id");
    $stmt->execute([':id' => $target_user_id]);
    $target_user = $stmt->fetch();

    if (!$target_user) {
        $_SESSION['error'] = "Target user not found.";
        header("location: ../view/users.php");
        exit;
    }

    // --- The Impersonation Logic ---

    // 1. Save the original admin's session details
    $_SESSION['original_admin_session'] = [
        'id' => $_SESSION['id'],
        'username' => $_SESSION['username'],
        'role_id' => $_SESSION['role_id']
    ];

    // 2. Switch the current session to the target user's details
    $_SESSION['id'] = $target_user['id'];
    $_SESSION['username'] = $target_user['username'];
    $_SESSION['role_id'] = $target_user['role_id'];

    // 3. Redirect to the target user's dashboard
    header("location: distribute.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error during impersonation: " . $e->getMessage();
    header("location: ../view/users.php");
    exit;
}
?>