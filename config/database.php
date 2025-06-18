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

/**
 * Initialize database tables if they don't exist
 */
function initializeDatabase() {
    try {
        $pdo = getDatabase();
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() == 0) {
            // Create users table
            $sql = "
            CREATE TABLE users (
                user_id INT PRIMARY KEY AUTO_INCREMENT,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE,
                phone_number VARCHAR(20) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                user_type ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
                is_verified BOOLEAN DEFAULT FALSE,
                verification_date DATETIME NULL,
                profile_image_url VARCHAR(500),
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL,
                is_active BOOLEAN DEFAULT TRUE,
                preferred_language VARCHAR(10) DEFAULT 'en',
                low_data_mode BOOLEAN DEFAULT FALSE,
                remember_token VARCHAR(255) NULL,
                status VARCHAR(20) DEFAULT 'active',
                INDEX idx_phone (phone_number),
                INDEX idx_email (email),
                INDEX idx_user_type (user_type),
                INDEX idx_verification (is_verified)
            )";
            $pdo->exec($sql);
        }
        
        // Check if categories table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
        if ($stmt->rowCount() == 0) {
            // Create categories table
            $sql = "
            CREATE TABLE categories (
                category_id INT PRIMARY KEY AUTO_INCREMENT,
                category_name VARCHAR(100) NOT NULL UNIQUE,
                category_slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                icon_class VARCHAR(50),
                is_active BOOLEAN DEFAULT TRUE,
                sort_order INT DEFAULT 0,
                parent_category_id INT NULL,
                FOREIGN KEY (parent_category_id) REFERENCES categories(category_id),
                INDEX idx_active (is_active),
                INDEX idx_parent (parent_category_id)
            )";
            $pdo->exec($sql);
            
            // Insert default categories
            $categories = [
                ['Fresh Produce', 'produce', 'Fruits, vegetables, and fresh farm products', 'fas fa-apple-alt'],
                ['Handicrafts', 'handicrafts', 'Handmade items, crafts, and artisan products', 'fas fa-paint-brush'],
                ['Clothing', 'clothing', 'Clothes, shoes, and fashion accessories', 'fas fa-tshirt'],
                ['Electronics', 'electronics', 'Phones, computers, and electronic devices', 'fas fa-mobile-alt'],
                ['Home Goods', 'home', 'Furniture, appliances, and household items', 'fas fa-home'],
                ['Other', 'other', 'Other items and miscellaneous products', 'fas fa-tag']
            ];
            
            $insertCat = $pdo->prepare("INSERT INTO categories (category_name, category_slug, description, icon_class) VALUES (?, ?, ?, ?)");
            foreach ($categories as $cat) {
                $insertCat->execute($cat);
            }
        }
        
        // Check if listings table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'listings'");
        if ($stmt->rowCount() == 0) {
            // Create listings table
            $sql = "
            CREATE TABLE listings (
                listing_id INT PRIMARY KEY AUTO_INCREMENT,
                seller_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                category_id INT NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                quantity_available INT NOT NULL DEFAULT 1,
                allow_offers BOOLEAN DEFAULT FALSE,
                allow_barter BOOLEAN DEFAULT FALSE,
                condition_type ENUM('new', 'like_new', 'good', 'fair', 'poor') DEFAULT 'good',
                location_province VARCHAR(100) NOT NULL,
                location_city VARCHAR(100) NOT NULL,
                location_area VARCHAR(200),
                pickup_available BOOLEAN DEFAULT TRUE,
                delivery_available BOOLEAN DEFAULT FALSE,
                status ENUM('draft', 'active', 'sold', 'expired', 'removed') DEFAULT 'active',
                views_count INT DEFAULT 0,
                favorites_count INT DEFAULT 0,
                date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                expiry_date DATETIME NULL,
                featured_until DATETIME NULL,
                admin_approved BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(category_id),
                INDEX idx_seller (seller_id),
                INDEX idx_category (category_id),
                INDEX idx_status (status),
                INDEX idx_location (location_province, location_city),
                INDEX idx_price (price),
                INDEX idx_date_created (date_created),
                FULLTEXT idx_search (title, description)
            )";
            $pdo->exec($sql);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

?>