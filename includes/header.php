<?php
// We start the session on every page that includes the header
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the language loader system
require_once __DIR__ . '/language.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Inventory System'; ?></title>
    
    <!-- Bootstrap CSS from CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome CSS from CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Optional: Link to your custom stylesheet -->
    <!-- <link rel="stylesheet" href="/B2-ceramic/assets/css/custom.css"> -->

    <style>
        /* Add padding to the body to prevent content from being hidden by the fixed navbar */
        :root {
            --body-bg: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --card-header-bg: #f7f7f7;
            --table-bg: #ffffff;
            --table-border-color: #dee2e6;
            --table-striped-bg: rgba(0, 0, 0, 0.05);
        }

        body.dark-mode {
            --body-bg: #212529;
            --text-color: #f8f9fa;
            --card-bg: #2c3034;
            --card-header-bg: #343a40;
            --table-bg: #2c3034;
            --table-border-color: #454d55;
            --table-striped-bg: rgba(255, 255, 255, 0.05);
        }

        body {
            padding-top: 70px;
            background-color: var(--body-bg);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body.dark-mode {
            background-image: 
                repeating-linear-gradient(45deg, rgba(255,255,255,0.03) 0, rgba(255,255,255,0.03) 1px, transparent 0, transparent 50%),
                repeating-linear-gradient(-45deg, rgba(255,255,255,0.03) 0, rgba(255,255,255,0.03) 1px, transparent 0, transparent 50%);
            background-size: 40px 40px;
        }

        body.animate-bg {
            animation: moveLines 2s linear;
        }

        .card { background-color: var(--card-bg); }
        .card-header { background-color: var(--card-header-bg); }
        .list-group-item { background-color: var(--card-bg); color: var(--text-color); }
        .table { background-color: var(--table-bg); color: var(--text-color); }
        .table-bordered, .table-bordered td, .table-bordered th { border-color: var(--table-border-color); }
        .table-striped tbody tr:nth-of-type(odd) { background-color: var(--table-striped-bg); }
        .text-white { color: #f8f9fa !important; } /* Ensure high contrast text remains white */
        .alert-info a { color: #0c5460; }
        body.dark-mode .alert-info a { color: #90deed; }

        @keyframes moveLines {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 40px 40px;
            }
        }

        .card-icon {
            font-size: 3rem; /* Larger icon size */
            opacity: 0.08; /* Make it subtle */
            position: absolute;
            right: 20px;
            bottom: 10px;
            transition: opacity 0.3s ease-in-out;
        }
    </style>
</head>
<body>

<?php
// Include the role-based navigation bar
include_once __DIR__ . '/nav.php';
?>

<?php if (isset($_SESSION['original_admin_session'])): ?>
<div class="alert alert-warning text-center mb-0" style="position: fixed; top: 56px; width: 100%; z-index: 1029;">
    <i class="fas fa-user-secret mr-2"></i>
    You are currently acting as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.
    <a href="/B2-ceramic/auth/revert_impersonation.php" class="alert-link ml-3">Return to your Admin account</a>
</div>
<?php endif; ?>

<!-- Start of the main page content container -->
<div class="container">