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
    $_SESSION['error'] = "You do not have permission to manage products.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid product ID for deletion.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['message'] = "Product deleted successfully!";
        } catch (PDOException $e) {
            // Check for foreign key constraint violation
            if ($e->errorInfo[1] == 1451) {
                $_SESSION['error'] = "Cannot delete this product because it is currently in use (e.g., in storage, imports, or exports).";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
}

header("location: ../auth/distribute.php");
exit;
?>