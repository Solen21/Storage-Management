<?php
// Start the session to access messages and login status
session_start();
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
        // Log the error, but don't expose sensitive info to the user
        error_log("Database error checking role in add.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred. Please try again.";
        header("location: ../auth/login.php"); // Redirect to login or an error page
        exit;
    }
}

// Check if the user's role is allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to add roles.";
    header("location: ../auth/distribute.php"); // Redirect to their dashboard
    exit;
}

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['error']);

// Set page title and include header
$page_title = 'Add New Role';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Add New Role</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2">Add Role</button>
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