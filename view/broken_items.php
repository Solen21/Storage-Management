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

// Fetch all broken item records with related names using JOINs
$broken_items = $pdo->query("
    SELECT 
        bi.id,
        bi.quantity,
        bi.description,
        bi.created_at,
        p.factory_code,
        s.name as store_name
    FROM broken_items bi
    LEFT JOIN products p ON bi.product_id = p.id
    LEFT JOIN stores s ON bi.store_id = s.id
    ORDER BY bi.created_at DESC
")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'View Broken Items';
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h2>Broken Item Reports</h2>
        </div>
        <div class="col text-right">
            <a href="../broken_items/add.php" class="btn btn-warning">Report New Broken Item</a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Date</th>
                    <th>Product (Code)</th>
                    <th>Store</th>
                    <th>Quantity</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($broken_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($item['created_at']))); ?></td>
                    <td><?php echo htmlspecialchars($item['factory_code']); ?></td>
                    <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <a href="../broken_items/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="../broken_items/delete.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
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