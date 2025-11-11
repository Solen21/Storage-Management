<?php

namespace App\Controllers;

use App\Models\User;

use App\Core\Controller;

class AuthController extends Controller
{
    public function login()
    {
        $this->view('auth/login');
    }

    /**
     * Process the login form submission.
     */
    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // We need to load the model file before we can use it.
            require_once __DIR__ . '/../Models/User.php';
            $userModel = new User();

            $user = $userModel->findByUsername($username);

            // Securely verify the password against the stored hash.
            // The 'password_hash' column comes from your 'users' table.
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful. Start a session.
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Redirect to the dashboard.
                header('Location: /dashboard/index');
                exit;
            } else {
                // Login failed. Redirect back to the login page with an error.
                // We'll implement showing the error message in the next step.
                header('Location: /auth/login?error=1');
                exit;
            }
        } else {
            // If not a POST request, redirect to the login page.
            header('Location: /auth/login');
            exit;
        }
    }
}
