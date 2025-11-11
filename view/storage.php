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

// Determine user role for conditional display of action buttons
$user_role_name = '';
$role_id = $_SESSION["role_id"] ?? null;
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
        // Log error but don't block the page load
        error_log("Could not fetch role name in view/storage.php: " . $e->getMessage());
    }
}
$is_manager_or_admin = in_array($user_role_name, ['admin', 'manager']);

// Fetch all storage data with product and store names using JOINs
$storage_items = $pdo->query("
    SELECT 
        s.id, 
        p.factory_code, 
        st.name as store_name, 
        s.available_pieces, 
        s.last_updated
    FROM storage s
    JOIN products p ON s.product_id = p.id
    JOIN stores st ON s.store_id = st.id
    ORDER BY st.name, p.factory_code ASC
")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'View All Stock';
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h2>View All Stock</h2>
        </div>
        <?php if ($is_manager_or_admin): ?>
        <div class="col text-right">
            <a href="../storage/add.php" class="btn btn-success">Add Product to Storage</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Store</th>
                    <th>Product (Factory Code)</th>
                    <th>Available Quantity</th>
                    <th>Last Updated</th>
                    <?php if ($is_manager_or_admin): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($storage_items)): ?>
                    <tr>
                        <td colspan="<?php echo $is_manager_or_admin ? '5' : '4'; ?>" class="text-center">No products are currently tracked in storage.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($storage_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['factory_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['available_pieces']); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($item['last_updated']))); ?></td>
                        <?php if ($is_manager_or_admin): ?>
                        <td>
                            <a href="../storage/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="../storage/delete.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>