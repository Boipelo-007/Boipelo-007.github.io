<?php
/**
 * Database Connection Test
 * Check if database is properly configured and accessible
 */

// Include database configuration
require_once 'config/database.php';

// Set content type
header('Content-Type: application/json');

try {
    // Test database connection
    $pdo = getDatabase();
    
    // Test basic query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    
    if ($result['test'] == 1) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Basic query failed');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>