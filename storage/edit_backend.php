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
    $_SESSION['error'] = "You do not have permission to manage storage.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $available_pieces = filter_input(INPUT_POST, 'available_pieces', FILTER_VALIDATE_INT);

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid storage entry ID.';
    } elseif ($available_pieces === false || $available_pieces < 0) {
        $_SESSION['error'] = 'A valid, non-negative quantity is required.';
    } else {
        try {
            $sql = "UPDATE storage SET available_pieces = :available_pieces WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':available_pieces', $available_pieces, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Stock quantity updated successfully!";
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