<?php
// Start the session to check the user's login status
session_start();

// If the user is already logged in, redirect them to the role-based distributor
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: auth/distribute.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .welcome-container {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1 class="display-4">Welcome to the Inventory System</h1>
        <p class="lead">To continue, please log in to your account.</p>
        <hr class="my-4">
        <a class="btn btn-primary btn-lg" href="auth/login.php" role="button">Login</a>
    </div>
</body>
</html>

