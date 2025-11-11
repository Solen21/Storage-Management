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
    $_SESSION['error'] = "You do not have permission to delete reports.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];

    if (empty($id) || !is_numeric($id)) {
        $_SESSION['error'] = 'Invalid report ID for deletion.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get the report details before deleting
            $getReportStmt = $pdo->prepare("SELECT product_id, store_id, quantity FROM broken_items WHERE id = :id");
            $getReportStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $getReportStmt->execute();
            $report = $getReportStmt->fetch();

            if (!$report) {
                throw new Exception("Report not found.");
            }

            // 2. Add the quantity back to the storage table
            $updateStockStmt = $pdo->prepare("UPDATE storage SET available_pieces = available_pieces + :quantity WHERE product_id = :product_id AND store_id = :store_id");
            $updateStockStmt->execute([':quantity' => $report['quantity'], ':product_id' => $report['product_id'], ':store_id' => $report['store_id']]);

            // 3. Delete the report from the broken_items table
            $deleteStmt = $pdo->prepare("DELETE FROM broken_items WHERE id = :id");
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();

            $pdo->commit();
            $_SESSION['message'] = "Broken item report deleted successfully. Stock has been restored.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Operation failed: " . $e->getMessage();
        }
    }
}

header("location: ../auth/distribute.php");
exit;
?>