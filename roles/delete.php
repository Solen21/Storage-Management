<?php
// Start the session to access messages and login status
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("location: ../auth/login.php");
    exit;
}

// Include the database connection to check role permissions
require_once '../database/connection.php';

$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin', 'manager']; // Define allowed roles

$user_role_name = '';
if ($role_id) {
    try {
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $role = $stmt->fetch();
            $user_role_name = strtolower($role['name']);
        }
    } catch (PDOException $e) {
        error_log("Database error checking role in delete.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/login.php");
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to delete roles.";
    header("location: ../auth/distribute.php");
    exit;
}

// Check if an ID was passed and fetch the role data
$role_to_delete = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = $_GET['id'];
    try {
        $sql = "SELECT id, name FROM roles WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $role_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Role not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No role ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

$page_title = 'Delete Role';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2>Confirm Deletion</h2>
            </div>
            <div class="card-body">
                <p class="text-center">Are you sure you want to delete the role: <strong><?php echo htmlspecialchars($role_to_delete['name']); ?></strong>?</p>
                <p class="text-center text-danger">This action cannot be undone.</p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($role_to_delete['id']); ?>">
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