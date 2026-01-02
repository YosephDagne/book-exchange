<?php
require_once __DIR__ . '/config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database.\n";

    $columns = [
        'payment_account_1_type' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_1_number' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_1_holder' => 'VARCHAR(100) DEFAULT NULL',
        'payment_account_2_type' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_2_number' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_2_holder' => 'VARCHAR(100) DEFAULT NULL',
        'payment_account_3_type' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_3_number' => 'VARCHAR(50) DEFAULT NULL',
        'payment_account_3_holder' => 'VARCHAR(100) DEFAULT NULL'
    ];

    foreach ($columns as $col => $def) {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM books LIKE ?");
        $stmt->execute([$col]);
        if (!$stmt->fetch()) {
            echo "Adding column $col...\n";
            $pdo->exec("ALTER TABLE books ADD COLUMN $col $def AFTER file_path");
        } else {
            echo "Column $col already exists.\n";
        }
    }

    echo "Schema update complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
