<?php
/**
 * Database Configuration
 * Eduvos C2C Marketplace
 */

// Database configuration
define('DB_HOST', 'sql204.infinityfree.com');
define('DB_NAME', 'if0_39193930_edu_c2c_marketplace');
define('DB_USER', 'if0_39193930');
define('DB_PASS', '3UKH8YQ7tkdn');

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
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            // Return error response for AJAX requests
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Database connection failed',
                    'debug' => $e->getMessage()
                ]);
                exit;
            }
            
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
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}

?>