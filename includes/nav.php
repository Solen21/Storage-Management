<?php
// This file assumes a session has already been started in header.php

$user_role_name = '';
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // We need a database connection to get the role name
    require_once __DIR__ . '/../database/connection.php';

    $role_id = $_SESSION["role_id"] ?? null;

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
            // Fail gracefully if the role can't be fetched
            error_log("Could not fetch role name in nav.php: " . $e->getMessage());
        }
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="/B2-ceramic/index.php">
            <?php // Use translation for the system name
            echo __('inventory_system');
            ?>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mr-auto">
                <?php if ($user_role_name === 'admin'): ?>
                    <!-- Admin Links -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo __('administration'); ?>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="adminMenu">
                            <a class="dropdown-item" href="/B2-ceramic/view/users.php"><?php echo __('manage_users'); ?></a>
                            <a class="dropdown-item" href="/B2-ceramic/view/roles.php"><?php echo __('manage_roles'); ?></a>
                            <a class="dropdown-item" href="/B2-ceramic/settings/edit.php"><?php echo __('system_settings'); ?></a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if (in_array($user_role_name, ['admin', 'manager'])): ?>
                    <!-- Admin & Manager Links -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="inventoryMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo __('inventory'); ?>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="inventoryMenu">
                            <a class="dropdown-item" href="/B2-ceramic/view/products.php"><?php echo __('manage_products'); ?></a>
                            <a class="dropdown-item" href="/B2-ceramic/view/categories.php"><?php echo __('manage_categories'); ?></a>
                            <a class="dropdown-item" href="/B2-ceramic/view/stores.php"><?php echo __('manage_stores'); ?></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="/B2-ceramic/view/storage.php"><?php echo __('view_all_stock'); ?></a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <?php if (in_array($user_role_name, ['admin', 'manager', 'worker'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="transactionsMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo __('transactions'); ?>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="transactionsMenu">
                                <?php if (in_array($user_role_name, ['admin', 'manager'])): ?>
                                    <a class="dropdown-item" href="/B2-ceramic/imports/add.php"><?php echo __('record_import'); ?></a>
                                <?php endif; ?>
                                <a class="dropdown-item" href="/B2-ceramic/exports/add.php"><?php echo __('record_export'); ?></a>
                                <div class="dropdown-divider"></div>
                                <?php if (in_array($user_role_name, ['admin', 'manager'])): ?>
                                    <a class="dropdown-item" href="/B2-ceramic/view/imports.php"><?php echo __('view_imports'); ?></a>
                                <?php endif; ?>
                                <a class="dropdown-item" href="/B2-ceramic/view/exports.php"><?php echo __('view_exports'); ?></a>
                            </div>
                        <?php endif; ?>
                    </li>
                     <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo __('reports'); ?>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="reportsMenu">
                             <a class="dropdown-item" href="/B2-ceramic/reports/add.php"><?php echo __('generate_report'); ?></a>
                             <a class="dropdown-item" href="/B2-ceramic/view/reports.php"><?php echo __('view_reports'); ?></a>
                             <a class="dropdown-item" href="/B2-ceramic/view/broken_items.php"><?php echo __('view_broken_items'); ?></a>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($user_role_name === 'worker'): ?>
                    <!-- Worker Links -->
                    <li class="nav-item"><a class="nav-link" href="/B2-ceramic/view/storage.php"><?php echo __('view_stock'); ?></a></li>
                <?php endif; ?>
                 <li class="nav-item"><a class="nav-link" href="/B2-ceramic/broken_items/add.php"><?php echo __('report_broken_item'); ?></a></li>
            </ul>

            <!-- Right side of Navbar -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="langMenu" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-globe"></i> <?php echo strtoupper($_SESSION['lang']); ?>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="langMenu">
                        <a class="dropdown-item" href="/B2-ceramic/auth/switch_language.php?lang=en"><?php echo __('english'); ?></a>
                        <a class="dropdown-item" href="/B2-ceramic/auth/switch_language.php?lang=am"><?php echo __('amharic'); ?></a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" id="darkModeToggle" title="<?php echo __('toggle_dark_mode'); ?>">
                        <i class="fas fa-moon"></i>
                        <i class="fas fa-sun" style="display: none;"></i>
                    </a>
                </li>
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <li class="nav-item">
                        <span class="navbar-text mr-3">
                            <?php echo __('welcome'); ?>, <?php echo htmlspecialchars($_SESSION["username"]); ?> (<?php echo htmlspecialchars(ucfirst($user_role_name)); ?>)
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light" href="/B2-ceramic/auth/logout.php"><?php echo __('logout'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>