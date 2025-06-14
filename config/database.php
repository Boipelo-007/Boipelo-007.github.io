<?php
/**
 * Database Configuration for Edu C2C Marketplace
 * InfinityFree Hosting Configuration
 */

// Database configuration constants
define('DB_HOST', 'sql204.infinityfree.com');
define('DB_NAME', 'if0_39193930_edu_c2c_marketplace');
define('DB_USER', 'if0_39193930');
define('DB_PASS', '3UKH8YQ7tkdn');
define('DB_CHARSET', 'utf8mb4');

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get PDO database connection
 * @return PDO
 * @throws Exception
 */
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_PERSISTENT => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Test the connection
            $pdo->query("SELECT 1");
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

/**
 * Test database connection
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

/**
 * Execute query safely with error handling
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 * @throws PDOException
 */
function executeQuery($sql, $params = []) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters with proper types
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_BOOL);
            } elseif (is_null($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt;
        
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage() . " SQL: " . $sql);
        throw $e;
    }
}

/**
 * Get single record
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function getSingleRecord($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getSingleRecord failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get multiple records
 * @param string $sql
 * @param array $params
 * @return array
 */
function getMultipleRecords($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getMultipleRecords failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get record count
 * @param string $sql
 * @param array $params
 * @return int
 */
function getRecordCount($sql, $params = []) {
    try {
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("getRecordCount failed: " . $e->getMessage());
        return 0;
    }
}

/**
 * Insert record and return last insert ID
 * @param string $table
 * @param array $data
 * @return string|false
 */
function insertRecord($table, $data) {
    try {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
        
        $pdo = getDatabase();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        return $pdo->lastInsertId();
        
    } catch (Exception $e) {
        error_log("insertRecord failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Update record
 * @param string $table
 * @param array $data
 * @param string $where
 * @param array $whereParams
 * @return bool
 */
function updateRecord($table, $data, $where, $whereParams = []) {
    try {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "`$column` = :$column";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE `$table` SET $setClause WHERE $where";
        
        $params = array_merge($data, $whereParams);
        $stmt = executeQuery($sql, $params);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        error_log("updateRecord failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete record
 * @param string $table
 * @param string $where
 * @param array $params
 * @return bool
 */
function deleteRecord($table, $where, $params = []) {
    try {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("deleteRecord failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Escape string for safe HTML output
 * @param string $string
 * @return string
 */
function escapeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Format currency for South African Rand
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    if ($amount == 0) {
        return 'Free';
    }
    return 'R' . number_format($amount, 2);
}

/**
 * Time ago helper function
 * @param string $datetime
 * @return string
 */
function timeAgo($datetime) {
    if (!$datetime) return 'Never';
    
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' weeks ago';
    
    return date('M j, Y', strtotime($datetime));
}

/**
 * Get system setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSystemSetting($key, $default = null) {
    try {
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = :key";
        $result = getSingleRecord($sql, [':key' => $key]);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("getSystemSetting failed: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set system setting value
 * @param string $key
 * @param mixed $value
 * @param string $description
 * @return bool
 */
function setSystemSetting($key, $value, $description = null) {
    try {
        $existing = getSingleRecord("SELECT setting_id FROM system_settings WHERE setting_key = :key", [':key' => $key]);
        
        if ($existing) {
            return updateRecord('system_settings', 
                ['setting_value' => $value], 
                'setting_key = :key', 
                [':key' => $key]
            );
        } else {
            return insertRecord('system_settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'description' => $description,
                'data_type' => 'string',
                'is_public' => false
            ]);
        }
    } catch (Exception $e) {
        error_log("setSystemSetting failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user activity
 * @param int $userId
 * @param string $actionType
 * @param string $tableName
 * @param int $recordId
 * @param array $details
 * @return bool
 */
function logActivity($userId, $actionType, $tableName = null, $recordId = null, $details = null) {
    try {
        $data = [
            'user_id' => $userId,
            'action_type' => $actionType,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'date_created' => date('Y-m-d H:i:s')
        ];
        
        if ($details) {
            $data['details'] = json_encode($details);
        }
        
        return insertRecord('activity_logs', $data) !== false;
        
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    if (is_string($data)) {
        return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
    }
    
    return $data;
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate South African phone number
 * @param string $phone
 * @return bool
 */
function isValidSAPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check various SA phone number formats
    $patterns = [
        '/^27[0-9]{9}$/',     // +27 format
        '/^0[0-9]{9}$/',      // 0 format
        '/^[0-9]{9}$/'        // 9 digits (missing leading 0)
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Format South African phone number
 * @param string $phone
 * @return string
 */
function formatSAPhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to standard +27 format
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        $phone = '27' . substr($phone, 1);
    } elseif (strlen($phone) === 9) {
        $phone = '27' . $phone;
    }
    
    // Format as +27 XX XXX XXXX
    if (strlen($phone) === 11 && substr($phone, 0, 2) === '27') {
        return '+27 ' . substr($phone, 2, 2) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
    }
    
    return $phone;
}

/**
 * Generate secure random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 32) {
    try {
        return bin2hex(random_bytes($length / 2));
    } catch (Exception $e) {
        // Fallback to less secure method
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate pagination HTML
 * @param int $currentPage
 * @param int $totalPages
 * @param string $baseUrl
 * @param array $params
 * @return string
 */
function generatePagination($currentPage, $totalPages, $baseUrl, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevParams = array_merge($params, ['page' => $currentPage - 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($prevParams) . '">Previous</a></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $pageParams = array_merge($params, ['page' => $i]);
        $active = ($i === $currentPage) ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($pageParams) . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextParams = array_merge($params, ['page' => $currentPage + 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . http_build_query($nextParams) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Check if user has permission
 * @param int $userId
 * @param string $permission
 * @return bool
 */
function userHasPermission($userId, $permission) {
    try {
        $sql = "SELECT user_type FROM users WHERE user_id = :user_id AND is_active = TRUE";
        $user = getSingleRecord($sql, [':user_id' => $userId]);
        
        if (!$user) {
            return false;
        }
        
        // Simple permission system based on user type
        $permissions = [
            'admin' => ['*'], // All permissions
            'seller' => ['view_own_listings', 'manage_own_listings', 'view_own_transactions'],
            'buyer' => ['view_own_transactions', 'create_reviews']
        ];
        
        $userPermissions = $permissions[$user['user_type']] ?? [];
        
        return in_array('*', $userPermissions) || in_array($permission, $userPermissions);
        
    } catch (Exception $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send notification to user
 * @param int $userId
 * @param string $type
 * @param string $title
 * @param string $content
 * @param int $relatedId
 * @return bool
 */
function sendNotification($userId, $type, $title, $content, $relatedId = null) {
    try {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'related_id' => $relatedId,
            'is_read' => false,
            'date_created' => date('Y-m-d H:i:s'),
            'expiry_date' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];
        
        return insertRecord('notifications', $data) !== false;
        
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's unread notification count
 * @param int $userId
 * @return int
 */
function getUnreadNotificationCount($userId) {
    try {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE AND (expiry_date IS NULL OR expiry_date > NOW())";
        $result = getSingleRecord($sql, [':user_id' => $userId]);
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log("Failed to get notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Initialize database connection and perform basic checks
 */
function initializeDatabase() {
    try {
        $pdo = getDatabase();
        
        // Check if basic tables exist
        $tables = ['users', 'listings', 'categories', 'transactions'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetch()) {
                throw new Exception("Required table '$table' not found. Please run the database setup script.");
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize database connection when this file is included
try {
    $dbInitialized = initializeDatabase();
    if (!$dbInitialized) {
        // Log error but don't stop execution in production
        error_log("Warning: Database initialization failed");
    }
} catch (Exception $e) {
    error_log("Database auto-initialization error: " . $e->getMessage());
}

// Set timezone for the application
date_default_timezone_set('Africa/Johannesburg');

/**
 * Development helper - only enable in development environment
 */
if (isset($_GET['debug']) && $_GET['debug'] === 'db' && php_sapi_name() === 'cli') {
    echo "Database Configuration Test\n";
    echo "==========================\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Connection: " . (testDatabaseConnection() ? "SUCCESS" : "FAILED") . "\n";
}
?>