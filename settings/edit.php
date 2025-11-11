<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("location: ../auth/login.php");
    exit;
}

require_once '../database/connection.php';

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
    $_SESSION['error'] = "You do not have permission to manage system settings.";
    header("location: ../auth/distribute.php");
    exit;
}

// Fetch the single row of settings
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
if (!$settings) {
    die("Critical Error: Settings row not found in the database. Please run the installation script.");
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'System Settings';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-10">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>System Settings</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="edit_backend.php" method="post" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name">System Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($settings['name']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="logo">System Logo</label>
                            <input type="file" name="logo" id="logo" class="form-control-file">
                            <?php if (!empty($settings['logo_path'])): ?>
                                <small class="form-text text-muted">Current logo: <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Current Logo" style="max-height: 40px;"></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="default_unit">Default Unit</label>
                            <input type="text" name="default_unit" id="default_unit" class="form-control" value="<?php echo htmlspecialchars($settings['default_unit']); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="category_size_unit">Category Size Unit</label>
                            <input type="text" name="category_size_unit" id="category_size_unit" class="form-control" value="<?php echo htmlspecialchars($settings['category_size_unit']); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="broken_items_threshold">Broken Items Threshold</label>
                            <input type="number" name="broken_items_threshold" id="broken_items_threshold" class="form-control" value="<?php echo htmlspecialchars($settings['broken_items_threshold']); ?>" min="0">
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="form-group col-md-12">
                            <label for="report_setting">Automatic Report Frequency</label>
                            <select name="report_setting" id="report_setting" class="form-control">
                                <option value="Daily" <?php echo ($settings['report_setting'] == 'Daily') ? 'selected' : ''; ?>>Daily</option>
                                <option value="Weekly" <?php echo ($settings['report_setting'] == 'Weekly') ? 'selected' : ''; ?>>Weekly</option>
                                <option value="Monthly" <?php echo ($settings['report_setting'] == 'Monthly') ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-primary mr-2">Save Settings</button>
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