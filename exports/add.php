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

$products = $pdo->query("SELECT id, factory_code, category_id FROM products ORDER BY factory_code ASC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$stores = $pdo->query("SELECT id, name FROM stores ORDER BY name ASC")->fetchAll();

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('record_new_export');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2><?php echo __('record_new_export'); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product_id"><?php echo __('product_by_factory_code'); ?></label>
                            <select name="product_id" id="product_id" class="form-control" required>
                                <option value=""><?php echo __('select_a_product'); ?></option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>" data-category-id="<?php echo htmlspecialchars($product['category_id']); ?>"><?php echo htmlspecialchars($product['factory_code']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="category_id"><?php echo __('category'); ?></label>
                            <select name="category_id" id="category_id" class="form-control" required readonly>
                                <option value=""><?php echo __('select_a_product_first'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="store_id"><?php echo __('export_from_store'); ?></label>
                        <select name="store_id" id="store_id" class="form-control" required>
                            <option value=""><?php echo __('select_a_store'); ?></option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo htmlspecialchars($store['id']); ?>"><?php echo htmlspecialchars($store['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity"><?php echo __('quantity_exported'); ?></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_name"><?php echo __('customer_name'); ?></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="description"><?php echo __('description'); ?></label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo __('record_export'); ?></button>
                        <a href="../auth/distribute.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('product_id').addEventListener('change', function() {
    var categoryId = this.options[this.selectedIndex].getAttribute('data-category-id');
    document.getElementById('category_id').value = categoryId;
});
</script>

<?php
include '../includes/footer.php';
?>