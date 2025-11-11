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
    $_SESSION['error'] = "You do not have permission to generate reports.";
    header("location: ../auth/distribute.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $description = trim($_POST['description']);
    $created_by = $_SESSION['id'];

    try {
        // Calculate all totals
        $total_category = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
        $total_product = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $total_available_product = $pdo->query("SELECT SUM(available_pieces) FROM storage")->fetchColumn() ?: 0;
        $total_broken_product = $pdo->query("SELECT SUM(quantity) FROM broken_items")->fetchColumn() ?: 0;
        $total_imported_product = $pdo->query("SELECT SUM(quantity) FROM imports")->fetchColumn() ?: 0;
        $total_exported_product = $pdo->query("SELECT SUM(quantity) FROM exports")->fetchColumn() ?: 0;

        // Insert the new report
        $sql = "INSERT INTO reports (total_category, total_product, total_available_product, total_broken_product, total_imported_product, total_exported_product, description, created_by) 
                VALUES (:total_category, :total_product, :total_available_product, :total_broken_product, :total_imported_product, :total_exported_product, :description, :created_by)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':total_category' => $total_category,
            ':total_product' => $total_product,
            ':total_available_product' => $total_available_product,
            ':total_broken_product' => $total_broken_product,
            ':total_imported_product' => $total_imported_product,
            ':total_exported_product' => $total_exported_product,
            ':description' => $description,
            ':created_by' => $created_by
        ]);

        $_SESSION['message'] = "New report generated successfully!";

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error during report generation: " . $e->getMessage();
    }

    header("location: add.php");
    exit;
}

header("location: add.php");
exit;
?>