<?php
/*
 * System States Widget
 * This widget is designed to be included in a dashboard page.
 * It assumes a session has already been started and a PDO connection ($pdo) is available
 * from the parent page that includes it.
 */

// We must re-verify the user's role to ensure this widget is only displayed to authorized users.
$widget_user_role_name = '';
$widget_role_id = $_SESSION["role_id"] ?? null;

if ($widget_role_id) {
    try {
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $widget_role_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $widget_user_role_name = strtolower($stmt->fetchColumn());
        }
    } catch (PDOException $e) {
        // Fail gracefully if role cannot be determined
        error_log("Error fetching role in system_states.php widget: " . $e->getMessage());
    }
}

// Only proceed if the user is an admin or manager
if (in_array($widget_user_role_name, ['admin', 'manager'])):

    // --- Fetch Application Statistics from the latest report ---
    $latest_report = $pdo->query("SELECT * FROM reports ORDER BY report_date DESC LIMIT 1")->fetch();

    // --- Fetch Server Environment Details ---
    $php_version = phpversion(); // e.g., 8.1.10
    $db_server_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); // e.g., 10.4.27-MariaDB
    $web_server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; // e.g., Apache/2.4.54 (Win64) OpenSSL/1.1.1p PHP/8.1.10

    // --- Fetch more server and PHP details ---
    $server_os = PHP_OS;
    $server_ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
    $memory_limit = ini_get('memory_limit');
    $max_execution_time = ini_get('max_execution_time');
    $upload_max_filesize = ini_get('upload_max_filesize');

    // Extract Apache version from SERVER_SOFTWARE
    $apache_version = 'N/A';
    if (preg_match('/Apache\/(\d+\.\d+\.\d+)/', $web_server_software, $matches)) {
        $apache_version = $matches[0];
    }

    // Disk Space (for the drive where the script is running)
    $disk_path = $_SERVER['DOCUMENT_ROOT']; // This should point to C:/xampp/htdocs/B2-ceramic/
    $free_space_bytes = @disk_free_space($disk_path);
    $total_space_bytes = @disk_total_space($disk_path);

    $free_space_gb = 'N/A';
    $total_space_gb = 'N/A';
    $disk_usage_percent = 'N/A';

    if ($free_space_bytes !== false && $total_space_bytes !== false && $total_space_bytes > 0) {
        $free_space_gb = round($free_space_bytes / (1024 * 1024 * 1024), 2) . ' GB';
        $total_space_gb = round($total_space_bytes / (1024 * 1024 * 1024), 2) . ' GB';
        $used_space_gb = round(($total_space_bytes - $free_space_bytes) / (1024 * 1024 * 1024), 2) . ' GB';
        $disk_usage_percent = round((($total_space_bytes - $free_space_bytes) / $total_space_bytes) * 100, 2) . '%';
    }

?>

<h3 class="mb-3 <?php echo $widget_user_role_name === 'admin' ? 'text-white' : ''; ?>">System Status</h3>
<div class="row">
    <?php if ($latest_report): ?>
        <div class="col-lg-2 col-md-4 col-6 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-box"></i></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($latest_report['total_product']); ?></h5>
                    <p class="card-text">Products</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-boxes"></i></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($latest_report['total_available_product']); ?></h5>
                    <p class="card-text">In Stock</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-arrow-circle-down"></i></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($latest_report['total_imported_product']); ?></h5>
                    <p class="card-text">Imported</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-arrow-circle-up"></i></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($latest_report['total_exported_product']); ?></h5>
                    <p class="card-text">Exported</p>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-heart-broken"></i></div>
                    <h5 class="card-title"><?php echo htmlspecialchars($latest_report['total_broken_product']); ?></h5>
                    <p class="card-text">Broken</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">No reports have been generated yet to show stats. <a href="../reports/add.php">Generate one now</a>.</div>
        </div>
    <?php endif; ?>

    <!-- Server Environment Card -->
    <?php if ($widget_user_role_name === 'admin'): // Only show the server card to admins ?>
        <div class="col-lg-4 col-md-6 col-12 mb-4"> <!-- Increased size for more details -->
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <div class="h1"><i class="fas fa-server"></i></div>
                    <h5 class="card-title">Server</h5>
                    <p class="card-text text-left" style="font-size: 0.75rem;">
                        <strong>PHP Version:</strong> <?php echo htmlspecialchars($php_version); ?><br>
                        <strong>Apache:</strong> <?php echo htmlspecialchars($apache_version); ?><br>
                        <strong>Database:</strong> <?php echo htmlspecialchars($db_server_version); ?><br>
                        <strong>OS:</strong> <?php echo htmlspecialchars($server_os); ?><br>
                        <strong>Server IP:</strong> <?php echo htmlspecialchars($server_ip); ?><br>
                        <hr class="my-1 border-light">
                        <strong>PHP Memory Limit:</strong> <?php echo htmlspecialchars($memory_limit); ?><br>
                        <strong>Max Execution Time:</strong> <?php echo htmlspecialchars($max_execution_time); ?>s<br>
                        <strong>Max Upload Size:</strong> <?php echo htmlspecialchars($upload_max_filesize); ?><br>
                        <hr class="my-1 border-light">
                        <strong>Disk Usage:</strong> <?php echo htmlspecialchars($disk_usage_percent); ?><br>
                        <small>(Used: <?php echo htmlspecialchars($used_space_gb); ?> / Total: <?php echo htmlspecialchars($total_space_gb); ?>)</small><br>
                        <small>Path: <?php echo htmlspecialchars($disk_path); ?></small>

                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div><!-- /.row -->

<?php endif; // End of role check ?>