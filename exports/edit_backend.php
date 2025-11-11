<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin', 'manager', 'worker'];

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
    $_SESSION['error'] = "You do not have permission to manage exports.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $customer_name = trim($_POST['customer_name']);
    $description = trim($_POST['description']);

    if (empty($id) || !is_numeric($id) || !$new_quantity || $new_quantity <= 0) {
        $_SESSION['error'] = 'Invalid data provided. A positive quantity is required.';
        header("location: edit.php?id=" . $id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Get the original export details
        $origStmt = $pdo->prepare("SELECT * FROM exports WHERE id = :id");
        $origStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $origStmt->execute();
        $original_export = $origStmt->fetch();

        if (!$original_export) {
            throw new Exception("Original export record not found.");
        }
        $old_quantity = $original_export['quantity'];
        $product_id = $original_export['product_id'];
        $store_id = $original_export['store_id'];

        // 2. Calculate the difference to adjust stock
        $quantity_diff = $new_quantity - $old_quantity;

        // 3. Check if there is enough stock to cover the change
        $stockStmt = $pdo->prepare("SELECT available_pieces FROM storage WHERE product_id = :product_id AND store_id = :store_id");
        $stockStmt->execute([':product_id' => $product_id, ':store_id' => $store_id]);
        $current_stock = $stockStmt->fetchColumn();

        if ($current_stock < $quantity_diff) {
            throw new Exception("Cannot update. This change would result in negative stock. Only " . $current_stock . " pieces available for this adjustment.");
        }

        // 4. Update the storage table with the difference
        $updateStockStmt = $pdo->prepare("UPDATE storage SET available_pieces = available_pieces - :quantity_diff WHERE product_id = :product_id AND store_id = :store_id");
        $updateStockStmt->execute([':quantity_diff' => $quantity_diff, ':product_id' => $product_id, ':store_id' => $store_id]);

        // 5. Update the export record itself
        $updateExportStmt = $pdo->prepare("UPDATE exports SET quantity = :quantity, customer_name = :customer_name, description = :description WHERE id = :id");
        $updateExportStmt->execute([':quantity' => $new_quantity, ':customer_name' => $customer_name, ':description' => $description, ':id' => $id]);

        $pdo->commit();
        $_SESSION['message'] = "Export record updated successfully. Stock has been adjusted.";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    }
    header("location: edit.php?id=" . $id);
    exit;
}

header("location: ../auth/distribute.php");
exit;
?>