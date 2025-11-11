<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION['error'] = "You must be logged in to perform this action.";
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get current logo path to keep it if no new logo is uploaded
        $current_settings = $pdo->query("SELECT logo_path FROM settings WHERE id = 1")->fetch();
        $logo_path = $current_settings['logo_path'] ?? null;

        // Handle file upload for logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = uniqid() . '-' . basename($_FILES['logo']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Validate file type
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    $logo_path = 'assets/uploads/' . $file_name; // Store relative path
                } else {
                    throw new Exception("Failed to move uploaded file.");
                }
            } else {
                throw new Exception("Invalid file type. Only JPG, JPEG, PNG, & GIF files are allowed.");
            }
        }

        // Prepare SQL to update settings
        $sql = "UPDATE settings SET 
                    name = :name, 
                    default_unit = :default_unit, 
                    category_size_unit = :category_size_unit, 
                    broken_items_threshold = :broken_items_threshold, 
                    report_setting = :report_setting,
                    logo_path = :logo_path
                WHERE id = 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':name' => trim($_POST['name']),
            ':default_unit' => trim($_POST['default_unit']),
            ':category_size_unit' => trim($_POST['category_size_unit']),
            ':broken_items_threshold' => filter_input(INPUT_POST, 'broken_items_threshold', FILTER_VALIDATE_INT),
            ':report_setting' => trim($_POST['report_setting']),
            ':logo_path' => $logo_path
        ]);

        $_SESSION['message'] = "Settings updated successfully!";

    } catch (Exception $e) {
        $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    }

    header("location: edit.php");
    exit;
}

header("location: ../auth/distribute.php");
exit;
?>