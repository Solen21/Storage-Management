<?php
// Start session to access session variables
session_start();

$login_err = $_SESSION['login_err'] ?? '';
unset($_SESSION['login_err']); // Clear the error message after displaying it

// Set the page title and include the header
$page_title = 'Login';
include '../includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header text-center">
                <h2>Login</h2>
            </div>
            <div class="card-body">
                <p class="text-center">Please fill in your credentials to login.</p>

                <?php
                if (!empty($login_err)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($login_err) . '</div>';
                }
                ?>

                <form action="login_backend.php" method="post">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control <?php echo (!empty($login_err)) ? 'is-invalid' : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control <?php echo (!empty($login_err)) ? 'is-invalid' : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary btn-block" value="Login">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// It's a good practice to have a footer file to include JS scripts and close the body/html tags.
// include '../includes/footer.php'; 
?>