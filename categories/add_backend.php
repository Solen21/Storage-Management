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
    $_SESSION['error'] = "You do not have permission to manage categories.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $width = !empty($_POST['width']) ? trim($_POST['width']) : null;
    $height = !empty($_POST['height']) ? trim($_POST['height']) : null;
    $description = trim($_POST['description']);

    if (empty($name)) {
        $_SESSION['error'] = 'Category name is required.';
    } else {
        try {
            $sql = "INSERT INTO categories (name, width, height, description) VALUES (:name, :width, :height, :description)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':width', $width);
            $stmt->bindParam(':height', $height);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Category added successfully!";
            } else {
                $_SESSION['error'] = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("location: add.php");
    exit;
}

header("location: add.php");
exit;
?>