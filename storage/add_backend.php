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
    $product_id = trim($_POST['product_id']);
    $store_id = trim($_POST['store_id']);
    $available_pieces = filter_input(INPUT_POST, 'available_pieces', FILTER_VALIDATE_INT);

    if (empty($product_id) || empty($store_id) || $available_pieces === false) {
        $_SESSION['error'] = 'Product, Store, and a valid Quantity are required.';
    } else {
        try {
            // Check if this product already exists in this store
            $checkSql = "SELECT id FROM storage WHERE product_id = :product_id AND store_id = :store_id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $checkStmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $_SESSION['error'] = "This product is already tracked in this store. Please edit the existing entry to update the quantity.";
            } else {
                // Insert new storage entry
                $sql = "INSERT INTO storage (product_id, store_id, available_pieces) VALUES (:product_id, :store_id, :available_pieces)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $stmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
                $stmt->bindParam(':available_pieces', $available_pieces, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Product successfully added to storage!";
                } else {
                    $_SESSION['error'] = "Something went wrong. Please try again.";
                }
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