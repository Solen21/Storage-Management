// c:\xampp\htdocs\Store-management\app\Controllers\AuthController.php

public function processLogin()
{
    // ... (code to check username and password) ...

    if ($user && password_verify(, ['password_hash'])) {
        // SUCCESS: Start session and redirect to dashboard
        header('Location: /dashboard/index');
        exit;
    } else {
        // FAILED: Redirect back to login page with an error
        header('Location: /auth/login?error=1');
        exit;
    }
}
