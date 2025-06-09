-- =============================================
-- edu C2C E-Marketplace Database Schema
-- Supporting South Africa's Informal Economy
-- =============================================

CREATE DATABASE IF NOT EXISTS edu_c2c_marketplace;
USE edu_c2c_marketplace;

-- =============================================
-- USER MANAGEMENT TABLES
-- =============================================

-- Users table (buyers, sellers, admins)
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
    INDEX idx_phone (phone_number),
    INDEX idx_email (email),
    INDEX idx_user_type (user_type),
    INDEX idx_verification (is_verified)
);

-- User locations
CREATE TABLE user_locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    province VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    area_neighborhood VARCHAR(200),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_primary BOOLEAN DEFAULT TRUE,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_location (province, city)
);

-- User verification documents
CREATE TABLE user_verifications (
    verification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    document_type ENUM('id_document', 'proof_of_address', 'business_registration') NOT NULL,
    document_url VARCHAR(500) NOT NULL,
    verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_date DATETIME NULL,
    reviewed_by INT NULL,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    INDEX idx_user_verification (user_id, verification_status)
);

-- User ratings and reviews
CREATE TABLE user_ratings (
    rating_id INT PRIMARY KEY AUTO_INCREMENT,
    rated_user_id INT NOT NULL,
    rating_user_id INT NOT NULL,
    transaction_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_public BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (rated_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (rating_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_rated_user (rated_user_id),
    UNIQUE KEY unique_user_transaction_rating (rating_user_id, transaction_id)
);

-- =============================================
-- PRODUCT CATALOG TABLES
-- =============================================

-- Product categories
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
);

-- Product listings
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
);

-- Listing images
CREATE TABLE listing_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    file_size_kb INT,
    image_width INT,
    image_height INT,
    date_uploaded DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    INDEX idx_listing (listing_id),
    INDEX idx_primary (is_primary)
);

-- Delivery options for listings
CREATE TABLE listing_delivery_options (
    delivery_option_id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    delivery_type ENUM('pickup', 'local_delivery', 'courier') NOT NULL,
    max_distance_km INT NULL,
    delivery_fee DECIMAL(8, 2) DEFAULT 0.00,
    estimated_days INT NULL,
    description VARCHAR(255),
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    INDEX idx_listing (listing_id)
);

-- User favorites/saved items
CREATE TABLE user_favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_listing (user_id, listing_id),
    INDEX idx_user_favorites (user_id)
);

-- =============================================
-- TRANSACTION TABLES
-- =============================================

-- Transaction records
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    transaction_type ENUM('purchase', 'barter') NOT NULL DEFAULT 'purchase',
    quantity INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    delivery_fee DECIMAL(8, 2) DEFAULT 0.00,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'escrow', 'barter') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    transaction_status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'disputed') DEFAULT 'pending',
    delivery_method ENUM('pickup', 'delivery') NOT NULL,
    delivery_address TEXT,
    delivery_status ENUM('pending', 'in_transit', 'delivered', 'failed') NULL,
    buyer_rating INT NULL CHECK (buyer_rating BETWEEN 1 AND 5),
    seller_rating INT NULL CHECK (seller_rating BETWEEN 1 AND 5),
    completion_date DATETIME NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id),
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    FOREIGN KEY (seller_id) REFERENCES users(user_id),
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_listing (listing_id),
    INDEX idx_status (transaction_status),
    INDEX idx_date (date_created)
);

-- Barter offers
CREATE TABLE barter_offers (
    barter_id INT PRIMARY KEY AUTO_INCREMENT,
    listing_id INT NOT NULL,
    offerer_id INT NOT NULL,
    offered_item_description TEXT NOT NULL,
    offered_item_value DECIMAL(10, 2),
    offer_status ENUM('pending', 'accepted', 'rejected', 'countered') DEFAULT 'pending',
    seller_response TEXT,
    counter_offer_description TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_responded DATETIME NULL,
    expiry_date DATETIME NULL,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (offerer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_listing (listing_id),
    INDEX idx_offerer (offerer_id),
    INDEX idx_status (offer_status)
);

-- Barter offer images
CREATE TABLE barter_offer_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    barter_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    description VARCHAR(255),
    date_uploaded DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barter_id) REFERENCES barter_offers(barter_id) ON DELETE CASCADE,
    INDEX idx_barter (barter_id)
);

-- =============================================
-- COMMUNICATION TABLES
-- =============================================

-- Messages between users
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    listing_id INT NULL,
    transaction_id INT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('text', 'image', 'location', 'offer') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_read DATETIME NULL,
    is_deleted_by_sender BOOLEAN DEFAULT FALSE,
    is_deleted_by_recipient BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE SET NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE SET NULL,
    INDEX idx_conversation (sender_id, recipient_id),
    INDEX idx_listing_messages (listing_id),
    INDEX idx_unread (recipient_id, is_read),
    INDEX idx_date (date_sent)
);

