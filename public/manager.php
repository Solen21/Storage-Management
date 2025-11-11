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

// Security check: Must be a manager or admin to view this page.
// Admins can also access manager dashboard.
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
    $_SESSION['error'] = "You do not have permission to view this page.";
    header("location: ../auth/distribute.php");
    exit;
}

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = 'Manager Dashboard';
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h1>Manager Dashboard</h1>
            <p class="lead">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
    </div>

    <?php
    // Include the activity chart and system states widgets
    include '../widget/activity_chart.php';
    include '../widget/system_states.php';
    ?>

    <hr>
    <h3 class="mb-3 <?php echo $user_role_name === 'admin' ? 'text-white' : ''; ?>">Inventory & Transactions</h3>
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-box mr-2"></i>Products</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-box card-icon"></i>
                    <p class="card-text">Add, edit, and delete product definitions and factory codes.</p>
                    <a href="../view/products.php" class="btn btn-dark mt-auto">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-tags mr-2"></i>Categories</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-tags card-icon"></i>
                    <p class="card-text">Define product categories and their specific dimensions.</p>
                    <a href="../view/categories.php" class="btn btn-dark mt-auto">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-store-alt mr-2"></i>Stores</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-store-alt card-icon"></i>
                    <p class="card-text">Add or remove inventory locations and warehouses.</p>
                    <a href="../view/stores.php" class="btn btn-dark mt-auto">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><i class="fas fa-boxes mr-2"></i>View Stock</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-boxes card-icon"></i>
                    <p class="card-text">See current stock levels for all products across all stores.</p>
                    <a href="../view/storage.php" class="btn btn-success mt-auto">View</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-arrow-circle-down mr-2"></i>Imports</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-arrow-circle-down card-icon"></i>
                    <p class="card-text">Browse the history of all incoming product shipments.</p>
                    <a href="../view/imports.php" class="btn btn-info mt-auto">View</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-arrow-circle-up mr-2"></i>Exports</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-arrow-circle-up card-icon"></i>
                    <p class="card-text">Browse the history of all outgoing product shipments.</p>
                    <a href="../view/exports.php" class="btn btn-info mt-auto">View</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-pie mr-2"></i>Reports</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-chart-pie card-icon"></i>
                    <p class="card-text">View and generate historical snapshots of system data.</p>
                    <a href="../view/reports.php" class="btn btn-dark mt-auto">View</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white"><i class="fas fa-heart-broken mr-2"></i>Broken Items</div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-heart-broken card-icon"></i>
                    <p class="card-text">Track and manage all items reported as broken or lost.</p>
                    <a href="../view/broken_items.php" class="btn btn-danger mt-auto">View</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include '../includes/footer.php';
?>