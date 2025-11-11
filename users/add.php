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
$allowed_roles = ['admin']; // Only admin can add users

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
        error_log("Database error checking role in users/add.php: " . $e->getMessage());
        $_SESSION['error'] = "An unexpected error occurred.";
        header("location: ../auth/distribute.php");
        exit;
    }
}

// Redirect if user's role is not allowed
if (!in_array($user_role_name, $allowed_roles)) {
    $_SESSION['error'] = "You do not have permission to add new users.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch all roles for the dropdown
$roles = [];
try {
    $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    // If roles can't be fetched, it's a critical error
    die("Could not fetch roles from the database.");
}

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = __('add_new_user');
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2><?php echo __('add_new_user'); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="add_backend.php" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="full_name"><?php echo __('full_name'); ?></label>
                            <input type="text" name="full_name" id="full_name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="username"><?php echo __('username'); ?></label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password"><?php echo __('password'); ?></label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="role_id"><?php echo __('role'); ?></label>
                            <select name="role_id" id="role_id" class="form-control" required>
                                <option value=""><?php echo __('select_a_role'); ?></option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role['id']); ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone"><?php echo __('phone_optional'); ?></label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary mr-2"><?php echo __('add_user'); ?></button>
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