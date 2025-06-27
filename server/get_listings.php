<?php
/**
 * Fixed Get Listings API
 * Edu C2C Marketplace
 */

require_once '../config/database.php';

// Set content type to JSON and headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling function
function sendError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

try {
    // Get database connection
    $pdo = getDatabase();
    
    // Get request parameters with validation
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(50, intval($_GET['limit'] ?? 12)));
    $offset = ($page - 1) * $limit;
    
    // Search and filter parameters
    $search = trim($_GET['search'] ?? '');
    $categories = array_filter(explode(',', $_GET['category'] ?? ''));
    $location = trim($_GET['location'] ?? '');
    $minPrice = isset($_GET['minPrice']) && $_GET['minPrice'] !== '' ? floatval($_GET['minPrice']) : null;
    $maxPrice = isset($_GET['maxPrice']) && $_GET['maxPrice'] !== '' ? floatval($_GET['maxPrice']) : null;
    $conditions = array_filter(explode(',', $_GET['condition'] ?? ''));
    $verified = filter_var($_GET['verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $allowBarter = filter_var($_GET['barter'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $allowOffers = filter_var($_GET['offers'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $sort = $_GET['sort'] ?? 'newest';
    
    // Build the base query
    $sql = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.price,
            l.location_city,
            l.location_province,
            l.condition_type,
            l.allow_barter,
            l.allow_offers,
            l.date_created,
            l.views_count,
            l.favorites_count,
            l.featured_until,
            u.first_name as seller_first_name,
            u.is_verified as seller_verified,
            c.category_name,
            c.category_slug,
            li.image_url as primary_image,
            COALESCE(AVG(ur.rating), 0) as seller_rating,
            COUNT(DISTINCT ur.rating_id) as seller_review_count
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = 1
        LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
        WHERE l.status = 'active'
            AND l.admin_approved = 1
            AND u.is_active = 1
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
    ";
    
    $params = [];
    
    // Add search condition
    if (!empty($search)) {
        $sql .= " AND (l.title LIKE :search OR l.description LIKE :search OR c.category_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Add category filter
    if (!empty($categories)) {
        $categoryPlaceholders = [];
        foreach ($categories as $index => $category) {
            $placeholder = ':category_' . $index;
            $categoryPlaceholders[] = $placeholder;
            $params[$placeholder] = trim($category);
        }
        $sql .= " AND c.category_slug IN (" . implode(',', $categoryPlaceholders) . ")";
    }
    
    // Add location filter
    if (!empty($location)) {
        $sql .= " AND (l.location_city LIKE :location OR l.location_province LIKE :location)";
        $params[':location'] = '%' . $location . '%';
    }
    
    // Add price filters
    if ($minPrice !== null) {
        $sql .= " AND l.price >= :min_price";
        $params[':min_price'] = $minPrice;
    }
    
    if ($maxPrice !== null) {
        $sql .= " AND l.price <= :max_price";
        $params[':max_price'] = $maxPrice;
    }
    
    // Add condition filter
    if (!empty($conditions)) {
        $conditionPlaceholders = [];
        foreach ($conditions as $index => $condition) {
            $placeholder = ':condition_' . $index;
            $conditionPlaceholders[] = $placeholder;
            $params[$placeholder] = trim($condition);
        }
        $sql .= " AND l.condition_type IN (" . implode(',', $conditionPlaceholders) . ")";
    }
    
    // Add verified seller filter
    if ($verified) {
        $sql .= " AND u.is_verified = 1";
    }
    
    // Add barter filter
    if ($allowBarter) {
        $sql .= " AND l.allow_barter = 1";
    }
    
    // Add offers filter
    if ($allowOffers) {
        $sql .= " AND l.allow_offers = 1";
    }
    
    // Group by to avoid duplicates from ratings join
    $sql .= " GROUP BY l.listing_id";
    
    // Add sorting
    switch ($sort) {
        case 'price_low':
            $sql .= " ORDER BY l.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY l.price DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY l.views_count DESC, l.date_created DESC";
            break;
        case 'featured':
            $sql .= " ORDER BY (l.featured_until IS NOT NULL AND l.featured_until > NOW()) DESC, l.date_created DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY l.date_created ASC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY l.date_created DESC";
            break;
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(DISTINCT l.listing_id) as total " . 
                substr($sql, strpos($sql, 'FROM'), strpos($sql, 'GROUP BY') - strpos($sql, 'FROM'));
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetch()['total'];
    
    // Add pagination
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    // Execute main query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
    
    // Process listings
    $processedListings = array_map(function($listing) {
        return [
            'listing_id' => (int)$listing['listing_id'],
            'title' => htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars(substr($listing['description'], 0, 150), ENT_QUOTES, 'UTF-8') . '...',
            'price' => formatPrice($listing['price']),
            'price_raw' => (float)$listing['price'],
            'location' => htmlspecialchars($listing['location_city'] . ', ' . $listing['location_province'], ENT_QUOTES, 'UTF-8'),
            'condition' => htmlspecialchars($listing['condition_type'], ENT_QUOTES, 'UTF-8'),
            'image_url' => $listing['primary_image'] ?: '/assets/images/placeholder.jpg',
            'category_name' => htmlspecialchars($listing['category_name'], ENT_QUOTES, 'UTF-8'),
            'category_slug' => $listing['category_slug'],
            'seller_verified' => (bool)$listing['seller_verified'],
            'allow_barter' => (bool)$listing['allow_barter'],
            'allow_offers' => (bool)$listing['allow_offers'],
            'time_ago' => timeAgo($listing['date_created']),
            'seller_rating' => round((float)$listing['seller_rating'], 1),
            'review_count' => (int)$listing['seller_review_count'],
            'views_count' => (int)$listing['views_count'],
            'favorites_count' => (int)$listing['favorites_count'],
            'is_featured' => $listing['featured_until'] && strtotime($listing['featured_until']) > time(),
            'url' => '/product-detail.php?id=' . $listing['listing_id']
        ];
    }, $listings);
    
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
    
    // Return successful response
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
        ],
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Database error in get_listings.php: " . $e->getMessage());
    sendError('Database error occurred');
} catch (Exception $e) {
    error_log("General error in get_listings.php: " . $e->getMessage());
    sendError('An error occurred while loading listings');
}

/**
 * Helper Functions
 */

function formatPrice($price) {
    $price = (float)$price;
    return $price == 0 ? 'Free' : 'R' . number_format($price, 0);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

?>