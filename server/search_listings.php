<?php
/**
 * Search Listings API
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
    
    // Get search parameters
    $query = trim($_GET['q'] ?? $_POST['q'] ?? '');
    $limit = max(1, min(20, intval($_GET['limit'] ?? 10)));
    $location = trim($_GET['location'] ?? '');
    
    if (empty($query)) {
        echo json_encode([
            'success' => false,
            'error' => 'Search query is required'
        ]);
        exit;
    }
    
    // Search in listings
    $searchSql = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.price,
            l.location_city,
            l.location_province,
            l.allow_barter,
            l.date_created,
            c.category_name,
            c.category_slug,
            u.is_verified as seller_verified,
            li.image_url as primary_image,
            MATCH(l.title, l.description) AGAINST(:query IN NATURAL LANGUAGE MODE) as relevance_score
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
        WHERE l.status = 'active'
            AND l.admin_approved = TRUE
            AND u.is_active = TRUE
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
            AND (
                MATCH(l.title, l.description) AGAINST(:query IN NATURAL LANGUAGE MODE)
                OR l.title LIKE :query_like
                OR l.description LIKE :query_like
                OR c.category_name LIKE :query_like
            )
    ";
    
    $params = [
        ':query' => $query,
        ':query_like' => '%' . $query . '%'
    ];
    
    // Add location filter if provided
    if (!empty($location)) {
        $searchSql .= " AND (l.location_city LIKE :location OR l.location_province LIKE :location)";
        $params[':location'] = '%' . $location . '%';
    }
    
    $searchSql .= " ORDER BY relevance_score DESC, l.date_created DESC LIMIT :limit";
    
    $stmt = $pdo->prepare($searchSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also search for similar categories
    $categorySql = "
        SELECT 
            category_id,
            category_name,
            category_slug,
            description
        FROM categories
        WHERE is_active = TRUE
            AND (category_name LIKE :query_like OR description LIKE :query_like)
        LIMIT 5
    ";
    
    $categoryStmt = $pdo->prepare($categorySql);
    $categoryStmt->bindValue(':query_like', '%' . $query . '%');
    $categoryStmt->execute();
    $categoryResults = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Search for sellers/users
    $sellerSql = "
        SELECT DISTINCT
            u.user_id,
            u.first_name,
            u.last_name,
            u.is_verified,
            ul.city,
            ul.province,
            COUNT(l.listing_id) as listing_count
        FROM users u
        LEFT JOIN user_locations ul ON u.user_id = ul.user_id AND ul.is_primary = TRUE
        LEFT JOIN listings l ON u.user_id = l.seller_id AND l.status = 'active'
        WHERE u.is_active = TRUE
            AND u.user_type = 'seller'
            AND (u.first_name LIKE :query_like OR u.last_name LIKE :query_like)
        GROUP BY u.user_id
        HAVING listing_count > 0
        LIMIT 5
    ";
    
    $sellerStmt = $pdo->prepare($sellerSql);
    $sellerStmt->bindValue(':query_like', '%' . $query . '%');
    $sellerStmt->execute();
    $sellerResults = $sellerStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process search results
    $processedListings = array_map(function($listing) {
        return [
            'listing_id' => $listing['listing_id'],
            'title' => $listing['title'],
            'description' => substr($listing['description'], 0, 100) . '...',
            'price' => $listing['price'],
            'location' => $listing['location_city'] . ', ' . $listing['location_province'],
            'image_url' => $listing['primary_image'],
            'category_name' => $listing['category_name'],
            'category_slug' => $listing['category_slug'],
            'seller_verified' => (bool)$listing['seller_verified'],
            'allow_barter' => (bool)$listing['allow_barter'],
            'time_ago' => timeAgo($listing['date_created']),
            'relevance_score' => $listing['relevance_score']
        ];
    }, $searchResults);
    
    // Process category results
    $processedCategories = array_map(function($category) {
        return [
            'category_id' => $category['category_id'],
            'category_name' => $category['category_name'],
            'category_slug' => $category['category_slug'],
            'description' => $category['description']
        ];
    }, $categoryResults);
    
    // Process seller results
    $processedSellers = array_map(function($seller) {
        return [
            'user_id' => $seller['user_id'],
            'name' => trim($seller['first_name'] . ' ' . $seller['last_name']),
            'is_verified' => (bool)$seller['is_verified'],
            'location' => trim(($seller['city'] ?? '') . ', ' . ($seller['province'] ?? '')),
            'listing_count' => (int)$seller['listing_count']
        ];
    }, $sellerResults);
    
    // Generate search suggestions
    $suggestions = generateSearchSuggestions($query, $pdo);
    
    // Return comprehensive search results
    echo json_encode([
        'success' => true,
        'query' => $query,
        'data' => [
            'listings' => $processedListings,
            'categories' => $processedCategories,
            'sellers' => $processedSellers,
            'suggestions' => $suggestions
        ],
        'counts' => [
            'listings' => count($processedListings),
            'categories' => count($processedCategories),
            'sellers' => count($processedSellers)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in search_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("General error in search_listings.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while searching'
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

function generateSearchSuggestions($query, $pdo) {
    $suggestions = [];
    
    try {
        // Get popular search terms from listing titles
        $sql = "
            SELECT l.title, COUNT(*) as frequency
            FROM listings l
            WHERE l.status = 'active' 
                AND l.title LIKE :query_like
                AND l.admin_approved = TRUE
            GROUP BY l.title
            ORDER BY frequency DESC, l.title ASC
            LIMIT 5
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':query_like', '%' . $query . '%');
        $stmt->execute();
        
        $titleSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($titleSuggestions as $suggestion) {
            $suggestions[] = [
                'type' => 'listing',
                'text' => $suggestion['title'],
                'frequency' => $suggestion['frequency']
            ];
        }
        
        // Get category suggestions
        $categorySql = "
            SELECT category_name
            FROM categories
            WHERE is_active = TRUE 
                AND category_name LIKE :query_like
            ORDER BY category_name ASC
            LIMIT 3
        ";
        
        $categoryStmt = $pdo->prepare($categorySql);
        $categoryStmt->bindValue(':query_like', '%' . $query . '%');
        $categoryStmt->execute();
        
        $categorySuggestions = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($categorySuggestions as $suggestion) {
            $suggestions[] = [
                'type' => 'category',
                'text' => $suggestion['category_name'],
                'frequency' => 0
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error generating suggestions: " . $e->getMessage());
    }
    
    return array_slice($suggestions, 0, 8); // Limit to 8 suggestions
}
?>