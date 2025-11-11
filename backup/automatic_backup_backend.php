<?php
/*
 * Automatic Backup Script
 * This script is intended to be run by a cron job or scheduled task.
 * It does not require a user session.
 */

// Set the working directory to the script's location
chdir(__DIR__);

// We need to include the connection file to get database credentials.
require_once '../database/connection.php';

// Database credentials are now available: $host, $db, $user, $pass
$backup_dir = __DIR__;

// Construct the mysqldump command
$mysqldump_path = 'C:\xampp\mysql\bin\mysqldump.exe'; // Adjust if your XAMPP is installed elsewhere

// --- Determine backup frequency and filename ---
$frequency = 'daily'; // Default frequency
if (isset($argv[1])) {
    $arg_frequency = strtolower($argv[1]);
    if (in_array($arg_frequency, ['daily', 'weekly', 'yearly'])) {
        $frequency = $arg_frequency;
    }
}

$date_format = '';
$retention_days = 0;
switch ($frequency) {
    case 'daily':
        $date_format = date('Y-m-d');
        $retention_days = 7; // Keep 7 daily backups
        break;
    case 'weekly':
        $date_format = date('Y-W'); // Year and week number
        $retention_days = 30 * 3; // Keep 3 months of weekly backups (approx 12 files)
        break;
    case 'yearly':
        $date_format = date('Y');
        $retention_days = 365 * 5; // Keep 5 years of yearly backups
        break;
}

$backup_file_name = 'auto-backup-' . $frequency . '-' . $date_format . '.sql';
$backup_file = $backup_dir . DIRECTORY_SEPARATOR . $backup_file_name;

$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s > %s',
    $mysqldump_path,
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($db),
    escapeshellarg($backup_file)
);

@exec($command, $output, $return_var);

// Optional: Log the result
$log_file = $backup_dir . DIRECTORY_SEPARATOR . 'auto_backup.log';
if ($return_var === 0 && file_exists($backup_file)) {
    $log_message = date('Y-m-d H:i:s') . " - SUCCESS: Automatic " . $frequency . " backup created: " . basename($backup_file) . "\n";

    // --- Cleanup old backups of this frequency ---
    if ($retention_days > 0) {
        $files_to_clean = glob($backup_dir . '/auto-backup-' . $frequency . '-*.sql');
        foreach ($files_to_clean as $old_file) {
            // Don't delete the backup we just created
            if (realpath($old_file) == realpath($backup_file)) {
                continue;
            }
            if (filemtime($old_file) < (time() - ($retention_days * 24 * 60 * 60))) {
                unlink($old_file);
                $log_message .= date('Y-m-d H:i:s') . " - CLEANUP: Deleted old backup: " . basename($old_file) . "\n";
            }
        }
    }
} else {
    $log_message = date('Y-m-d H:i:s') . " - FAILED: Automatic backup failed. Return code: $return_var\n";
}
file_put_contents($log_file, $log_message, FILE_APPEND);

exit($return_var);
?>