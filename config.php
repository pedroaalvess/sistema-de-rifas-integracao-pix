<?php
// config.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // disable public error output; use logs instead
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/logs/error.log');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'raffle_system');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed.");
}

// BlackCat API Keys
define('BLACKCAT_SECRET_KEY', 'sk_Br-pkbauum5bAzSRqqHa1kfcirDqVLrVMRu5Dr-gZdn2B4WP');
define('BLACKCAT_PUBLIC_KEY', 'pk_pXb05DCxytcnz8SViYmOjSo2BlHKf0vUlpegTgmgkfwdNF-7');
?>
