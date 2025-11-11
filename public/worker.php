<?php
session_start();

// Security check: Must be logged in to view this page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("location: ../auth/login.php");
    exit;
}

// Include database connection
require_once '../database/connection.php';

// Security check: All roles can access the worker dashboard as it's the most basic.
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
    $_SESSION['error'] = "You do not have permission to view this page.";
    header("location: ../auth/distribute.php");
    exit;
}

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('worker_dashboard');
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h1><?php echo __('worker_dashboard'); ?></h1>
            <p class="lead"><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
    </div>

    <hr>
    <h3 class="mb-3 <?php echo in_array($user_role_name, ['admin', 'manager']) ? 'text-white' : ''; ?>"><?php echo __('your_tasks'); ?></h3>
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><i class="fas fa-boxes mr-2"></i><?php echo __('view_stock'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-boxes card-icon"></i>
                    <p class="card-text"><?php echo __('view_stock_desc'); ?></p>
                    <a href="../view/storage.php" class="btn btn-success mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-arrow-circle-up mr-2"></i><?php echo __('manage_exports'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-arrow-circle-up card-icon"></i>
                    <p class="card-text"><?php echo __('manage_exports_desc'); ?></p>
                    <a href="../view/exports.php" class="btn btn-info mt-auto"><?php echo __('manage'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark"><i class="fas fa-heart-broken mr-2"></i><?php echo __('report_broken_item'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-heart-broken card-icon"></i>
                    <p class="card-text"><?php echo __('report_broken_item_desc'); ?></p>
                    <a href="../broken_items/add.php" class="btn btn-warning mt-auto"><?php echo __('report'); ?> <?php echo __('report_broken_item'); ?></a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include '../includes/footer.php';
?>