-- =============================================
-- ADMINISTRATIVE TABLES
-- =============================================

-- Reports and flagged content
CREATE TABLE reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    reported_listing_id INT NULL,
    report_type ENUM('inappropriate_content', 'spam', 'fraud', 'harassment', 'fake_listing', 'other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_resolved DATETIME NULL,
    resolved_by INT NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(user_id),
    INDEX idx_status (status),
    INDEX idx_reporter (reporter_id),
    INDEX idx_reported_user (reported_user_id),
    INDEX idx_reported_listing (reported_listing_id)
);

-- System notifications
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('message', 'transaction', 'listing', 'system', 'promotion') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    related_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_read DATETIME NULL,
    expiry_date DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_date (date_created)
);

-- Digital literacy workshops
CREATE TABLE workshops (
    workshop_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location_province VARCHAR(100) NOT NULL,
    location_city VARCHAR(100) NOT NULL,
    venue_address TEXT,
    instructor_name VARCHAR(200),
    workshop_date DATETIME NOT NULL,
    duration_hours INT DEFAULT 2,
    max_participants INT DEFAULT 20,
    current_participants INT DEFAULT 0,
    registration_fee DECIMAL(8, 2) DEFAULT 0.00,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_location (location_province, location_city),
    INDEX idx_date (workshop_date),
    INDEX idx_status (status)
);

-- Workshop registrations
CREATE TABLE workshop_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    workshop_id INT NOT NULL,
    user_id INT NOT NULL,
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'no_show') DEFAULT 'registered',
    completion_certificate_issued BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (workshop_id) REFERENCES workshops(workshop_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_workshop_user (workshop_id, user_id),
    INDEX idx_workshop (workshop_id),
    INDEX idx_user (user_id)
);

-- =============================================
-- SYSTEM CONFIGURATION TABLES
-- =============================================

-- System settings
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    data_type ENUM('string', 'integer', 'decimal', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    last_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    modified_by INT NULL,
    FOREIGN KEY (modified_by) REFERENCES users(user_id),
    INDEX idx_key (setting_key)
);

-- Activity logs for auditing
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action_type VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_activity (user_id, date_created),
    INDEX idx_action (action_type),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_date (date_created)
);

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- Active listings with seller info
CREATE VIEW active_listings_view AS
SELECT 
    l.*,
    u.first_name as seller_first_name,
    u.last_name as seller_last_name,
    u.is_verified as seller_verified,
    u.phone_number as seller_phone,
    c.category_name,
    c.category_slug,
    COALESCE(AVG(ur.rating), 0) as seller_average_rating,
    COUNT(ur.rating) as seller_total_ratings,
    (SELECT image_url FROM listing_images li WHERE li.listing_id = l.listing_id AND li.is_primary = TRUE LIMIT 1) as primary_image_url
FROM listings l
JOIN users u ON l.seller_id = u.user_id
JOIN categories c ON l.category_id = c.category_id
LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
WHERE l.status = 'active' 
    AND l.admin_approved = TRUE 
    AND u.is_active = TRUE
    AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
GROUP BY l.listing_id;

-- User dashboard stats
CREATE VIEW user_dashboard_stats AS
SELECT 
    u.user_id,
    u.user_type,
    -- Seller stats
    COUNT(DISTINCT CASE WHEN l.status = 'active' THEN l.listing_id END) as active_listings,
    COUNT(DISTINCT CASE WHEN l.status = 'sold' THEN l.listing_id END) as sold_listings,
    COUNT(DISTINCT CASE WHEN t.seller_id = u.user_id AND t.transaction_status = 'completed' THEN t.transaction_id END) as completed_sales,
    COALESCE(SUM(CASE WHEN t.seller_id = u.user_id AND t.transaction_status = 'completed' THEN t.total_amount END), 0) as total_sales_amount,
    -- Buyer stats
    COUNT(DISTINCT CASE WHEN t.buyer_id = u.user_id AND t.transaction_status = 'completed' THEN t.transaction_id END) as completed_purchases,
    COALESCE(SUM(CASE WHEN t.buyer_id = u.user_id AND t.transaction_status = 'completed' THEN t.total_amount END), 0) as total_purchase_amount,
    -- Rating stats
    COALESCE(AVG(ur.rating), 0) as average_rating,
    COUNT(ur.rating) as total_ratings,
    -- Message stats
    COUNT(DISTINCT CASE WHEN m.recipient_id = u.user_id AND m.is_read = FALSE THEN m.message_id END) as unread_messages
