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

// Security check: Must be an admin
$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin']; // Only admin can delete users

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
        error_log("Database error checking role in users/delete.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

// Redirect if user's role is not allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to delete users.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch the user to be deleted
$user_to_delete = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = $_GET['id'];

    // Critical check: Prevent an admin from deleting themselves
    if ($delete_id == $_SESSION['id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header("location: ../auth/distribute.php");
        exit;
    }

    try {
        $sql = "SELECT id, full_name, username FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $delete_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $user_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $_SESSION['error'] = "User not found.";
            header("location: ../auth/distribute.php"); 
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("location: ../auth/distribute.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No user ID specified for deletion.";
    header("location: ../auth/distribute.php");
    exit;
}

$page_title = __('delete');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-danger text-white text-center">
                <h2><?php echo __('confirm_deletion'); ?></h2>
            </div>
            <div class="card-body">
                <p class="text-center"><?php echo __('are_you_sure_delete_user'); ?>: <strong><?php echo htmlspecialchars($user_to_delete['username']); ?></strong> (<?php echo htmlspecialchars($user_to_delete['full_name']); ?>)?</p>
                <p class="text-center text-danger"><?php echo __('this_action_cannot_be_undone'); ?></p>
                <form action="delete_backend.php" method="post" class="text-center">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_to_delete['id']); ?>">
                    <button type="submit" class="btn btn-danger mr-2"><?php echo __('yes_delete_this_user'); ?></button>
                    <a href="../auth/distribute.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>