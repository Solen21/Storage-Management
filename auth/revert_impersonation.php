<?php
session_start();

// Check if there is an original admin session to revert to
if (isset($_SESSION['original_admin_session'])) {

    // Restore the original admin's session details
    $original_session = $_SESSION['original_admin_session'];
    $_SESSION['id'] = $original_session['id'];
    $_SESSION['username'] = $original_session['username'];
    $_SESSION['role_id'] = $original_session['role_id'];

    // Unset the impersonation data
    unset($_SESSION['original_admin_session']);

    // Redirect to the admin dashboard
    header("location: distribute.php");
    exit;

} else {
    // If there's no impersonation session, just go to the normal dashboard
    header("location: distribute.php");
    exit;
}
?>