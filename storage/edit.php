<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

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

$storage_entry = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_id = $_GET['id'];
    try {
        $sql = "SELECT s.id, s.available_pieces, p.factory_code, st.name as store_name 
                FROM storage s
                JOIN products p ON s.product_id = p.id
                JOIN stores st ON s.store_id = st.id
                WHERE s.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $storage_entry = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Storage entry not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No storage entry ID specified.";
    header("location: ../auth/distribute.php");
    exit;
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'Update Stock Quantity';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Update Stock Quantity</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="edit_backend.php" method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($storage_entry['id']); ?>">
                    <div class="form-group">
                        <label>Product (Factory Code)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($storage_entry['factory_code']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Store</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($storage_entry['store_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="available_pieces">Available Quantity</label>
                        <input type="number" name="available_pieces" id="available_pieces" class="form-control" value="<?php echo htmlspecialchars($storage_entry['available_pieces']); ?>" min="0" required>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Update Quantity</button>
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