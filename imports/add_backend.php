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
    $_SESSION['error'] = "You do not have permission to manage imports.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = trim($_POST['product_id']);
    $category_id = trim($_POST['category_id']);
    $store_id = trim($_POST['store_id']);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $supplier_name = trim($_POST['supplier_name']);
    $description = trim($_POST['description']);

    if (empty($product_id) || empty($category_id) || empty($store_id) || !$quantity || $quantity <= 0) {
        $_SESSION['error'] = 'Product, Category, Store, and a valid positive Quantity are required.';
        header("location: add.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert the record into the imports table
        $sql = "INSERT INTO imports (product_id, category_id, store_id, quantity, supplier_name, description) VALUES (:product_id, :category_id, :store_id, :quantity, :supplier_name, :description)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':product_id' => $product_id, ':category_id' => $category_id, ':store_id' => $store_id, ':quantity' => $quantity, ':supplier_name' => $supplier_name, ':description' => $description]);

        // 2. Add the quantity to the storage table.
        $updateSql = "INSERT INTO storage (product_id, store_id, available_pieces) VALUES (:product_id, :store_id, :quantity) ON DUPLICATE KEY UPDATE available_pieces = available_pieces + :quantity";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':product_id' => $product_id, ':store_id' => $store_id, ':quantity' => $quantity]);

        $pdo->commit();
        $_SESSION['message'] = "Import recorded successfully. Stock has been updated.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    }

    header("location: add.php");
    exit;
}

header("location: add.php");
exit;
?>