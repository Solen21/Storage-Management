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
    $_SESSION['error'] = "You do not have permission to manage products.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch all categories for the dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'Add New Product';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Add New Product</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-group">
                        <label for="factory_code">Factory Code</label>
                        <input type="text" name="factory_code" id="factory_code" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Add Product</button>
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