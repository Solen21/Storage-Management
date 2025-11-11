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
$allowed_roles = ['admin']; // Only admin can edit users

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
        error_log("Database error checking role in users/edit.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

// Redirect if user's role is not allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to edit users.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch the user to be edited
$user_to_edit = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $edit_id = $_GET['id'];
    try {
        $sql = "SELECT id, full_name, username, role_id, phone, status FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $user_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
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
    $_SESSION['error'] = "No user ID specified.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch all roles for the dropdown
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = __('edit_user');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2><?php echo __('edit_user'); ?>: <?php echo htmlspecialchars($user_to_edit['username']); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="edit_backend.php" method="post">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="full_name"><?php echo __('full_name'); ?></label>
                            <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($user_to_edit['full_name']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="username"><?php echo __('username'); ?></label>
                            <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($user_to_edit['username']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo __('new_password_instructions'); ?></label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="role_id"><?php echo __('role'); ?></label>
                            <select name="role_id" id="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['id']); ?>" <?php echo ($role['id'] == $user_to_edit['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="phone"><?php echo __('phone'); ?></label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($user_to_edit['phone']); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="status"><?php echo __('status'); ?></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="Active" <?php echo ($user_to_edit['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($user_to_edit['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo __('update_user'); ?></button>
                        <a href="../auth/distribute.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>