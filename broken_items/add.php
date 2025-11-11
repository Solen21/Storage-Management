<?php
// Start the session
session_start();

// Security check: Any logged-in user can report broken items.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("location: ../auth/login.php");
    exit;
}

// Include database connection
require_once '../database/connection.php';

// Fetch products and stores for dropdowns
$products = $pdo->query("SELECT id, factory_code FROM products ORDER BY factory_code ASC")->fetchAll();
$stores = $pdo->query("SELECT id, name FROM stores ORDER BY name ASC")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'Report Broken Items';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Report Broken Items</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product_id">Product (by Factory Code)</label>
                            <select name="product_id" id="product_id" class="form-control" required>
                                <option value="">Select a product...</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['factory_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="store_id">Store</label>
                            <select name="store_id" id="store_id" class="form-control" required>
                                <option value="">Select a store...</option>
                                <?php foreach ($stores as $store): ?>
                                    <option value="<?php echo htmlspecialchars($store['id']); ?>"><?php echo htmlspecialchars($store['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity Broken</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description / Reason</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Report Broken</button>
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