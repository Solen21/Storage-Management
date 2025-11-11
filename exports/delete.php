<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
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

$export_to_delete = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = $_GET['id'];
    try {
        $sql = "SELECT e.id, e.quantity, p.factory_code, s.name as store_name 
                FROM exports e
                JOIN products p ON e.product_id = p.id
                JOIN stores s ON e.store_id = s.id
                WHERE e.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $export_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Export record not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No export ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('delete_export_record');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2><?php echo __('confirm_deletion'); ?></h2>
            </div>
            <div class="card-body">
                <p class="text-center"><?php echo __('are_you_sure_delete_export'); ?> (<?php echo htmlspecialchars($export_to_delete['quantity']); ?> <?php echo __('pieces_of'); ?> <strong><?php echo htmlspecialchars($export_to_delete['factory_code']); ?></strong> from store <strong><?php echo htmlspecialchars($export_to_delete['store_name']); ?></strong>)</p>
                <p class="text-center text-danger"><?php echo __('this_action_highly_discouraged_export'); ?></p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($export_to_delete['id']); ?>">
                    <button type="submit" class="btn btn-danger mr-2"><?php echo __('yes_delete_record'); ?></button>
                    <a href="../auth/distribute.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>