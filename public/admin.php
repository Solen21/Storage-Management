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

// Security check: Must be an admin to view this page.
$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin'];

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

$page_title = __('admin_dashboard');
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h1><?php echo __('admin_dashboard'); ?></h1>
            <p class="lead"><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        </div>
    </div>

    <?php
    // Include the activity chart widget
    include '../widget/activity_chart.php';
    ?>

    <hr>
    <h3 class="mb-3"><?php echo __('administration'); ?></h3>
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white"><i class="fas fa-users-cog mr-2"></i><?php echo __('manage_users'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-users-cog card-icon"></i>
                    <p class="card-text"><?php echo __('manage_users_desc'); ?></p>
                    <a href="../view/users.php" class="btn btn-primary mt-auto"><?php echo __('go_to'); ?> <?php echo __('manage_users'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-user-tag mr-2"></i><?php echo __('manage_roles'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-user-tag card-icon"></i>
                    <p class="card-text"><?php echo __('manage_roles_desc'); ?></p>
                    <a href="../view/roles.php" class="btn btn-info mt-auto"><?php echo __('go_to'); ?> <?php echo __('manage_roles'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white"><i class="fas fa-cogs mr-2"></i><?php echo __('system_settings'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-cogs card-icon"></i>
                    <p class="card-text"><?php echo __('system_settings_desc'); ?></p>
                    <a href="../settings/edit.php" class="btn btn-secondary mt-auto"><?php echo __('go_to'); ?> <?php echo __('system_settings'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark"><i class="fas fa-database mr-2"></i><?php echo __('database_backups'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-database card-icon"></i>
                    <p class="card-text"><?php echo __('database_backups_desc'); ?></p>
                    <a href="../backup/backup.php" class="btn btn-warning mt-auto"><?php echo __('manage'); ?> <?php echo __('database_backups'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <h3 class="mb-3 text-white"><?php echo __('inventory_transactions'); ?></h3>
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-box mr-2"></i><?php echo __('products'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-box card-icon"></i>
                    <p class="card-text"><?php echo __('products_desc'); ?></p>
                    <a href="../view/products.php" class="btn btn-dark mt-auto"><?php echo __('manage'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-tags mr-2"></i><?php echo __('categories'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-tags card-icon"></i>
                    <p class="card-text"><?php echo __('categories_desc'); ?></p>
                    <a href="../view/categories.php" class="btn btn-dark mt-auto"><?php echo __('manage'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-store-alt mr-2"></i><?php echo __('stores'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-store-alt card-icon"></i>
                    <p class="card-text"><?php echo __('stores_desc'); ?></p>
                    <a href="../view/stores.php" class="btn btn-dark mt-auto"><?php echo __('manage'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white"><i class="fas fa-boxes mr-2"></i><?php echo __('view_stock'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-boxes card-icon"></i>
                    <p class="card-text"><?php echo __('view_all_stock_desc'); ?></p>
                    <a href="../view/storage.php" class="btn btn-success mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-arrow-circle-down mr-2"></i><?php echo __('imports'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-arrow-circle-down card-icon"></i>
                    <p class="card-text"><?php echo __('imports_desc'); ?></p>
                    <a href="../view/imports.php" class="btn btn-info mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white"><i class="fas fa-arrow-circle-up mr-2"></i><?php echo __('exports'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-arrow-circle-up card-icon"></i>
                    <p class="card-text"><?php echo __('exports_desc'); ?></p>
                    <a href="../view/exports.php" class="btn btn-info mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-dark text-white"><i class="fas fa-chart-pie mr-2"></i><?php echo __('reports'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-chart-pie card-icon"></i>
                    <p class="card-text"><?php echo __('reports_desc'); ?></p>
                    <a href="../view/reports.php" class="btn btn-dark mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white"><i class="fas fa-heart-broken mr-2"></i><?php echo __('report_broken_item'); ?></div>
                <div class="card-body d-flex flex-column">
                    <i class="fas fa-heart-broken card-icon"></i>
                    <p class="card-text"><?php echo __('report_broken_item_desc'); ?></p>
                    <a href="../view/broken_items.php" class="btn btn-danger mt-auto"><?php echo __('view'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <?php
    // Include the system states widget at the bottom
    include '../widget/system_states.php';
    ?>

</div>

<?php
include '../includes/footer.php';
?>
