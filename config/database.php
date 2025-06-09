<?php
/**
 * Database Configuration
 * Eduvos C2C Marketplace
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'edu_c2c_marketplace');
define('DB_USER', 'root');  // Change this to your database username
define('DB_PASS', '');      // Change this to your database password

/**
 * Get database connection
 * @return PDO
 */
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $pdo;
}

/**
 * Check if database connection is working
 * @return bool
 */
function testDatabaseConnection() {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}

?>