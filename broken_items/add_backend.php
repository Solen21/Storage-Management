<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = trim($_POST['product_id']);
    $store_id = trim($_POST['store_id']);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);

    if (empty($product_id) || empty($store_id) || !$quantity || $quantity <= 0) {
        $_SESSION['error'] = 'Product, Store, and a valid positive Quantity are required.';
        header("location: add.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Check if there is enough stock in the storage table
        $checkSql = "SELECT available_pieces FROM storage WHERE product_id = :product_id AND store_id = :store_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $checkStmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        $current_stock = $checkStmt->fetchColumn();

        if ($current_stock === false) {
            throw new Exception("This product is not tracked in the selected store's inventory.");
        }

        if ($current_stock < $quantity) {
            throw new Exception("Not enough stock. Only " . $current_stock . " pieces available, but " . $quantity . " were reported broken.");
        }

        // 2. Insert the record into the broken_items table
        $insertSql = "INSERT INTO broken_items (product_id, store_id, quantity, description) VALUES (:product_id, :store_id, :quantity, :description)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $insertStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $insertStmt->bindParam(':description', $description, PDO::PARAM_STR);
        $insertStmt->execute();

        // 3. Deduct the quantity from the storage table
        $updateSql = "UPDATE storage SET available_pieces = available_pieces - :quantity WHERE product_id = :product_id AND store_id = :store_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $updateStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $updateStmt->bindParam(':store_id', $store_id, PDO::PARAM_INT);
        $updateStmt->execute();

        // If all queries were successful, commit the transaction
        $pdo->commit();
        $_SESSION['message'] = "Broken item report submitted successfully. Stock has been updated.";

    } catch (Exception $e) {
        // If any query fails, roll back the changes
        $pdo->rollBack();
        $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    }

    header("location: add.php");
    exit;
}

header("location: add.php");
exit;
?>