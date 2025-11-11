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

$export_to_edit = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_id = $_GET['id'];
    try {
        $sql = "SELECT e.*, p.factory_code, s.name as store_name 
                FROM exports e
                JOIN products p ON e.product_id = p.id
                JOIN stores s ON e.store_id = s.id
                WHERE e.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $export_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
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
    $_SESSION['error'] = "No export ID specified.";
    header("location: ../auth/distribute.php");
    exit;
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('edit_export_record');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2><?php echo __('edit_export_record'); ?></h2>
                <p class="text-danger"><?php echo __('warning_editing_record'); ?></p>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="edit_backend.php" method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($export_to_edit['id']); ?>">
                    <p><strong><?php echo __('product'); ?>:</strong> <?php echo htmlspecialchars($export_to_edit['factory_code']); ?></p>
                    <p><strong><?php echo __('store'); ?>:</strong> <?php echo htmlspecialchars($export_to_edit['store_name']); ?></p>
                    <div class="form-group">
                        <label for="quantity"><?php echo __('quantity_exported'); ?></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo htmlspecialchars($export_to_edit['quantity']); ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_name"><?php echo __('customer_name'); ?></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control" value="<?php echo htmlspecialchars($export_to_edit['customer_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="description"><?php echo __('description'); ?></label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($export_to_edit['description']); ?></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo __('update_export'); ?></button>
                        <a href="../auth/distribute.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>