FROM users u
LEFT JOIN listings l ON u.user_id = l.seller_id
LEFT JOIN transactions t ON (u.user_id = t.seller_id OR u.user_id = t.buyer_id)
LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
LEFT JOIN messages m ON u.user_id = m.recipient_id
WHERE u.is_active = TRUE
GROUP BY u.user_id;

-- =============================================
-- INITIAL DATA INSERTION
-- =============================================

-- Insert default categories
INSERT INTO categories (category_name, category_slug, description, icon_class) VALUES
('Fresh Produce', 'produce', 'Fruits, vegetables, and fresh farm products', 'fas fa-apple-alt'),
('Handicrafts', 'handicrafts', 'Handmade items, crafts, and artisan products', 'fas fa-paint-brush'),
('Clothing', 'clothing', 'Clothes, shoes, and fashion accessories', 'fas fa-tshirt'),
('Electronics', 'electronics', 'Phones, computers, and electronic devices', 'fas fa-mobile-alt'),
('Home Goods', 'home', 'Furniture, appliances, and household items', 'fas fa-home'),
('Barter Offers', 'barter', 'Items available for trade or barter', 'fas fa-exchange-alt');

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description, data_type, is_public) VALUES
('platform_name', 'edu C2C Marketplace', 'Platform name', 'string', TRUE),
('commission_rate', '0.05', 'Platform commission rate (5%)', 'decimal', FALSE),
('max_listing_images', '5', 'Maximum images per listing', 'integer', TRUE),
('listing_expiry_days', '30', 'Default listing expiry in days', 'integer', TRUE),
('min_seller_rating', '1', 'Minimum seller rating to remain active', 'decimal', FALSE),
('support_phone', '+27831234567', 'Support phone number', 'string', TRUE),
('support_email', 'support@educ2c.co.za', 'Support email address', 'string', TRUE),
('whatsapp_support', '+27831234567', 'WhatsApp support number', 'string', TRUE);

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================

-- Additional indexes for search performance
CREATE INDEX idx_listings_search ON listings(status, location_province, location_city, price);
CREATE INDEX idx_listings_featured ON listings(featured_until, status) WHERE featured_until IS NOT NULL;
CREATE INDEX idx_transactions_recent ON transactions(date_created DESC, transaction_status);
CREATE INDEX idx_messages_conversations ON messages(sender_id, recipient_id, date_sent);

-- =============================================
-- TRIGGERS FOR DATA INTEGRITY
-- =============================================

-- Update listing modification timestamp
DELIMITER //
CREATE TRIGGER tr_listings_update_modified 
    BEFORE UPDATE ON listings
    FOR EACH ROW
BEGIN
    SET NEW.date_modified = CURRENT_TIMESTAMP;
END//

-- Update favorites count when user adds/removes favorite
CREATE TRIGGER tr_favorites_insert_count
    AFTER INSERT ON user_favorites
    FOR EACH ROW
BEGIN
    UPDATE listings 
    SET favorites_count = favorites_count + 1 
    WHERE listing_id = NEW.listing_id;
END//

CREATE TRIGGER tr_favorites_delete_count
    AFTER DELETE ON user_favorites
    FOR EACH ROW
BEGIN
    UPDATE listings 
    SET favorites_count = favorites_count - 1 
    WHERE listing_id = OLD.listing_id;
END//

-- Update workshop participant count
CREATE TRIGGER tr_workshop_registration_insert
    AFTER INSERT ON workshop_registrations
    FOR EACH ROW
BEGIN
    UPDATE workshops 
    SET current_participants = current_participants + 1 
    WHERE workshop_id = NEW.workshop_id;
END//

CREATE TRIGGER tr_workshop_registration_delete
    AFTER DELETE ON workshop_registrations
    FOR EACH ROW
BEGIN
    UPDATE workshops 
    SET current_participants = current_participants - 1 
    WHERE workshop_id = OLD.workshop_id;
END//

DELIMITER ;

-- =============================================
-- SAMPLE QUERIES FOR TESTING
-- =============================================

/*
-- Find active listings near a location
SELECT * FROM active_listings_view 
WHERE location_province = 'Gauteng' 
    AND location_city = 'Johannesburg'
    AND price BETWEEN 50 AND 500
ORDER BY date_created DESC;

-- Get user dashboard stats
SELECT * FROM user_dashboard_stats WHERE user_id = 1;

-- Search listings by keyword
SELECT * FROM active_listings_view 
WHERE MATCH(title, description) AGAINST('vegetables fresh organic' IN NATURAL LANGUAGE MODE);

-- Get popular categories
SELECT c.category_name, COUNT(l.listing_id) as listing_count
FROM categories c
LEFT JOIN listings l ON c.category_id = l.category_id AND l.status = 'active'
GROUP BY c.category_id
ORDER BY listing_count DESC;
*/