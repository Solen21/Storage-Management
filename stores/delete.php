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
    $_SESSION['error'] = "You do not have permission to manage stores.";
    header("location: ../auth/distribute.php");
    exit;
}

$store_to_delete = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM stores WHERE id = :id");
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $store_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Store not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No store ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

$page_title = 'Delete Store';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2>Confirm Deletion</h2>
            </div>
            <div class="card-body">
                <p class="text-center">Are you sure you want to delete the store: <strong><?php echo htmlspecialchars($store_to_delete['name']); ?></strong>?</p>
                <p class="text-center text-danger">This action cannot be undone and may fail if the store contains products.</p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($store_to_delete['id']); ?>">
                    <button type="submit" class="btn btn-danger mr-2">Yes, Delete</button>
                    <a href="../auth/distribute.php" class="btn btn-secondary">No, Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>