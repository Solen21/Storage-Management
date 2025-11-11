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

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid export ID for deletion.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get the export details before deleting
            $getStmt = $pdo->prepare("SELECT product_id, store_id, quantity FROM exports WHERE id = :id");
            $getStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $getStmt->execute();
            $export = $getStmt->fetch();

            if (!$export) {
                throw new Exception("Export record not found.");
            }

            // 2. Add the quantity back to the storage table
            $updateStmt = $pdo->prepare("UPDATE storage SET available_pieces = available_pieces + :quantity WHERE product_id = :product_id AND store_id = :store_id");
            $updateStmt->execute([':quantity' => $export['quantity'], ':product_id' => $export['product_id'], ':store_id' => $export['store_id']]);

            // 3. Delete the export record
            $deleteStmt = $pdo->prepare("DELETE FROM exports WHERE id = :id");
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $pdo->commit();
            $_SESSION['message'] = "Export record deleted successfully. Stock has been restored.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Operation failed: " . $e->getMessage();
        }
    }
}

header("location: ../auth/distribute.php");
exit;
?>