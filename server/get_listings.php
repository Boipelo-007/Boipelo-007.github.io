<?php
/**
 * Get Listings with Filtering and Pagination
 * Edu C2C Marketplace
 */

// Include database configuration
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get database connection
    $pdo = getDatabase();
    
    // Get request parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(50, intval($_GET['limit'] ?? 12))); // Max 50 items per page
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
    
    // Build the base query
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
            
            -- Seller information
            u.first_name as seller_first_name,
            u.last_name as seller_last_name,
            u.is_verified as seller_verified,
            u.profile_image_url as seller_avatar,
            
            -- Category information
            c.category_name,
            c.category_slug,
            
            -- Primary image
            li.image_url as primary_image,
            li.alt_text as image_alt,
            
            -- Seller rating (average)
            COALESCE(AVG(ur.rating), 0) as seller_rating,
            COUNT(DISTINCT ur.rating_id) as seller_review_count
            
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
        LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
    ";
    
    // Build WHERE conditions
    $whereConditions = [
        "l.status = 'active'",
        "l.admin_approved = TRUE",
        "u.is_active = TRUE",
        "(l.expiry_date IS NULL OR l.expiry_date > NOW())"
    ];
    
    $params = [];
    
    // Search condition
    if (!empty($search)) {
        $whereConditions[] = "(l.title LIKE :search OR l.description LIKE :search OR c.category_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Category filter
    if (!empty($categories)) {
        $categoryPlaceholders = [];
        foreach ($categories as $index => $categoryId) {
            $placeholder = ":category_$index";
            $categoryPlaceholders[] = $placeholder;
            $params[$placeholder] = intval($categoryId);
        }
        $whereConditions[] = "l.category_id IN (" . implode(',', $categoryPlaceholders) . ")";
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
    
    // Verified seller filter
    if ($verified) {
        $whereConditions[] = "u.is_verified = TRUE";
    }
    
    // Barter available filter
    if ($allowBarter) {
        $whereConditions[] = "l.allow_barter = TRUE";
    }
    
    // Offers accepted filter
    if ($allowOffers) {
        $whereConditions[] = "l.allow_offers = TRUE";
    }
    
    // Complete the WHERE clause
    $whereClause = implode(' AND ', $whereConditions);
    
    // GROUP BY clause
    $groupBy = "GROUP BY l.listing_id, u.user_id, c.category_id, li.image_id";
    
    // ORDER BY clause
    $orderBy = match($sort) {
        'newest' => 'ORDER BY l.date_created DESC',
        'oldest' => 'ORDER BY l.date_created ASC',
        'price_low' => 'ORDER BY l.price ASC',
        'price_high' => 'ORDER BY l.price DESC',
        'popular' => 'ORDER BY l.views_count DESC, l.favorites_count DESC',
        'distance' => 'ORDER BY l.location_city ASC', // Placeholder - would need user location for real distance
        default => 'ORDER BY l.date_created DESC'
    };
    
    // Count total results
    $countQuery = "
        SELECT COUNT(DISTINCT l.listing_id) as total
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        WHERE $whereClause
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get listings with pagination
    $listingsQuery = "$baseQuery WHERE $whereClause $groupBy $orderBy LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($listingsQuery);
    
    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process listings for output
    $processedListings = array_map(function($listing) {
        return [
            'listing_id' => $listing['listing_id'],
            'title' => $listing['title'],
            'description' => substr($listing['description'], 0, 100) . '...',
            'price' => $listing['price'],
            'condition_type' => $listing['condition_type'],
            'location' => $listing['location_city'] . ', ' . $listing['location_province'],
            'image_url' => $listing['primary_image'],
            'category_name' => $listing['category_name'],
            'category_slug' => $listing['category_slug'],
            'seller_verified' => (bool)$listing['seller_verified'],
            'allow_barter' => (bool)$listing['allow_barter'],
            'allow_offers' => (bool)$listing['allow_offers'],
            'time_ago' => timeAgo($listing['date_created']),
            'distance' => calculateDistance($listing) . 'km away',
            'seller_rating' => round($listing['seller_rating'], 1),
            'review_count' => $listing['seller_review_count'],
            'views_count' => $listing['views_count'],
            'favorites_count' => $listing['favorites_count'],
            'is_featured' => $listing['featured_until'] && strtotime($listing['featured_until']) > time()
        ];
    }, $listings);
    
    // Calculate pagination info
    $totalPages = ceil($totalResults / $limit);
    $pagination = [
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalResults' => $totalResults,
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
            'total' => $totalResults,
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
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in get_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while loading listings'
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
    // In a real app, this would calculate based on user's location
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
?>