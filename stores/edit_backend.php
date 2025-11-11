<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin', 'manager'];

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
    $_SESSION['error'] = "You do not have permission to manage stores.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid store ID.';
    } elseif (empty($name)) {
        $_SESSION['error'] = 'Store name is required.';
    } else {
        try {
            $sql = "UPDATE stores SET name = :name, location = :location, capacity = :capacity WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Store updated successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("location: edit.php?id=" . $id);
    exit;
}

header("location: ../auth/distribute.php");
exit;
?>