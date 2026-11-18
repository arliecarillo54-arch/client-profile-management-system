
<?php

// Start session if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load private database configuration
$configFile = __DIR__ . "/config.local.php";

if (!file_exists($configFile)) {
    die("Database configuration file is missing.");
}

$config = require $configFile;

try {
    $conn = new PDO(
        "mysql:host=" . $config['host'] .
        ";dbname=" . $config['dbname'] .
        ";charset=utf8mb4",
        $config['username'],
        $config['password']
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

?>
