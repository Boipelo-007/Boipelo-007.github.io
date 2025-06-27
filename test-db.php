<?php
/**
 * Database Connection Test Script
 * Use this to diagnose database connection issues
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test 1: Basic PHP Info
echo "<h3>1. PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br><br>";

// Test 2: Database Configuration
echo "<h3>2. Database Configuration</h3>";
require_once 'config/database.php';

echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "DB_PASS: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : 'Empty') . "<br><br>";

// Test 3: Connection Test
echo "<h3>3. Connection Test</h3>";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    echo "DSN: " . $dsn . "<br>";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "<span style='color: green;'>✓ Database connection successful!</span><br><br>";
    
    // Test 4: Check Tables
    echo "<h3>4. Database Tables Check</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<span style='color: red;'>✗ No tables found in database!</span><br>";
        echo "You need to import the database schema first.<br><br>";
    } else {
        echo "<span style='color: green;'>✓ Found " . count($tables) . " tables:</span><br>";
        foreach ($tables as $table) {
            echo "- " . $table . "<br>";
        }
        echo "<br>";
    }
    
    // Test 5: Check Required Tables
    echo "<h3>5. Required Tables Check</h3>";
    $requiredTables = ['users', 'listings', 'categories', 'listing_images'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "<span style='color: green;'>✓ {$table} table exists</span><br>";
            
            // Check record count
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                $count = $countStmt->fetch()['count'];
                echo "&nbsp;&nbsp;Records: {$count}<br>";
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;<span style='color: orange;'>Warning: Could not count records - " . $e->getMessage() . "</span><br>";
            }
        } else {
            $missingTables[] = $table;
            echo "<span style='color: red;'>✗ {$table} table missing</span><br>";
        }
    }
    
    if (!empty($missingTables)) {
        echo "<br><span style='color: red;'>Missing tables need to be created: " . implode(', ', $missingTables) . "</span><br><br>";
    }
    
    // Test 6: Test API Endpoint
    echo "<h3>6. API Endpoint Test</h3>";
    
    // Test get_listings.php directly
    $apiUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . 
              dirname($_SERVER['REQUEST_URI']) . '/server/get_listings.php?limit=1';
    
    echo "Testing API URL: <a href='{$apiUrl}' target='_blank'>{$apiUrl}</a><br>";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $apiResponse = file_get_contents($apiUrl, false, $context);
    
    if ($apiResponse === false) {
        echo "<span style='color: red;'>✗ API endpoint not accessible</span><br>";
    } else {
        echo "<span style='color: green;'>✓ API endpoint accessible</span><br>";
        echo "Response length: " . strlen($apiResponse) . " characters<br>";
        
        // Try to parse JSON
        $jsonData = json_decode($apiResponse, true);
        if ($jsonData === null) {
            echo "<span style='color: red;'>✗ API returned invalid JSON</span><br>";
            echo "Raw response (first 500 chars): <pre>" . htmlspecialchars(substr($apiResponse, 0, 500)) . "</pre>";
        } else {
            echo "<span style='color: green;'>✓ API returned valid JSON</span><br>";
            echo "Success: " . ($jsonData['success'] ? 'true' : 'false') . "<br>";
            if (!$jsonData['success']) {
                echo "Error: " . ($jsonData['error'] ?? 'Unknown error') . "<br>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<span style='color: red;'>✗ Database connection failed!</span><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br><br>";
    
    // Common solutions
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Check if database credentials are correct</li>";
    echo "<li>Verify database server is running</li>";
    echo "<li>Ensure database name exists</li>";
    echo "<li>Check if user has permissions to access the database</li>";
    echo "<li>Verify firewall settings allow database connections</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ General error occurred!</span><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If connection failed, fix database credentials in config/database.php</li>";
echo "<li>If tables are missing, import the database schema from misc/php.sql</li>";
echo "<li>If API test failed, check server configuration and file permissions</li>";
echo "<li>Add sample data to test the listings functionality</li>";
echo "</ol>";

echo "<p><strong>File Location:</strong> Save this as 'test-db.php' in your root directory and access via browser.</p>";
?>