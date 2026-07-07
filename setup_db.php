<?php


$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'vintage_dial';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass);
    $conn->set_charset('utf8mb4');
    $conn->query("DROP DATABASE IF EXISTS `$dbName`");
    $conn->query("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$dbName' recreated successfully.\n";
    $conn->select_db($dbName);

    $tables = [
        'admins' => "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'Administrator',
            reset_token VARCHAR(255) NULL,
            reset_token_expiry DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            image VARCHAR(255) NULL,
            status VARCHAR(20) DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'products' => "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            subtitle VARCHAR(255) NULL,
            category_id INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            description TEXT NULL,
            image TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'customers' => "CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NULL,
            city VARCHAR(50) NULL,
            address TEXT NULL,
            password VARCHAR(255) NOT NULL,
            gender VARCHAR(20) NULL,
            dob DATE NULL,
            total_orders INT DEFAULT 0,
            total_spent DECIMAL(10,2) DEFAULT 0.00,
            reset_token VARCHAR(255) NULL,
            reset_token_expiry DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'orders' => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_code VARCHAR(50) NOT NULL,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'Pending',
            note TEXT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review_text TEXT NOT NULL,
            review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            admin_reply TEXT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

        'contact_messages' => "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(255) NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'New',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($tables as $name => $sql) {
        $conn->query($sql);
        echo "Table '$name' created successfully.\n";
    }

    $conn->close();
    echo "Setup complete.\n";
} catch (mysqli_sql_exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}