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
        error_log("Database error checking role in edit.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/login.php");
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to edit roles.";
    header("location: ../auth/distribute.php");
    exit;
}

// Check if an ID was passed via GET, and fetch the role data
$role_to_edit = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_id = $_GET['id'];
    try {
        $sql = "SELECT id, name, description FROM roles WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $role_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "Role not found.";
            // Redirect to a list page if it exists, otherwise back to dashboard
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No role ID specified.";
    header("location: ../auth/distribute.php");
    exit;
}

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Set page title and include header
$page_title = 'Edit Role';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Edit Role</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="edit_backend.php" method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($role_to_edit['id']); ?>">
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($role_to_edit['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($role_to_edit['description']); ?></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Update Role</button>
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