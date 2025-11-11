<?php
// Database connection parameters
$host = 'localhost';
$db   = 'Stor_management';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // 1. Connect to MySQL (without database)
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database '$db' created or already exists.<br>";

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
        category_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        supplier_name VARCHAR(255),
        description TEXT,
        import_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id)
            ON UPDATE CASCADE
            ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS exports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 0,
        customer_name VARCHAR(255),
        description TEXT,
        export_date DATETIME DEFAULT CURRENT_TIMESTAMP,
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
    echo "All tables created successfully.<br>";

    // 6. Insert default admin role and user
    $pdo->exec("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'Admin', 'Full system access')");
    
    $adminPassword = password_hash('4321', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password_hash, full_name, role_id, phone, status) 
                           VALUES (:username, :password, :full_name, :role_id, :phone, :status)");
    $stmt->execute([
        ':username' => 'admin',
        ':password' => $adminPassword,
        ':full_name' => 'System Administrator',
        ':role_id' => 1,
        ':phone' => '0000000000',
        ':status' => 'Active'
    ]);

    echo "Default admin user created (username: <b>admin</b>, password: <b>admin123</b>).<br>";
    echo "<b>Installation completed successfully!</b>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>


