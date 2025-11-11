<?php
session_start();

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    // Whitelist allowed languages for security
    if ($lang == 'en' || $lang == 'am') {
        $_SESSION['lang'] = $lang;
    }
}

// Redirect back to the previous page, or to the dashboard as a fallback
$previous_page = $_SERVER['HTTP_REFERER'] ?? '../auth/distribute.php';
header('Location: ' . $previous_page);
exit;
?>