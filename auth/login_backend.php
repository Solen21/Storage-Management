<?php
// Start the session
session_start();

// Include the database connection file
require_once '../database/connection.php';

$username = $password = "";
$login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $login_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $login_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($login_err)) {
        // Prepare a select statement
        $sql = "SELECT id, username, password_hash, role_id FROM users WHERE username = :username";

        if ($stmt = $pdo->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = $username;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Check if username exists, if yes then verify password
                if ($stmt->rowCount() == 1) {
                    if ($row = $stmt->fetch()) {
                        $id = $row["id"];
                        $hashed_password = $row["password_hash"];
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_regenerate_id(); // Regenerate session ID

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role_id"] = $row["role_id"];

                            // Redirect user to dashboard page
                            header("location: distribute.php");
                        } else {
                            // Password is not valid
                            $_SESSION['login_err'] = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist
                    $_SESSION['login_err'] = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            // Close statement
            unset($stmt);
        }
    }
    // Close connection
    unset($pdo);

    // If there was a login error, redirect back to the login page
    if (!empty($_SESSION['login_err'])) {
        header("location: login.php");
        exit;
    }
}
?>
