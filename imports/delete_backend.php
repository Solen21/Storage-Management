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
    $id = $_POST['id'];

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid import ID for deletion.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get the import details before deleting
            $getStmt = $pdo->prepare("SELECT product_id, store_id, quantity FROM imports WHERE id = :id");
            $getStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $getStmt->execute();
            $import = $getStmt->fetch();

            if (!$import) {
                throw new Exception("Import record not found.");
            }

            // 2. Subtract the quantity from the storage table
            $updateStmt = $pdo->prepare("UPDATE storage SET available_pieces = available_pieces - :quantity WHERE product_id = :product_id AND store_id = :store_id");
            $updateStmt->execute([':quantity' => $import['quantity'], ':product_id' => $import['product_id'], ':store_id' => $import['store_id']]);

            // 3. Delete the import record
            $deleteStmt = $pdo->prepare("DELETE FROM imports WHERE id = :id");
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $pdo->commit();
            $_SESSION['message'] = "Import record deleted successfully. Stock has been reduced.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Operation failed: " . $e->getMessage();
        }
    }
}

header("location: ../auth/distribute.php");
exit;
?>