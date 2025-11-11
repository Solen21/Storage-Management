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
    $_SESSION['error'] = "You do not have permission to manage backups.";
    header("location: ../auth/distribute.php");
    exit;
}

// Function to get human-readable file size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$backup_dir = __DIR__;

// --- Handle Download Request ---
if (isset($_GET['download'])) {
    $file_name = basename($_GET['download']);
    $file_path = $backup_dir . DIRECTORY_SEPARATOR . $file_name;

    // Security check: ensure the file is a .sql file and is within the backup directory
    if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == 'sql') {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        $_SESSION['error'] = "File not found or invalid file type.";
        header("Location: backup.php");
        exit;
    }
}

// --- Handle Delete Request ---
if (isset($_GET['delete'])) {
    $file_name = basename($_GET['delete']);
    $file_path = $backup_dir . DIRECTORY_SEPARATOR . $file_name;

    if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == 'sql') {
        unlink($file_path);
        $_SESSION['message'] = "Backup file '" . htmlspecialchars($file_name) . "' deleted successfully.";
    } else {
        $_SESSION['error'] = "Could not delete file. File not found.";
    }
    header("Location: backup.php");
    exit;
}

// Scan for existing backup files
$backup_files = glob($backup_dir . '/*.sql');
rsort($backup_files); // Sort by most recent first

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

$page_title = 'Database Backups';
include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row mb-3">
        <div class="col">
            <h2>Database Backups</h2>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="row">
        <!-- Manual Backup Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-hand-paper mr-2"></i>Manual Backup</div>
                <div class="card-body">
                    <p class="card-text">Click the button below to create an immediate backup of the entire database. The backup file will be saved on the server.</p>
                    <a href="manual_backup_backend.php" class="btn btn-primary">Create Manual Backup Now</a>
                </div>
            </div>
        </div>

        <!-- Automatic Backup Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-robot mr-2"></i>Automatic Backups</div>
                <div class="card-body">
                    <p class="card-text">To enable automatic daily backups, set up a cron job on your server to execute the following script once per day:</p>
                    <pre><code>php <?php echo htmlspecialchars(__DIR__ . DIRECTORY_SEPARATOR . 'automatic_backup_backend.php'); ?> daily</code></pre>
                    <p class="card-text">For weekly backups (e.g., once a week):</p>
                    <pre><code>php <?php echo htmlspecialchars(__DIR__ . DIRECTORY_SEPARATOR . 'automatic_backup_backend.php'); ?> weekly</code></pre>
                    <p class="card-text">For yearly backups (e.g., once a year):</p>
                    <pre><code>php <?php echo htmlspecialchars(__DIR__ . DIRECTORY_SEPARATOR . 'automatic_backup_backend.php'); ?> yearly</code></pre>
                    <small class="text-muted">Consult your hosting provider's documentation for instructions on how to set up a cron job.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Backups Card -->
    <div class="card mt-4">
        <div class="card-header"><i class="fas fa-list mr-2"></i>Existing Backups</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backup_files)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No backups found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backup_files as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(basename($file)); ?></td>
                                    <td><?php echo formatBytes(filesize($file)); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', filemtime($file)); ?></td>
                                    <td>
                                        <a href="?download=<?php echo urlencode(basename($file)); ?>" class="btn btn-success btn-sm">Download</a>
                                        <a href="?delete=<?php echo urlencode(basename($file)); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this backup?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>