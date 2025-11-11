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
    $factory_code = trim($_POST['factory_code']);
    $category_id = trim($_POST['category_id']);
    $description = trim($_POST['description']);

    if (empty($factory_code) || empty($category_id)) {
        $_SESSION['error'] = 'Factory Code and Category are required.';
    } else {
        try {
            $sql = "INSERT INTO products (factory_code, category_id, description) VALUES (:factory_code, :category_id, :description)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':factory_code', $factory_code, PDO::PARAM_STR);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Product added successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Handle unique constraint violation for factory_code
                $_SESSION['error'] = "Error: A product with this Factory Code already exists.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
    }
    header("location: add.php");
    exit;
}

header("location: add.php");
exit;
?>