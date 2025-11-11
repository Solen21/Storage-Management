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
    $delete_id = $_GET['id'];
    try {
        $sql = "SELECT s.id, p.factory_code, st.name as store_name 
                FROM storage s
                JOIN products p ON s.product_id = p.id
                JOIN stores st ON s.store_id = st.id
                WHERE s.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
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
    $_SESSION['error'] = "No storage entry ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

$page_title = 'Delete Storage Entry';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2>Confirm Deletion</h2>
            </div>
            <div class="card-body">
                <p class="text-center">Are you sure you want to remove product <strong><?php echo htmlspecialchars($storage_entry['factory_code']); ?></strong> from the inventory of store <strong><?php echo htmlspecialchars($storage_entry['store_name']); ?></strong>?</p>
                <p class="text-center text-danger">This action cannot be undone.</p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($storage_entry['id']); ?>">
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