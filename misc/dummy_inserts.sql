-- =============================================
-- DEMO DATA INSERTS FOR Edu C2C MARKETPLACE
-- Comprehensive dummy data for testing and demonstration
-- =============================================

USE edu_c2c_marketplace;

-- =============================================
-- USERS DATA
-- =============================================

-- Insert demo users (buyers and sellers)
INSERT INTO users (first_name, last_name, email, phone_number, password_hash, user_type, is_verified, verification_date, profile_image_url, preferred_language, low_data_mode) VALUES
-- Verified Sellers
('Nomsa', 'Dlamini', 'nomsa.dlamini@gmail.com', '+27831234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 30 DAY, 'https://randomuser.me/api/portraits/women/44.jpg', 'zu', FALSE),
('Thabo', 'Mthembu', 'thabo.mthembu@yahoo.com', '+27821234568', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 25 DAY, 'https://randomuser.me/api/portraits/men/32.jpg', 'en', TRUE),
('Lerato', 'Kgosi', 'lerato.kgosi@hotmail.com', '+27831234569', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 20 DAY, 'https://randomuser.me/api/portraits/women/68.jpg', 'en', FALSE),
('Sipho', 'Ndlovu', 'sipho.ndlovu@gmail.com', '+27821234570', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 15 DAY, 'https://randomuser.me/api/portraits/men/65.jpg', 'zu', TRUE),
('Maria', 'Silva', 'maria.silva@outlook.com', '+27831234571', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 10 DAY, 'https://randomuser.me/api/portraits/women/55.jpg', 'af', FALSE),
('David', 'Kruger', 'david.kruger@gmail.com', '+27821234572', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 12 DAY, 'https://randomuser.me/api/portraits/men/33.jpg', 'af', TRUE),
('Fatima', 'Adams', 'fatima.adams@yahoo.com', '+27831234573', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 8 DAY, 'https://randomuser.me/api/portraits/women/28.jpg', 'en', FALSE),
('James', 'Thompson', 'james.thompson@hotmail.com', '+27821234574', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', TRUE, NOW() - INTERVAL 18 DAY, 'https://randomuser.me/api/portraits/men/45.jpg', 'en', TRUE),
('Zinhle', 'Mapisa', 'zinhle.mapisa@gmail.com', '+27831234575', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', FALSE, NULL, 'https://randomuser.me/api/portraits/women/62.jpg', 'zu', TRUE),
('Peter', 'Van Der Merwe', 'peter.vandermerwe@outlook.com', '+27821234576', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', FALSE, NULL, 'https://randomuser.me/api/portraits/men/12.jpg', 'af', FALSE),

-- Active Buyers
('Sarah', 'Johnson', 'sarah.johnson@gmail.com', '+27831234577', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', FALSE, NULL, 'https://randomuser.me/api/portraits/women/35.jpg', 'en', FALSE),
('Michael', 'Brown', 'michael.brown@yahoo.com', '+27821234578', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', FALSE, NULL, 'https://randomuser.me/api/portraits/men/22.jpg', 'en', TRUE),
('Palesa', 'Mokoena', 'palesa.mokoena@hotmail.com', '+27831234579', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', FALSE, NULL, 'https://randomuser.me/api/portraits/women/32.jpg', 'zu', TRUE),
('Johan', 'Botha', 'johan.botha@gmail.com', '+27821234580', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', FALSE, NULL, 'https://randomuser.me/api/portraits/men/41.jpg', 'af', FALSE),
('Priya', 'Patel', 'priya.patel@outlook.com', '+27831234581', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', FALSE, NULL, 'https://randomuser.me/api/portraits/women/49.jpg', 'en', FALSE),

-- Admin User
('Admin', 'User', 'admin@eduvosc2c.co.za', '+27831234500', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, NOW() - INTERVAL 90 DAY, 'https://randomuser.me/api/portraits/men/32.jpg', 'en', FALSE);

-- =============================================
-- USER LOCATIONS
-- =============================================

INSERT INTO user_locations (user_id, province, city, area_neighborhood, latitude, longitude, is_primary) VALUES
(1, 'Gauteng', 'Johannesburg', 'Soweto', -26.2041, 27.8650, TRUE),
(2, 'Gauteng', 'Johannesburg', 'Alexandra', -26.1023, 28.0924, TRUE),
(3, 'Western Cape', 'Cape Town', 'Khayelitsha', -34.0351, 18.6920, TRUE),
(4, 'KwaZulu-Natal', 'Durban', 'Umlazi', -29.9689, 30.8830, TRUE),
(5, 'Gauteng', 'Pretoria', 'Mamelodi', -25.7081, 28.3540, TRUE),
(6, 'Western Cape', 'Cape Town', 'Mitchells Plain', -34.0364, 18.6271, TRUE),
(7, 'Western Cape', 'Cape Town', 'Athlone', -33.9833, 18.5167, TRUE),
(8, 'Gauteng', 'Johannesburg', 'Sandton', -26.1076, 28.0567, TRUE),
(9, 'KwaZulu-Natal', 'Durban', 'Chatsworth', -29.9378, 30.8653, TRUE),
(10, 'Western Cape', 'Cape Town', 'Bellville', -33.8988, 18.6292, TRUE),
(11, 'Gauteng', 'Johannesburg', 'Bryanston', -26.0685, 28.0167, TRUE),
(12, 'Gauteng', 'Johannesburg', 'Randburg', -26.0939, 28.0061, TRUE),
(13, 'Gauteng', 'Pretoria', 'Centurion', -25.8601, 28.1885, TRUE),
(14, 'Western Cape', 'Cape Town', 'Stellenbosch', -33.9321, 18.8602, TRUE),
(15, 'KwaZulu-Natal', 'Durban', 'Phoenix', -29.7036, 31.0292, TRUE);

-- =============================================
-- CATEGORIES
-- =============================================

-- Categories are already inserted in the schema, but let's add more specific ones
INSERT INTO categories (category_name, category_slug, description, icon_class, parent_category_id) VALUES
('Vegetables', 'vegetables', 'Fresh vegetables and greens', 'fas fa-carrot', 1),
('Fruits', 'fruits', 'Fresh seasonal fruits', 'fas fa-apple-alt', 1),
('Traditional Crafts', 'traditional-crafts', 'African traditional handicrafts', 'fas fa-palette', 2),
('Jewelry', 'jewelry', 'Handmade jewelry and accessories', 'fas fa-gem', 2),
('Mens Clothing', 'mens-clothing', 'Clothing for men', 'fas fa-tshirt', 3),
('Womens Clothing', 'womens-clothing', 'Clothing for women', 'fas fa-female', 3),
('Mobile Phones', 'mobile-phones', 'Smartphones and accessories', 'fas fa-mobile-alt', 4),
('Computers', 'computers', 'Laptops and desktop computers', 'fas fa-laptop', 4),
('Kitchen Items', 'kitchen-items', 'Kitchen utensils and appliances', 'fas fa-utensils', 5),
('Tools & Equipment', 'tools-equipment', 'Tools for trade and construction', 'fas fa-tools', 6);

-- =============================================
-- LISTINGS
-- =============================================

INSERT INTO listings (seller_id, title, description, category_id, price, quantity_available, allow_offers, allow_barter, condition_type, location_province, location_city, location_area, pickup_available, delivery_available, status, views_count, favorites_count, expiry_date, featured_until, admin_approved) VALUES

-- Fresh Produce Listings
(1, 'Fresh Organic Vegetables Bundle', 'Bundle of fresh organic vegetables including spinach, carrots, onions, and tomatoes. Grown locally in Soweto without pesticides. Perfect for families looking for healthy, affordable vegetables.', 7, 85.00, 15, TRUE, TRUE, 'new', 'Gauteng', 'Johannesburg', 'Soweto', TRUE, TRUE, 'active', 247, 18, NOW() + INTERVAL 25 DAY, NOW() + INTERVAL 3 DAY, TRUE),

(1, 'Traditional Spinach (Morogo)', 'Fresh traditional African spinach, hand-picked this morning. Rich in nutrients and perfect for traditional dishes. Available in 1kg bundles.', 7, 25.00, 30, TRUE, TRUE, 'new', 'Gauteng', 'Johannesburg', 'Soweto', TRUE, FALSE, 'active', 156, 12, NOW() + INTERVAL 28 DAY, NULL, TRUE),

(4, 'Sweet Mangoes - Durban Special', 'Deliciously sweet mangoes from KwaZulu-Natal. Perfect ripeness, ready to eat. These are premium quality mangoes with no bruising.', 8, 45.00, 20, TRUE, FALSE, 'new', 'KwaZulu-Natal', 'Durban', 'Umlazi', TRUE, TRUE, 'active', 189, 23, NOW() + INTERVAL 20 DAY, NOW() + INTERVAL 2 DAY, TRUE),

(2, 'Fresh Beetroot and Carrots', 'Locally grown beetroot and carrots. Perfect for juicing or cooking. Harvested yesterday, very fresh and crunchy.', 7, 35.00, 25, TRUE, TRUE, 'new', 'Gauteng', 'Johannesburg', 'Alexandra', TRUE, FALSE, 'active', 78, 9, NOW() + INTERVAL 22 DAY, NULL, TRUE),

-- Handicrafts & Traditional Items
(3, 'Handwoven Zulu Baskets', 'Beautiful traditional Zulu baskets handwoven by local artisans. Perfect for home decoration or storage. Each basket is unique and made with natural materials.', 9, 150.00, 8, TRUE, TRUE, 'new', 'Western Cape', 'Cape Town', 'Khayelitsha', TRUE, TRUE, 'active', 234, 31, NOW() + INTERVAL 30 DAY, NOW() + INTERVAL 5 DAY, TRUE),

(5, 'Beaded Jewelry Set', 'Colorful traditional beaded jewelry including necklace, bracelet, and earrings. Made with authentic African beads and traditional patterns.', 10, 120.00, 12, TRUE, FALSE, 'new', 'Gauteng', 'Pretoria', 'Mamelodi', TRUE, TRUE, 'active', 167, 19, NOW() + INTERVAL 27 DAY, NULL, TRUE),

(7, 'Hand-carved Wooden Bowls', 'Set of 3 hand-carved wooden bowls made from indigenous South African wood. Perfect for serving traditional food or as decorative pieces.', 9, 180.00, 5, TRUE, TRUE, 'new', 'Western Cape', 'Cape Town', 'Athlone', TRUE, FALSE, 'active', 98, 14, NOW() + INTERVAL 29 DAY, NULL, TRUE),

(6, 'Traditional Pottery Collection', 'Beautiful collection of traditional African pottery. Includes decorative pots and functional pieces. All handmade using traditional techniques.', 9, 250.00, 6, TRUE, TRUE, 'new', 'Western Cape', 'Cape Town', 'Mitchells Plain', TRUE, TRUE, 'active', 145, 22, NOW() + INTERVAL 26 DAY, NOW() + INTERVAL 1 DAY, TRUE),

-- Clothing Items
(2, 'Traditional Shweshwe Dress', 'Beautiful traditional Shweshwe dress in classic blue and white pattern. Made locally, perfect for special occasions. Available in medium size.', 11, 280.00, 3, TRUE, FALSE, 'like_new', 'Gauteng', 'Johannesburg', 'Alexandra', TRUE, TRUE, 'active', 312, 45, NOW() + INTERVAL 24 DAY, NOW() + INTERVAL 4 DAY, TRUE),

(8, 'Mens Traditional Shirt', 'Smart casual traditional African print shirt for men. High quality fabric, comfortable fit. Perfect for office or casual wear.', 10, 195.00, 7, TRUE, FALSE, 'new', 'Gauteng', 'Johannesburg', 'Sandton', TRUE, TRUE, 'active', 89, 11, NOW() + INTERVAL 28 DAY, NULL, TRUE),

(9, 'Handmade Leather Sandals', 'Comfortable handmade leather sandals. Durable and stylish, perfect for everyday wear. Made by local craftsman with quality leather.', 11, 165.00, 10, TRUE, TRUE, 'new', 'KwaZulu-Natal', 'Durban', 'Chatsworth', TRUE, FALSE, 'active', 134, 16, NOW() + INTERVAL 25 DAY, NULL, TRUE),

-- Electronics
(8, 'Samsung Galaxy A04 - Good Condition', 'Samsung Galaxy A04 smartphone in good working condition. Minor scratches but all functions work perfectly. Includes charger and screen protector.', 12, 1299.00, 1, TRUE, FALSE, 'good', 'Gauteng', 'Johannesburg', 'Sandton', TRUE, TRUE, 'active', 456, 67, NOW() + INTERVAL 21 DAY, NOW() + INTERVAL 2 DAY, TRUE),

(10, 'Refurbished Laptop - HP', 'Refurbished HP laptop perfect for students or basic office work. Windows 11, 8GB RAM, 256GB SSD. Very good condition with warranty.', 13, 3500.00, 2, TRUE, FALSE, 'good', 'Western Cape', 'Cape Town', 'Bellville', TRUE, TRUE, 'active', 234, 28, NOW() + INTERVAL 19 DAY, NULL, TRUE),

(4, 'Phone Accessories Bundle', 'Bundle of phone accessories including power bank, earphones, phone cases, and cables. Compatible with most smartphones.', 12, 125.00, 15, TRUE, FALSE, 'new', 'KwaZulu-Natal', 'Durban', 'Umlazi', TRUE, FALSE, 'active', 67, 8, NOW() + INTERVAL 23 DAY, NULL, TRUE),

-- Home Goods
(5, 'Traditional Cooking Pots', 'Set of traditional clay cooking pots. Perfect for cooking traditional meals. These pots enhance the flavor of food naturally.', 14, 195.00, 8, TRUE, TRUE, 'new', 'Gauteng', 'Pretoria', 'Mamelodi', TRUE, TRUE, 'active', 123, 15, NOW() + INTERVAL 27 DAY, NULL, TRUE),

(6, 'Handmade Kitchen Utensils', 'Set of handmade wooden kitchen utensils including spoons, spatulas, and serving dishes. Made from sustainable local wood.', 14, 85.00, 12, TRUE, TRUE, 'new', 'Western Cape', 'Cape Town', 'Mitchells Plain', TRUE, FALSE, 'active', 94, 11, NOW() + INTERVAL 26 DAY, NULL, TRUE),

(7, 'Solar LED Lanterns', 'Solar powered LED lanterns perfect for homes without electricity or camping. Long-lasting battery, weather resistant.', 5, 145.00, 20, TRUE, FALSE, 'new', 'Western Cape', 'Cape Town', 'Athlone', TRUE, TRUE, 'active', 178, 21, NOW() + INTERVAL 29 DAY, NOW() + INTERVAL 1 DAY, TRUE),

-- Tools & Equipment
(3, 'Garden Tools Set', 'Complete set of garden tools including spade, rake, hoe, and hand tools. Perfect for small gardens and vegetable growing.', 15, 220.00, 6, TRUE, TRUE, 'good', 'Western Cape', 'Cape Town', 'Khayelitsha', TRUE, FALSE, 'active', 145, 18, NOW() + INTERVAL 24 DAY, NULL, TRUE),

(9, 'Sewing Machine - Singer', 'Singer sewing machine in excellent working condition. Perfect for tailoring business or home use. Includes instruction manual.', 15, 850.00, 1, TRUE, FALSE, 'good', 'KwaZulu-Natal', 'Durban', 'Chatsworth', TRUE, TRUE, 'active', 267, 34, NOW() + INTERVAL 22 DAY, NOW() + INTERVAL 3 DAY, TRUE),

-- Some sold listings for transaction history
(1, 'Fresh Tomatoes - 5kg', 'Fresh, ripe tomatoes perfect for cooking. Locally grown and pesticide-free.', 7, 65.00, 0, FALSE, FALSE, 'new', 'Gauteng', 'Johannesburg', 'Soweto', TRUE, FALSE, 'sold', 89, 7, NOW() + INTERVAL 15 DAY, NULL, TRUE),

(2, 'Traditional Doek/Headwrap', 'Beautiful traditional African headwrap in vibrant colors. Perfect for special occasions.', 11, 45.00, 0, FALSE, FALSE, 'new', 'Gauteng', 'Johannesburg', 'Alexandra', TRUE, FALSE, 'sold', 134, 12, NOW() + INTERVAL 18 DAY, NULL, TRUE);

-- =============================================
-- LISTING IMAGES
-- =============================================

INSERT INTO listing_images (listing_id, image_url, alt_text, display_order, is_primary, file_size_kb, image_width, image_height) VALUES
-- Vegetables Bundle
(1, 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800', 'Fresh organic vegetables bundle', 0, TRUE, 245, 800, 600),
(1, 'https://images.unsplash.com/photo-1518843875459-f738682238a6?w=800', 'Close up of fresh vegetables', 1, FALSE, 198, 800, 600),

-- Traditional Spinach
(2, 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=800', 'Fresh morogo (traditional spinach)', 0, TRUE, 167, 800, 600),

-- Sweet Mangoes
(3, 'https://images.unsplash.com/photo-1553279742-3c8fb1ab8e15?w=800', 'Sweet ripe mangoes from Durban', 0, TRUE, 223, 800, 600),
(3, 'https://images.unsplash.com/photo-1565450384295-1cc75c0c2a0d?w=800', 'Mango close-up showing quality', 1, FALSE, 189, 800, 600),

-- Beetroot and Carrots
(4, 'https://images.unsplash.com/photo-1445282768818-728615cc910a?w=800', 'Fresh beetroot and carrots', 0, TRUE, 201, 800, 600),

-- Zulu Baskets
(5, 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800', 'Traditional Zulu woven baskets', 0, TRUE, 312, 800, 600),
(5, 'https://images.unsplash.com/photo-1616122351915-0077141f36bb?w=800', 'Detail of basket weaving pattern', 1, FALSE, 267, 800, 600),

-- Beaded Jewelry
(6, 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=800', 'Traditional beaded jewelry set', 0, TRUE, 234, 800, 600),

-- Wooden Bowls
(7, 'https://images.unsplash.com/photo-1610701596007-bc2c4cf8e89a?w=800', 'Hand-carved wooden bowls', 0, TRUE, 278, 800, 600),

-- Traditional Pottery
(8, 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=800', 'Traditional African pottery', 0, TRUE, 298, 800, 600),

-- Shweshwe Dress
(9, 'https://images.unsplash.com/photo-1617038220319-276d3cfab638?w=800', 'Traditional Shweshwe dress', 0, TRUE, 345, 800, 600),

-- Traditional Shirt
(10, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=800', 'African print mens shirt', 0, TRUE, 287, 800, 600),

-- Leather Sandals
(11, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800', 'Handmade leather sandals', 0, TRUE, 213, 800, 600),

-- Samsung Phone
(12, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800', 'Samsung Galaxy A04 smartphone', 0, TRUE, 189, 800, 600),

-- Laptop
(13, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800', 'Refurbished HP laptop', 0, TRUE, 234, 800, 600),

-- Phone Accessories
(14, 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800', 'Phone accessories bundle', 0, TRUE, 198, 800, 600),

-- Cooking Pots
(15, 'https://images.unsplash.com/photo-1584269600519-112e9196a5c4?w=800', 'Traditional clay cooking pots', 0, TRUE, 267, 800, 600),

-- Kitchen Utensils
(16, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800', 'Handmade wooden kitchen utensils', 0, TRUE, 223, 800, 600),

-- Solar Lanterns
(17, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=800', 'Solar LED lanterns', 0, TRUE, 178, 800, 600),

-- Garden Tools
(18, 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=800', 'Complete garden tools set', 0, TRUE, 245, 800, 600),

-- Sewing Machine
(19, 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800', 'Singer sewing machine', 0, TRUE, 298, 800, 600);

-- =============================================
-- LISTING DELIVERY OPTIONS
-- =============================================

INSERT INTO listing_delivery_options (listing_id, delivery_type, max_distance_km, delivery_fee, estimated_days, description) VALUES
-- Delivery options for various listings
(1, 'pickup', NULL, 0.00, NULL, 'Free pickup from Soweto market'),
(1, 'local_delivery', 10, 25.00, 1, 'Same day delivery within 10km'),

(3, 'pickup', NULL, 0.00, NULL, 'Pickup from Umlazi'),
(3, 'local_delivery', 15, 35.00, 2, 'Delivery within Durban metro'),

(5, 'pickup', NULL, 0.00, NULL, 'Pickup from workshop in Khayelitsha'),
(5, 'courier', NULL, 95.00, 3, 'Nationwide courier delivery'),

(9, 'pickup', NULL, 0.00, NULL, 'Pickup from Alexandra'),
(9, 'local_delivery', 20, 45.00, 2, 'Delivery within Johannesburg'),

(12, 'pickup', NULL, 0.00, NULL, 'Pickup from Sandton'),
(12, 'courier', NULL, 75.00, 2, 'Secure courier delivery with insurance'),

(13, 'pickup', NULL, 0.00, NULL, 'Pickup from Bellville'),
(13, 'local_delivery', 25, 50.00, 1, 'Cape Town metro delivery'),

(17, 'pickup', NULL, 0.00, NULL, 'Pickup from Athlone'),
(17, 'local_delivery', 15, 30.00, 1, 'Local Cape Town delivery'),

(19, 'pickup', NULL, 0.00, NULL, 'Pickup from Chatsworth'),
(19, 'local_delivery', 20, 65.00, 2, 'Durban and surrounding areas');

-- =============================================
-- USER FAVORITES
-- =============================================

INSERT INTO user_favorites (user_id, listing_id) VALUES
(11, 1), (11, 5), (11, 9), (11, 12),
(12, 3), (12, 6), (12, 13), (12, 17),
(13, 1), (13, 2), (13, 8), (13, 15),
(14, 5), (14, 7), (14, 10), (14, 19),
(15, 3), (15, 4), (15, 11), (15, 16);

-- =============================================
-- TRANSACTIONS
-- =============================================

INSERT INTO transactions (listing_id, buyer_id, seller_id, transaction_type, quantity, total_amount, delivery_fee, payment_method, payment_status, transaction_status, delivery_method, delivery_address, delivery_status, completion_date, notes) VALUES

-- Completed transactions
(20, 12, 1, 'purchase', 2, 130.00, 25.00, 'mobile_money', 'paid', 'completed', 'delivery', '123 Main Street, Alexandra, Johannesburg', 'delivered', NOW() - INTERVAL 2 DAY, 'Customer very satisfied with quality'),

(21, 13, 2, 'purchase', 1, 45.00, 0.00, 'cash', 'paid', 'completed', 'pickup', NULL, NULL, NOW() - INTERVAL 1 DAY, 'Cash on pickup, smooth transaction'),

(3, 11, 4, 'purchase', 3, 135.00, 35.00, 'mobile_money', 'paid', 'completed', 'delivery', '456 Oak Road, Randburg, Johannesburg', 'delivered', NOW() - INTERVAL 3 DAY, 'Repeat customer, excellent mangoes'),

(6, 14, 5, 'purchase', 1, 120.00, 0.00, 'bank_transfer', 'paid', 'completed', 'pickup', NULL, NULL, NOW() - INTERVAL 5 DAY, 'Beautiful jewelry, exactly as described'),

(12, 15, 8, 'purchase', 1, 1374.00, 75.00, 'escrow', 'paid', 'completed', 'courier', '789 Pine Avenue, Phoenix, Durban', 'delivered', NOW() - INTERVAL 7 DAY, 'Phone in excellent condition, fast delivery'),

-- Pending transactions
(13, 11, 10, 'purchase', 1, 3550.00, 50.00, 'escrow', 'paid', 'in_progress', 'delivery', '321 Elm Street, Bryanston, Johannesburg', 'in_transit', NULL, 'Laptop being delivered today'),

(17, 12, 7, 'purchase', 2, 320.00, 30.00, 'mobile_money', 'paid', 'confirmed', 'delivery', '654 Birch Lane, Randburg, Johannesburg', 'pending', NULL, 'Order confirmed, preparing for delivery'),

-- Barter transactions
(5, 13, 3, 'barter', 1, 0.00, 0.00, 'barter', 'paid', 'completed', 'pickup', NULL, NULL, NOW() - INTERVAL 4 DAY, 'Traded basket for vegetables - both parties happy'),

(7, 14, 6, 'barter', 1, 0.00, 0.00, 'barter', 'paid', 'completed', 'pickup', NULL, NULL, NOW() - INTERVAL 6 DAY, 'Exchanged wooden bowls for pottery pieces'),

-- Failed transaction
(14, 15, 4, 'purchase', 5, 125.00, 0.00, 'mobile_money', 'failed', 'cancelled', 'pickup', NULL, NULL, NULL, 'Payment failed due to insufficient funds');

-- =============================================
-- BARTER OFFERS
-- =============================================

INSERT INTO barter_offers (listing_id, offerer_id, offered_item_description, offered_item_value, offer_status, seller_response, date_created, expiry_date) VALUES

(8, 11, 'Traditional wooden drums (set of 2) - handmade, excellent sound quality', 280.00, 'accepted', 'Love the drums! Perfect trade for my pottery collection.', NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 5 DAY),

(16, 12, 'Fresh vegetables (weekly supply for 1 month) - mixed seasonal vegetables', 85.00, 'pending', NULL, NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 6 DAY),

(11, 13, 'Handmade leather bag - genuine leather, very durable', 165.00, 'countered', 'Nice bag, but could you add R20 cash to make it fair?', NOW() - INTERVAL 3 DAY, NOW() + INTERVAL 4 DAY),

(18, 14, 'Solar radio with USB charging - perfect for outdoor work', 220.00, 'rejected', 'Thanks but I need the cash for materials. Good luck!', NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 2 DAY),

(15, 15, 'Traditional herbs and spices collection - organic, medicinal', 195.00, 'pending', NULL, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 6 DAY);

-- =============================================
-- MESSAGES
-- =============================================

INSERT INTO messages (sender_id, recipient_id, listing_id, transaction_id, message_content, message_type, is_read, date_sent, date_read) VALUES

-- Messages about active listings
(11, 1, 1, NULL, 'Hi! Are these vegetables still available? I need them for this weekend.', 'text', TRUE, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR),
(1, 11, 1, NULL, 'Yes, they are available! Fresh harvest from yesterday. When would you like to collect?', 'text', FALSE, NOW() - INTERVAL 1 HOUR, NULL),

(12, 3, 5, NULL, 'Beautiful baskets! Can you do R130 for one basket?', 'text', TRUE, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 2 HOUR),
(3, 12, 5, NULL, 'Thank you! The price is firm at R150 as these take 3 days to make by hand.', 'text', TRUE, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR),
(12, 3, 5, NULL, 'I understand. I will take one at R150. When can I collect?', 'text', FALSE, NOW() - INTERVAL 1 HOUR, NULL),

-- Transaction-related messages
(12, 1, 20, 1, 'Thank you for the quick delivery! The vegetables were very fresh.', 'text', TRUE, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 23 HOUR),
(1, 12, 20, 1, 'So happy you enjoyed them! Thank you for supporting local farming.', 'text', TRUE, NOW() - INTERVAL 23 HOUR, NOW() - INTERVAL 22 HOUR),

(15, 8, 12, 5, 'Phone received and working perfectly! Excellent condition as described.', 'text', TRUE, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),
(8, 15, 12, 5, 'Great to hear! Enjoy your new phone and thank you for the smooth transaction.', 'text', TRUE, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY),

-- Barter-related messages
(11, 6, 8, NULL, 'I have some beautiful traditional drums that would go perfectly with your pottery. Interested in a trade?', 'text', TRUE, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(6, 11, 8, NULL, 'That sounds wonderful! Can you send me photos of the drums?', 'text', TRUE, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),

-- Workshop inquiry
(13, 16, NULL, NULL, 'Hi! I saw information about digital literacy workshops. When is the next session in Pretoria?', 'text', FALSE, NOW() - INTERVAL 4 HOUR, NULL);

-- =============================================
-- USER RATINGS
-- =============================================

INSERT INTO user_ratings (rated_user_id, rating_user_id, transaction_id, rating, review_text, date_created) VALUES

-- Ratings for sellers (buyers rating sellers)
(1, 12, 1, 5, 'Excellent vegetables, very fresh and good value. Nomsa is a trustworthy seller!', NOW() - INTERVAL 1 DAY),
(2, 13, 2, 4, 'Good quality doek, exactly as pictured. Fast pickup service.', NOW() - INTERVAL 18 HOUR),
(4, 11, 3, 5, 'Amazing mangoes! So sweet and perfectly ripe. Will definitely buy again.', NOW() - INTERVAL 2 DAY),
(5, 14, 4, 5, 'Beautiful beadwork, traditional and authentic. Maria is very skilled.', NOW() - INTERVAL 4 DAY),
(8, 15, 5, 4, 'Phone was exactly as described. Good communication and fast delivery.', NOW() - INTERVAL 6 DAY),

-- Ratings for buyers (sellers rating buyers)
(12, 1, 1, 5, 'Polite buyer, quick payment, easy to work with.', NOW() - INTERVAL 1 DAY),
(13, 2, 2, 5, 'Respectful and understanding buyer. Pleasure to trade with.', NOW() - INTERVAL 18 HOUR),
(11, 4, 3, 5, 'Great buyer, paid immediately and very friendly.', NOW() - INTERVAL 2 DAY),
(14, 5, 4, 5, 'Sarah appreciated the craftsmanship and paid promptly.', NOW() - INTERVAL 4 DAY),
(15, 8, 5, 5, 'Smooth transaction, would sell to Priya again.', NOW() - INTERVAL 6 DAY),

-- Additional ratings without transaction reference (general seller ratings)
(1, 14, NULL, 5, 'Always provides quality produce. Highly recommended local farmer.', NOW() - INTERVAL 3 DAY),
(3, 12, NULL, 5, 'Beautiful traditional crafts, authentic and well-made.', NOW() - INTERVAL 5 DAY),
(6, 11, NULL, 4, 'Good quality pottery, fair prices and friendly service.', NOW() - INTERVAL 7 DAY);

-- =============================================
-- NOTIFICATIONS
-- =============================================

INSERT INTO notifications (user_id, type, title, content, related_id, is_read, expiry_date) VALUES

-- New message notifications
(1, 'message', 'New Message', 'You have a new message about your vegetables listing', 1, FALSE, NOW() + INTERVAL 7 DAY),
(3, 'message', 'New Message', 'Customer interested in your baskets', 5, TRUE, NOW() + INTERVAL 7 DAY),

-- Transaction notifications
(12, 'transaction', 'Order Delivered', 'Your vegetable order has been delivered successfully', 1, TRUE, NOW() + INTERVAL 3 DAY),
(11, 'transaction', 'Payment Received', 'Payment received for mango order', 3, TRUE, NOW() + INTERVAL 3 DAY),

-- System notifications
(1, 'system', 'Workshop Available', 'New digital literacy workshop in Soweto - Register now!', NULL, FALSE, NOW() + INTERVAL 14 DAY),
(2, 'system', 'Verification Reminder', 'Complete your seller verification to increase trust', NULL, FALSE, NOW() + INTERVAL 30 DAY),

-- Promotion notifications
(11, 'promotion', 'Featured Listing Discount', 'Get 20% off featured listings this week!', NULL, FALSE, NOW() + INTERVAL 5 DAY),
(12, 'promotion', 'Mobile Money Cashback', 'Get 5% cashback on mobile money payments', NULL, TRUE, NOW() + INTERVAL 10 DAY);

-- =============================================
-- REPORTS
-- =============================================

INSERT INTO reports (reporter_id, reported_listing_id, report_type, description, status, date_created) VALUES

(12, 14, 'fake_listing', 'This phone listing seems too good to be true. Price is suspiciously low for the model described.', 'investigating', NOW() - INTERVAL 2 DAY),

(13, NULL, 'spam', 'User keeps sending unsolicited messages about products not related to my listings.', 'resolved', NOW() - INTERVAL 5 DAY),

(14, 16, 'inappropriate_content', 'Listing description contains misleading information about the materials used.', 'pending', NOW() - INTERVAL 1 DAY);

-- =============================================
-- WORKSHOPS
-- =============================================

INSERT INTO workshops (title, description, location_province, location_city, venue_address, instructor_name, workshop_date, duration_hours, max_participants, current_participants, registration_fee, status) VALUES

('Digital Literacy for Informal Traders', 'Learn how to use smartphones and apps for your business. Topics include online selling, mobile payments, and customer communication.', 'Gauteng', 'Johannesburg', 'Soweto Community Center, Vilakazi Street', 'Thabo Mokwena', NOW() + INTERVAL 3 DAY + INTERVAL 14 HOUR, 3, 25, 18, 0.00, 'scheduled'),

('Mobile Money and Digital Payments', 'Understanding mobile money services, setting up accounts, and safely conducting digital transactions.', 'Western Cape', 'Cape Town', 'Khayelitsha Skills Development Center', 'Sarah Williams', NOW() + INTERVAL 5 DAY + INTERVAL 10 HOUR, 2, 20, 12, 0.00, 'scheduled'),

('Online Photography for Products', 'Learn to take attractive photos of your products using just your smartphone. Lighting, angles, and editing basics.', 'KwaZulu-Natal', 'Durban', 'Umlazi Business Hub', 'Nomsa Dube', NOW() + INTERVAL 7 DAY + INTERVAL 13 HOUR, 2, 15, 8, 0.00, 'scheduled'),

('Growing Your Customer Base Online', 'Strategies for reaching more customers through social media and online platforms. Build your digital presence.', 'Gauteng', 'Pretoria', 'Mamelodi Development Center', 'Peter van Zyl', NOW() - INTERVAL 2 DAY + INTERVAL 9 HOUR, 3, 30, 30, 0.00, 'completed'),

('Basic Bookkeeping for Small Business', 'Simple methods to track income, expenses, and profits. Using basic apps and tools for financial management.', 'Western Cape', 'Cape Town', 'Mitchells Plain Community Hall', 'Fatima Adams', NOW() - INTERVAL 1 WEEK + INTERVAL 11 HOUR, 4, 25, 23, 0.00, 'completed');

-- =============================================
-- WORKSHOP REGISTRATIONS
-- =============================================

INSERT INTO workshop_registrations (workshop_id, user_id, attendance_status, completion_certificate_issued) VALUES

-- Completed workshop registrations
(4, 1, 'attended', TRUE),
(4, 2, 'attended', TRUE),
(4, 3, 'attended', TRUE),
(4, 5, 'no_show', FALSE),
(4, 7, 'attended', TRUE),

(5, 3, 'attended', TRUE),
(5, 6, 'attended', TRUE),
(5, 7, 'attended', TRUE),
(5, 10, 'attended', TRUE),

-- Upcoming workshop registrations
(1, 1, 'registered', FALSE),
(1, 2, 'registered', FALSE),
(1, 9, 'registered', FALSE),
(1, 10, 'registered', FALSE),

(2, 3, 'registered', FALSE),
(2, 6, 'registered', FALSE),
(2, 7, 'registered', FALSE),

(3, 4, 'registered', FALSE),
(3, 9, 'registered', FALSE);

-- =============================================
-- SYSTEM SETTINGS (Additional)
-- =============================================

INSERT INTO system_settings (setting_key, setting_value, description, data_type, is_public, modified_by) VALUES
('marketplace_status', 'active', 'Current marketplace operational status', 'string', TRUE, 16),
('featured_listing_fee', '25.00', 'Fee for featuring a listing', 'decimal', TRUE, 16),
('max_barter_value_ratio', '1.5', 'Maximum ratio between barter items', 'decimal', FALSE, 16),
('workshop_booking_advance_days', '14', 'Days in advance workshops can be booked', 'integer', TRUE, 16),
('low_data_mode_enabled', 'true', 'Enable low data mode globally', 'boolean', TRUE, 16),
('multilingual_support', 'true', 'Enable multi-language interface', 'boolean', TRUE, 16),
('verification_required_amount', '1000.00', 'Transaction amount requiring verification', 'decimal', FALSE, 16),
('mobile_money_providers', '["MTN Mobile Money", "Vodacom VodaPay", "FNB eWallet", "Standard Bank SnapScan"]', 'Supported mobile money providers', 'json', TRUE, 16);

-- =============================================
-- ACTIVITY LOGS
-- =============================================

INSERT INTO activity_logs (user_id, action_type, table_name, record_id, ip_address, user_agent, date_created) VALUES

(1, 'listing_created', 'listings', 1, '196.213.45.123', 'Mozilla/5.0 (Linux; Android 10; SM-A205F) Mobile App', NOW() - INTERVAL 10 DAY),
(1, 'listing_created', 'listings', 2, '196.213.45.123', 'Mozilla/5.0 (Linux; Android 10; SM-A205F) Mobile App', NOW() - INTERVAL 8 DAY),

(12, 'transaction_completed', 'transactions', 1, '41.185.29.67', 'Mozilla/5.0 (Linux; Android 11; Pixel 3) Mobile App', NOW() - INTERVAL 2 DAY),
(13, 'user_registered', 'users', 13, '105.186.45.89', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6) Mobile App', NOW() - INTERVAL 15 DAY),

(3, 'workshop_attended', 'workshop_registrations', 1, '41.74.182.156', 'Mozilla/5.0 (Linux; Android 9; SM-J730F) Mobile App', NOW() - INTERVAL 3 DAY),

(16, 'system_backup', 'system_settings', NULL, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Admin Panel', NOW() - INTERVAL 1 DAY),
(16, 'user_verified', 'users', 8, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Admin Panel', NOW() - INTERVAL 5 DAY);

-- =============================================
-- UPDATE LISTING STATS BASED ON ACTIVITY
-- =============================================

-- Update favorites count for listings based on user_favorites
UPDATE listings SET favorites_count = (
    SELECT COUNT(*) FROM user_favorites WHERE listing_id = listings.listing_id
) WHERE listing_id IN (SELECT DISTINCT listing_id FROM user_favorites);

-- Update some view counts to make them more realistic
UPDATE listings SET views_count = FLOOR(RAND() * 500) + 50 WHERE status = 'active';

-- =============================================
-- ADDITIONAL DEMO SCENARIOS
-- =============================================

-- Add some expired listings
INSERT INTO listings (seller_id, title, description, category_id, price, quantity_available, allow_offers, allow_barter, condition_type, location_province, location_city, location_area, pickup_available, delivery_available, status, views_count, favorites_count, expiry_date, admin_approved) VALUES

(9, 'Fresh Bread - Daily Baked', 'Fresh bread baked daily in traditional wood oven. Available every morning at 6 AM.', 1, 15.00, 0, FALSE, FALSE, 'new', 'KwaZulu-Natal', 'Durban', 'Chatsworth', TRUE, FALSE, 'expired', 45, 3, NOW() - INTERVAL 2 DAY, TRUE),

(10, 'Computer Repair Services', 'Professional computer and laptop repair services. Free diagnosis, competitive prices.', 4, 150.00, 1, TRUE, FALSE, 'new', 'Western Cape', 'Cape Town', 'Bellville', TRUE, TRUE, 'expired', 67, 8, NOW() - INTERVAL 1 DAY, TRUE);

-- Add some pending approval listings
INSERT INTO listings (seller_id, title, description, category_id, price, quantity_available, allow_offers, allow_barter, condition_type, location_province, location_city, location_area, pickup_available, delivery_available, status, views_count, favorites_count, expiry_date, admin_approved) VALUES

(9, 'Traditional Medicine Herbs', 'Collection of traditional healing herbs and plants. Organically grown and sustainably harvested.', 1, 85.00, 10, TRUE, TRUE, 'new', 'KwaZulu-Natal', 'Durban', 'Chatsworth', TRUE, FALSE, 'active', 12, 1, NOW() + INTERVAL 30 DAY, FALSE),

(10, 'Homemade Rusks and Biscuits', 'Traditional South African rusks and biscuits made from family recipe. Perfect with coffee or tea.', 1, 35.00, 20, TRUE, FALSE, 'new', 'Western Cape', 'Cape Town', 'Bellville', TRUE, TRUE, 'active', 8, 0, NOW() + INTERVAL 28 DAY, FALSE);

-- =============================================
-- SUMMARY OF DEMO DATA
-- =============================================

/*
DEMO DATA SUMMARY:
==================

USERS: 16 total
- 10 sellers (7 verified, 3 unverified)
- 5 buyers
- 1 admin

LISTINGS: 25 total
- 19 active listings
- 2 sold listings  
- 2 expired listings
- 2 pending approval

CATEGORIES: 15 total
- 6 main categories
- 9 subcategories

TRANSACTIONS: 11 total
- 6 completed purchases
- 1 in-progress
- 1 confirmed/pending
- 2 completed barter trades
- 1 failed transaction

FEATURES DEMONSTRATED:
- Multi-language users (EN, AF, ZU)
- Low data mode preferences
- Verified and unverified sellers
- Various payment methods (cash, mobile money, bank transfer, escrow, barter)
- Different delivery options
- User ratings and reviews
- Messaging between users
- Barter offers and trades
- Workshop registrations
- Favorites/saved listings
- Geographic distribution across SA
- Digital literacy impact
- Admin activity logging
- System notifications
- Content reporting

LOCATIONS COVERED:
- Gauteng: Johannesburg (Soweto, Alexandra, Sandton, Randburg, Bryanston), Pretoria (Mamelodi, Centurion)
- Western Cape: Cape Town (Khayelitsha, Mitchells Plain, Athlone, Bellville, Stellenbosch)
- KwaZulu-Natal: Durban (Umlazi, Chatsworth, Phoenix)

This demo data represents a realistic C2C marketplace focused on South Africa's informal economy with authentic scenarios, user behavior patterns, and platform features specifically designed for the target market.
*/