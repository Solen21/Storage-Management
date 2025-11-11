<?php
session_start();

// Security check: Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to view this page.";
    header("location: ../auth/login.php");
    exit;
}

// Include database connection
require_once '../database/connection.php';

// Security check: Must be an admin
$role_id = $_SESSION["role_id"] ?? null;
$allowed_roles = ['admin'];

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
    $_SESSION['error'] = "You do not have permission to view this page.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch all users with their role names using a JOIN
$users = $pdo->query("
    SELECT u.id, u.full_name, u.username, u.phone, u.status, u.created_at, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.id ASC
")->fetchAll();

// Retrieve and clear messages from session
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

// Include the language loader system first to define the __() function
require_once '../includes/language.php';

$page_title = __('manage_users');
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo __('manage_users'); ?></h2>
        </div>
        <div class="col text-right">
            <a href="../users/add.php" class="btn btn-success"><?php echo __('add_new_user'); ?></a>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th><?php echo __('id'); ?></th>
                    <th><?php echo __('full_name'); ?></th>
                    <th><?php echo __('username'); ?></th>
                    <th><?php echo __('role'); ?></th>
                    <th><?php echo __('phone'); ?></th>
                    <th><?php echo __('status'); ?></th>
                    <th><?php echo __('created_at'); ?></th>
                    <th><?php echo __('actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars(ucfirst($user['role_name'])); ?></span></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><span class="badge <?php echo $user['status'] == 'Active' ? 'badge-success' : 'badge-secondary'; ?>"><?php echo htmlspecialchars($user['status']); ?></span></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))); ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['id']): // Prevent admin from deleting themselves ?>
                            <a href="../auth/impersonate.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm" title="Login as this user"><i class="fas fa-user-secret"></i></a>
                            <a href="../users/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm"><?php echo __('edit'); ?></a>
                            <a href="../users/delete.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm"><?php echo __('delete'); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../includes/footer.php';
?>