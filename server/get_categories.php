<?php
/**
 * Get Categories for Listings Filter
 * Edu C2C Marketplace
 */

// Include database configuration
require_once '../config/database.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get database connection
    $pdo = getDatabase();
    
    /**
     * Get all active categories with listing counts
     */
    $sql = "
        SELECT 
            c.category_id,
            c.category_name,
            c.category_slug,
            c.description,
            c.icon_class,
            c.parent_category_id,
            COUNT(l.listing_id) as listing_count
        FROM categories c
        LEFT JOIN listings l ON c.category_id = l.category_id 
            AND l.status = 'active' 
            AND l.admin_approved = TRUE
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
        WHERE c.is_active = TRUE
        GROUP BY c.category_id
        ORDER BY c.sort_order ASC, c.category_name ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process categories to group parent and child categories
    $parentCategories = [];
    $childCategories = [];
    
    foreach ($categories as $category) {
        $processedCategory = [
            'category_id' => (int)$category['category_id'],
            'category_name' => $category['category_name'],
            'category_slug' => $category['category_slug'],
            'description' => $category['description'],
            'icon_class' => $category['icon_class'],
            'listing_count' => (int)$category['listing_count'],
            'parent_category_id' => $category['parent_category_id'] ? (int)$category['parent_category_id'] : null
        ];
        
        if ($category['parent_category_id']) {
            $childCategories[] = $processedCategory;
        } else {
            $parentCategories[] = $processedCategory;
        }
    }
    
    // Attach child categories to their parents
    foreach ($parentCategories as $index => $parent) {
        $parentCategories[$index]['subcategories'] = array_filter($childCategories, function($child) use ($parent) {
            return $child['parent_category_id'] === $parent['category_id'];
        });
        $parentCategories[$index]['subcategories'] = array_values($parentCategories[$index]['subcategories']);
    }
    
    // Get some quick stats for the response
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT l.listing_id) as total_listings,
            COUNT(DISTINCT CASE WHEN l.featured_until > NOW() THEN l.listing_id END) as featured_listings,
            COUNT(DISTINCT u.user_id) as active_sellers
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        WHERE l.status = 'active' 
            AND l.admin_approved = TRUE
            AND u.is_active = TRUE
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'data' => $parentCategories,
        'stats' => [
            'total_categories' => count($parentCategories),
            'total_listings' => (int)$stats['total_listings'],
            'featured_listings' => (int)$stats['featured_listings'],
            'active_sellers' => (int)$stats['active_sellers']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'data' => getFallbackCategories() // Provide fallback categories
    ]);
} catch (Exception $e) {
    error_log("General error in get_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while loading categories',
        'data' => getFallbackCategories()
    ]);
}

/**
 * Fallback categories in case of database error
 */
function getFallbackCategories() {
    return [
        [
            'category_id' => 1,
            'category_name' => 'Fresh Produce',
            'category_slug' => 'produce',
            'description' => 'Fruits, vegetables, and fresh farm products',
            'icon_class' => 'fas fa-apple-alt',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => [
                [
                    'category_id' => 7,
                    'category_name' => 'Vegetables',
                    'category_slug' => 'vegetables',
                    'description' => 'Fresh vegetables and greens',
                    'icon_class' => 'fas fa-carrot',
                    'listing_count' => 0,
                    'parent_category_id' => 1
                ],
                [
                    'category_id' => 8,
                    'category_name' => 'Fruits',
                    'category_slug' => 'fruits',
                    'description' => 'Fresh seasonal fruits',
                    'icon_class' => 'fas fa-apple-alt',
                    'listing_count' => 0,
                    'parent_category_id' => 1
                ]
            ]
        ],
        [
            'category_id' => 2,
            'category_name' => 'Handicrafts',
            'category_slug' => 'handicrafts',
            'description' => 'Handmade items, crafts, and artisan products',
            'icon_class' => 'fas fa-paint-brush',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => [
                [
                    'category_id' => 9,
                    'category_name' => 'Traditional Crafts',
                    'category_slug' => 'traditional-crafts',
                    'description' => 'African traditional handicrafts',
                    'icon_class' => 'fas fa-palette',
                    'listing_count' => 0,
                    'parent_category_id' => 2
                ],
                [
                    'category_id' => 10,
                    'category_name' => 'Jewelry',
                    'category_slug' => 'jewelry',
                    'description' => 'Handmade jewelry and accessories',
                    'icon_class' => 'fas fa-gem',
                    'listing_count' => 0,
                    'parent_category_id' => 2
                ]
            ]
        ],
        [
            'category_id' => 3,
            'category_name' => 'Clothing',
            'category_slug' => 'clothing',
            'description' => 'Clothes, shoes, and fashion accessories',
            'icon_class' => 'fas fa-tshirt',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => []
        ],
        [
            'category_id' => 4,
            'category_name' => 'Electronics',
            'category_slug' => 'electronics',
            'description' => 'Phones, computers, and electronic devices',
            'icon_class' => 'fas fa-mobile-alt',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => [
                [
                    'category_id' => 12,
                    'category_name' => 'Mobile Phones',
                    'category_slug' => 'mobile-phones',
                    'description' => 'Smartphones and accessories',
                    'icon_class' => 'fas fa-mobile-alt',
                    'listing_count' => 0,
                    'parent_category_id' => 4
                ]
            ]
        ],
        [
            'category_id' => 5,
            'category_name' => 'Home Goods',
            'category_slug' => 'home',
            'description' => 'Furniture, appliances, and household items',
            'icon_class' => 'fas fa-home',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => []
        ],
        [
            'category_id' => 6,
            'category_name' => 'Barter Offers',
            'category_slug' => 'barter',
            'description' => 'Items available for trade or barter',
            'icon_class' => 'fas fa-exchange-alt',
            'listing_count' => 0,
            'parent_category_id' => null,
            'subcategories' => []
        ]
    ];
}
?>