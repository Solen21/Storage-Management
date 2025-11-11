<?php
// Start the session
session_start();

// Security check: Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
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
    $_SESSION['error'] = "You do not have permission to manage storage.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch products and stores for dropdowns
$products = $pdo->query("SELECT id, factory_code FROM products ORDER BY factory_code ASC")->fetchAll();
$stores = $pdo->query("SELECT id, name FROM stores ORDER BY name ASC")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'Add Product to Storage';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Add Product to Storage</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-group">
                        <label for="product_id">Product (by Factory Code)</label>
                        <select name="product_id" id="product_id" class="form-control" required>
                            <option value="">Select a product...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['factory_code']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="store_id">Store</label>
                        <select name="store_id" id="store_id" class="form-control" required>
                            <option value="">Select a store...</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo htmlspecialchars($store['id']); ?>"><?php echo htmlspecialchars($store['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="available_pieces">Initial Quantity</label>
                        <input type="number" name="available_pieces" id="available_pieces" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Add to Storage</button>
                        <a href="../auth/distribute.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>