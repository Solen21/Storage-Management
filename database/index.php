<?php
/*
 * This script handles the initial setup of the database and tables.
 * It's designed to be run once.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Installation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding-top: 40px; padding-bottom: 40px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h2>System Installation & Setup</h2>
        </div>
        <div class="card-body">
<?php
// Database connection parameters are now inside the main block
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $host = 'localhost';
    $db   = 'Stor_management';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    // 1. Connect to MySQL (without database)
    $dsn = "mysql:host=$host;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo '<div class="alert alert-success">Database \''.htmlspecialchars($db).'\' created or already exists.</div>';

    // 3. Connect to the new database
    $pdo->exec("USE `$db`");

    // 4. Turn off foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 5. Create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        full_name TEXT NOT NULL,
        role_id INT NOT NULL,
        phone TEXT,
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    );

    CREATE TABLE IF NOT EXISTS stores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        location TEXT,
        capacity INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        width DECIMAL(10,2),
        height DECIMAL(10,2),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        factory_code VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS storage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        store_id INT NOT NULL,
        available_pieces INT DEFAULT 0,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS broken_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (store_id) REFERENCES stores(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS imports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        category_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        supplier_name VARCHAR(255),
        description TEXT,
        import_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS exports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        category_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        customer_name VARCHAR(255),
        description TEXT,
        export_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        total_category INT NOT NULL,
        total_product INT NOT NULL,
        total_available_product INT NOT NULL,
        total_broken_product INT NOT NULL,
        total_imported_product INT NOT NULL,
        total_exported_product INT NOT NULL,
        description TEXT,
        created_by INT,
        report_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
            ON UPDATE CASCADE
            ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        default_unit VARCHAR(50) DEFAULT 'carton',
        category_size_unit VARCHAR(50) DEFAULT 'cm',
        role_and_permission TEXT,
        name VARCHAR(100) DEFAULT 'Inventory System',
        logo_path TEXT,
        report_setting ENUM('Daily', 'Weekly', 'Monthly') DEFAULT 'Daily',
        broken_items_threshold INT DEFAULT 0,
        toggle_features TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    SET FOREIGN_KEY_CHECKS = 1;
    ";

    $pdo->exec($sql);
    echo '<div class="alert alert-success">All tables created successfully.</div>';

    // --- Schema Updates ---
    // Add store_id to imports table if it doesn't exist, to fix errors on existing installations.
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `imports` LIKE 'store_id'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `imports` ADD COLUMN `store_id` INT NOT NULL AFTER `id`, ADD FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE CASCADE ON DELETE CASCADE;");
        echo '<div class="alert alert-info">Updated `imports` table to include `store_id`.</div>';
    }

    // Add store_id to exports table if it doesn't exist, to fix errors on existing installations.
    $checkColumnExports = $pdo->query("SHOW COLUMNS FROM `exports` LIKE 'store_id'");
    if ($checkColumnExports->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `exports` ADD COLUMN `store_id` INT NOT NULL AFTER `id`, ADD FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON UPDATE CASCADE ON DELETE CASCADE;");
        echo '<div class="alert alert-info">Updated `exports` table to include `store_id`.</div>';
    }

    // --- End Schema Updates ---

    // 6. Insert default roles and users
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'Admin', 'Full system access')");
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (2, 'Manager', 'Manages inventory and staff')");
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (3, 'Worker', 'Handles day-to-day inventory tasks')");
    echo '<div class="alert alert-info">Default roles (Admin, Manager, Worker) created.</div>';
    
    // Prepare statement for inserting users
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password_hash, full_name, role_id, phone, status) 
                           VALUES (:username, :password, :full_name, :role_id, :phone, :status)");

    // Insert Admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT); // Set a secure password
    $stmt->execute([
        ':username' => 'admin',
        ':password' => $adminPassword,
        ':full_name' => 'System Administrator',
        ':role_id' => 1,
        ':phone' => '0000000000',
        ':status' => 'Active'
    ]);

    // Insert Manager user
    $managerPassword = password_hash('manager123', PASSWORD_DEFAULT); // Set a secure password
    $stmt->execute([
        ':username' => 'manager',
        ':password' => $managerPassword,
        ':full_name' => 'Inventory Manager',
        ':role_id' => 2,
        ':phone' => '1111111111',
        ':status' => 'Active'
    ]);

    // Insert Worker user
    $workerPassword = password_hash('worker123', PASSWORD_DEFAULT); // Set a secure password
    $stmt->execute([
        ':username' => 'worker',
        ':password' => $workerPassword,
        ':full_name' => 'Warehouse Worker',
        ':role_id' => 3,
        ':phone' => '2222222222',
        ':status' => 'Active'
    ]);

    // 7. Insert default settings row
    $pdo->exec("INSERT IGNORE INTO settings (id) VALUES (1)");

    echo '<div class="alert alert-info">Default users created.</div>';
    ?>
    <div class="alert alert-warning">
        <h4 class="alert-heading">Default User Credentials</h4>
        <p>Please use the following credentials to log in. It is highly recommended to change these passwords after your first login.</p>
        <hr>
        <ul class="list-unstyled">
            <li><strong>Admin:</strong> username: <code>admin</code>, password: <code>admin123</code></li>
            <li><strong>Manager:</strong> username: <code>manager</code>, password: <code>manager123</code></li>
            <li><strong>Worker:</strong> username: <code>worker</code>, password: <code>worker123</code></li>
        </ul>
    </div>
    <div class="alert alert-success text-center">
        <h4>Installation Completed Successfully!</h4>
        <a href="../index.php" class="btn btn-primary mt-2">Go to Welcome Page</a>
    </div>
    <?php

} catch (PDOException $e) {
    echo '<div class="alert alert-danger"><h4>Installation Failed!</h4><p>A database error occurred: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
?>
        </div>
    </div>
</div>
</body>
</html>
