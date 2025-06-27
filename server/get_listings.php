<?php
/**
 * Get Listings API - Edu C2C Marketplace
 * Fixed version with proper error handling
 */

// Prevent any output before headers
ob_start();

// Include database configuration
require_once '../config/database.php';

// Set content type to JSON and headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Clear any previous output
ob_end_clean();

try {
    // Test database connection first
    if (!testDatabaseConnection()) {
        throw new Exception("Database connection failed");
    }
    
    // Get database connection
    $pdo = getDatabase();
    
    // Handle test request
    if (isset($_GET['test'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Server is working',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
    
    // Get request parameters with defaults
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(50, intval($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;
    
    // Search and filter parameters
    $search = trim($_GET['search'] ?? '');
    $categories = array_filter(explode(',', $_GET['category'] ?? ''));
    $location = trim($_GET['location'] ?? '');
    $minPrice = $_GET['minPrice'] ? floatval($_GET['minPrice']) : null;
    $maxPrice = $_GET['maxPrice'] ? floatval($_GET['maxPrice']) : null;
    $conditions = array_filter(explode(',', $_GET['condition'] ?? ''));
    $verified = filter_var($_GET['verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $allowBarter = filter_var($_GET['barter'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $allowOffers = filter_var($_GET['offers'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $sort = $_GET['sort'] ?? 'newest';
    
    // Build the base query - simplified to avoid joins issues
    $baseQuery = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.price,
            l.quantity_available,
            l.allow_offers,
            l.allow_barter,
            l.condition_type,
            l.location_city,
            l.location_province,
            l.location_area,
            l.status,
            l.views_count,
            l.favorites_count,
            l.date_created,
            l.featured_until,
            l.seller_id,
            l.category_id
        FROM listings l
        WHERE l.status = 'active'
            AND l.admin_approved = TRUE
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
    ";
    
    $params = [];
    $whereConditions = [];
    
    // Search condition
    if (!empty($search)) {
        $whereConditions[] = "(l.title LIKE :search OR l.description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Category filter
    if (!empty($categories)) {
        $categoryPlaceholders = [];
        foreach ($categories as $index => $categoryId) {
            if (is_numeric($categoryId)) {
                $placeholder = ":category_$index";
                $categoryPlaceholders[] = $placeholder;
                $params[$placeholder] = intval($categoryId);
            }
        }
        if (!empty($categoryPlaceholders)) {
            $whereConditions[] = "l.category_id IN (" . implode(',', $categoryPlaceholders) . ")";
        }
    }
    
    // Location filter
    if (!empty($location)) {
        $whereConditions[] = "(l.location_city LIKE :location OR l.location_province LIKE :location)";
        $params[':location'] = '%' . $location . '%';
    }
    
    // Price range filter
    if ($minPrice !== null) {
        $whereConditions[] = "l.price >= :min_price";
        $params[':min_price'] = $minPrice;
    }
    if ($maxPrice !== null) {
        $whereConditions[] = "l.price <= :max_price";
        $params[':max_price'] = $maxPrice;
    }
    
    // Condition filter
    if (!empty($conditions)) {
        $conditionPlaceholders = [];
        foreach ($conditions as $index => $condition) {
            $placeholder = ":condition_$index";
            $conditionPlaceholders[] = $placeholder;
            $params[$placeholder] = $condition;
        }
        $whereConditions[] = "l.condition_type IN (" . implode(',', $conditionPlaceholders) . ")";
    }
    
    // Barter available filter
    if ($allowBarter) {
        $whereConditions[] = "l.allow_barter = TRUE";
    }
    
    // Offers accepted filter
    if ($allowOffers) {
        $whereConditions[] = "l.allow_offers = TRUE";
    }
    
    // Add WHERE conditions to query
    if (!empty($whereConditions)) {
        $baseQuery .= " AND " . implode(' AND ', $whereConditions);
    }
    
    // ORDER BY clause
    $orderBy = match($sort) {
        'newest' => 'ORDER BY l.date_created DESC',
        'oldest' => 'ORDER BY l.date_created ASC',
        'price_low' => 'ORDER BY l.price ASC',
        'price_high' => 'ORDER BY l.price DESC',
        'popular' => 'ORDER BY l.views_count DESC, l.favorites_count DESC',
        'distance' => 'ORDER BY l.location_city ASC',
        default => 'ORDER BY l.date_created DESC'
    };
    
    // Count total results
    $countQuery = "SELECT COUNT(*) as total FROM listings l WHERE l.status = 'active' AND l.admin_approved = TRUE AND (l.expiry_date IS NULL OR l.expiry_date > NOW())";
    if (!empty($whereConditions)) {
        $countQuery .= " AND " . implode(' AND ', $whereConditions);
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get listings with pagination
    $listingsQuery = "$baseQuery $orderBy LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($listingsQuery);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get additional data for each listing
    $processedListings = [];
    foreach ($listings as $listing) {
        // Get seller info
        $sellerQuery = "SELECT first_name, last_name, is_verified FROM users WHERE user_id = ?";
        $sellerStmt = $pdo->prepare($sellerQuery);
        $sellerStmt->execute([$listing['seller_id']]);
        $seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get category info
        $categoryQuery = "SELECT category_name, category_slug FROM categories WHERE category_id = ?";
        $categoryStmt = $pdo->prepare($categoryQuery);
        $categoryStmt->execute([$listing['category_id']]);
        $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get primary image
        $imageQuery = "SELECT image_url FROM listing_images WHERE listing_id = ? AND is_primary = TRUE LIMIT 1";
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute([$listing['listing_id']]);
        $image = $imageStmt->fetch(PDO::FETCH_ASSOC);
        
        $processedListings[] = [
            'listing_id' => $listing['listing_id'],
            'title' => $listing['title'],
            'description' => substr($listing['description'], 0, 100) . '...',
            'price' => $listing['price'],
            'condition_type' => $listing['condition_type'],
            'location' => trim($listing['location_city'] . ', ' . $listing['location_province']),
            'image_url' => $image ? $image['image_url'] : getDefaultImage($category['category_slug'] ?? 'default'),
            'category_name' => $category['category_name'] ?? 'Unknown',
            'category_slug' => $category['category_slug'] ?? 'unknown',
            'seller_verified' => (bool)($seller['is_verified'] ?? false),
            'allow_barter' => (bool)$listing['allow_barter'],
            'allow_offers' => (bool)$listing['allow_offers'],
            'time_ago' => timeAgo($listing['date_created']),
            'distance' => calculateDistance($listing) . 'km away',
            'seller_rating' => 4.5, // Placeholder
            'review_count' => 0,
            'views_count' => $listing['views_count'],
            'favorites_count' => $listing['favorites_count'],
            'is_featured' => $listing['featured_until'] && strtotime($listing['featured_until']) > time()
        ];
    }
    
    // Calculate pagination info
    $totalPages = ceil($totalResults / $limit);
    $pagination = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalResults' => (int)$totalResults,
        'pageSize' => $limit,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
    
    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'listings' => $processedListings,
            'pagination' => $pagination,
            'total' => (int)$totalResults,
            'filters_applied' => [
                'search' => $search,
                'categories' => $categories,
                'location' => $location,
                'price_range' => [$minPrice, $maxPrice],
                'conditions' => $conditions,
                'verified' => $verified,
                'barter' => $allowBarter,
                'offers' => $allowOffers,
                'sort' => $sort
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error in get_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    error_log("General error in get_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'GENERAL_ERROR'
    ]);
}

/**
 * Helper Functions
 */

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' weeks ago';
    
    return date('M j, Y', strtotime($datetime));
}

function calculateDistance($listing) {
    // Placeholder distance calculation
    switch ($listing['location_city']) {
        case 'Johannesburg':
            return rand(1, 15);
        case 'Cape Town':
            return rand(5, 25);
        case 'Durban':
            return rand(3, 20);
        case 'Pretoria':
            return rand(2, 18);
        default:
            return rand(5, 30);
    }
}

function getDefaultImage($categorySlug) {
    $defaultImages = [
        'produce' => 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=400',
        'handicrafts' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400',
        'clothing' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
        'electronics' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=400',
        'home' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400',
        'default' => 'https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400'
    ];
    
    return $defaultImages[$categorySlug] ?? $defaultImages['default'];
}
?>