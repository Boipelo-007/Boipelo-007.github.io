<?php
/**
 * Get Featured Listings from Database
 * Eduvos C2C Marketplace
 */

// Include database configuration
require_once '../config/database.php';

try {
    // Get database connection
    $pdo = getDatabase();
    
    /**
     * Get featured listings that are currently active and featured
     * Joins with users, categories, and listing images tables
     */
    function getFeaturedListings($pdo, $limit = 8) {
        $sql = "
            SELECT 
                l.listing_id,
                l.title,
                l.description,
                l.price,
                l.location_city,
                l.location_province,
                l.date_created,
                l.allow_barter,
                l.allow_offers,
                l.featured_until,
                l.views_count,
                l.favorites_count,
                
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
                COUNT(DISTINCT ur.rating_id) as seller_review_count,
                
                -- Distance calculation (placeholder - would need user's location)
                CASE 
                    WHEN l.location_city = 'Johannesburg' THEN FLOOR(RAND() * 10) + 1
                    WHEN l.location_city = 'Cape Town' THEN FLOOR(RAND() * 15) + 5
                    WHEN l.location_city = 'Durban' THEN FLOOR(RAND() * 12) + 3
                    ELSE FLOOR(RAND() * 20) + 5
                END as distance_km
                
            FROM listings l
            INNER JOIN users u ON l.seller_id = u.user_id
            INNER JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
            LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
            
            WHERE l.status = 'active'
                AND l.admin_approved = TRUE
                AND u.is_active = TRUE
                AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
                AND (l.featured_until IS NOT NULL AND l.featured_until > NOW())
            
            GROUP BY l.listing_id, u.user_id, c.category_id, li.image_id
            ORDER BY l.featured_until DESC, l.date_created DESC
            LIMIT :limit
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Format price for display
     */
    function formatPrice($price) {
        return 'R' . number_format($price, 0);
    }
    
    /**
     * Get time ago string
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
    
    /**
     * Generate seller rating stars HTML
     */
    function generateStars($rating, $reviewCount = 0) {
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
        
        $stars = '';
        
        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        }
        
        // Half star
        if ($halfStar) {
            $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
        }
        
        // Empty stars
        for ($i = 0; $i < $emptyStars; $i++) {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }
        
        return $stars;
    }
    
    /**
     * Get default image if no primary image exists
     */
    function getListingImage($primaryImage, $categorySlug) {
        if ($primaryImage) {
            return $primaryImage;
        }
        
        // Default images based on category
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
    
    // If this file is called directly (for testing)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename($_SERVER['PHP_SELF']) === 'get_featured_listings.php') {
        header('Content-Type: application/json');
        
        try {
            $featuredListings = getFeaturedListings($pdo, 8);
            
            // Process listings for JSON output
            $processedListings = array_map(function($listing) {
                return [
                    'id' => $listing['listing_id'],
                    'title' => $listing['title'],
                    'description' => substr($listing['description'], 0, 100) . '...',
                    'price' => formatPrice($listing['price']),
                    'location' => $listing['location_city'],
                    'image' => getListingImage($listing['primary_image'], $listing['category_slug']),
                    'category' => $listing['category_name'],
                    'seller_verified' => (bool)$listing['seller_verified'],
                    'allow_barter' => (bool)$listing['allow_barter'],
                    'time_ago' => timeAgo($listing['date_created']),
                    'distance' => $listing['distance_km'] . 'km away',
                    'seller_rating' => round($listing['seller_rating'], 1),
                    'review_count' => $listing['seller_review_count']
                ];
            }, $featuredListings);
            
            echo json_encode([
                'success' => true,
                'data' => $processedListings,
                'count' => count($processedListings)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?>