<?php
session_start();

// Security check: Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("location: ../auth/login.php");
    exit;
}

// Include database connection
require_once '../database/connection.php';

// Security check: Must be an admin or manager
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

// Fetch all export records with related names using JOINs
$exports = $pdo->query("
    SELECT 
        e.id,
        e.quantity,
        e.customer_name,
        e.description,
        e.export_date,
        p.factory_code,
        c.name as category_name,
        s.name as store_name
    FROM exports e
    LEFT JOIN products p ON e.product_id = p.id
    LEFT JOIN categories c ON e.category_id = c.id
    LEFT JOIN stores s ON e.store_id = s.id
    ORDER BY e.export_date DESC
")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('export_records');
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo __('export_records'); ?></h2>
        </div>
        <div class="col text-right">
            <a href="../exports/add.php" class="btn btn-success"><?php echo __('record_new_export'); ?></a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th><?php echo __('date'); ?></th>
                    <th><?php echo __('product_by_factory_code'); ?></th>
                    <th><?php echo __('category'); ?></th>
                    <th><?php echo __('store'); ?></th>
                    <th><?php echo __('quantity'); ?></th>
                    <th><?php echo __('customer'); ?></th>
                    <th><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exports as $export): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($export['export_date']))); ?></td>
                    <td><?php echo htmlspecialchars($export['factory_code']); ?></td>
                    <td><?php echo htmlspecialchars($export['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($export['store_name']); ?></td>
                    <td><?php echo htmlspecialchars($export['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($export['customer_name']); ?></td>
                    <td>
                        <a href="../exports/edit.php?id=<?php echo $export['id']; ?>" class="btn btn-primary btn-sm"><?php echo __('edit'); ?></a>
                        <a href="../exports/delete.php?id=<?php echo $export['id']; ?>" class="btn btn-danger btn-sm"><?php echo __('delete'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>