<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
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

$report_to_delete = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = $_GET['id'];
    try {
        $sql = "SELECT bi.id, bi.quantity, p.factory_code, s.name as store_name 
                FROM broken_items bi
                JOIN products p ON bi.product_id = p.id
                JOIN stores s ON bi.store_id = s.id
                WHERE bi.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $report_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Report not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No report ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

$page_title = 'Delete Broken Item Report';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2>Confirm Deletion</h2>
            </div>
            <div class="card-body">
                <p class="text-center">Are you sure you want to delete this report? (<?php echo htmlspecialchars($report_to_delete['quantity']); ?> pieces of <strong><?php echo htmlspecialchars($report_to_delete['factory_code']); ?></strong> from store <strong><?php echo htmlspecialchars($report_to_delete['store_name']); ?></strong>)</p>
                <p class="text-center text-danger">This will add the quantity back to the main storage count. This action cannot be undone.</p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($report_to_delete['id']); ?>">
                    <button type="submit" class="btn btn-danger mr-2">Yes, Delete Report</button>
                    <a href="../auth/distribute.php" class="btn btn-secondary">No, Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>