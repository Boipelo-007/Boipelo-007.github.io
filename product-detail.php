<?php
/**
 * Fixed Product Detail Page - Edu C2C Marketplace
 * Displays individual product information with proper error handling
 */

session_start();
require_once 'config/database.php';

$error_message = '';
$listing = null;
$listing_images = [];
$seller_info = null;
$related_listings = [];

// Get listing ID from URL
$listing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($listing_id <= 0) {
    $error_message = 'Invalid listing ID provided.';
} else {
    try {
        $pdo = getDatabase();
        
        // Get listing details with seller info
        $listing_sql = "
            SELECT 
                l.*,
                u.user_id as seller_id,
                u.first_name as seller_first_name,
                u.last_name as seller_last_name,
                u.email as seller_email,
                u.phone_number as seller_phone,
                u.is_verified as seller_verified,
                u.date_created as seller_member_since,
                u.profile_image_url as seller_avatar,
                c.category_name,
                c.category_slug,
                COALESCE(AVG(ur.rating), 0) as seller_rating,
                COUNT(DISTINCT ur.rating_id) as seller_review_count,
                COUNT(DISTINCT sl.listing_id) as seller_total_listings
            FROM listings l
            INNER JOIN users u ON l.seller_id = u.user_id
            INNER JOIN categories c ON l.category_id = c.category_id
            LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
            LEFT JOIN listings sl ON u.user_id = sl.seller_id AND sl.status = 'active'
            WHERE l.listing_id = ? 
                AND l.status = 'active'
                AND l.admin_approved = 1
                AND u.is_active = 1
            GROUP BY l.listing_id
        ";
        
        $stmt = $pdo->prepare($listing_sql);
        $stmt->execute([$listing_id]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$listing) {
            $error_message = 'Listing not found or no longer available.';
        } else {
            // Increment view count
            $update_views = $pdo->prepare("UPDATE listings SET views_count = views_count + 1 WHERE listing_id = ?");
            $update_views->execute([$listing_id]);
            
            // Get listing images
            $images_sql = "SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, display_order ASC";
            $images_stmt = $pdo->prepare($images_sql);
            $images_stmt->execute([$listing_id]);
            $listing_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get related listings (same category, different seller)
            $related_sql = "
                SELECT 
                    l.listing_id,
                    l.title,
                    l.price,
                    l.location_city,
                    l.date_created,
                    li.image_url as primary_image,
                    u.is_verified as seller_verified
                FROM listings l
                INNER JOIN users u ON l.seller_id = u.user_id
                LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
                WHERE l.category_id = ? 
                    AND l.listing_id != ?
                    AND l.status = 'active'
                    AND l.admin_approved = 1
                    AND u.is_active = 1
                ORDER BY l.date_created DESC
                LIMIT 4
            ";
            
            $related_stmt = $pdo->prepare($related_sql);
            $related_stmt->execute([$listing['category_id'], $listing_id]);
            $related_listings = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        error_log("Product detail error: " . $e->getMessage());
        $error_message = 'Unable to load listing details. Please try again later.';
    }
}

// Helper functions
function formatPrice($price) {
    return $price == 0 ? 'Free' : 'R' . number_format($price, 0);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return floor($time/2592000) . ' months ago';
}

function getDefaultImage() {
    return '/assets/images/placeholder.jpg';
}

function getListingImage($image_url) {
    return !empty($image_url) ? htmlspecialchars($image_url) : getDefaultImage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $listing ? htmlspecialchars($listing['title']) . ' - Edu C2C Marketplace' : 'Product Not Found - Edu C2C Marketplace'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php if ($listing): ?>
    <!-- Meta tags for social sharing -->
    <meta property="og:title" content="<?php echo htmlspecialchars($listing['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($listing['description'], 0, 160)); ?>">
    <meta property="og:image" content="<?php echo getListingImage($listing_images[0]['image_url'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake me-2"></i>Edu C2C
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="listings.php">Browse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.php">Sell</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-4">
        <?php if ($error_message): ?>
            <!-- Error State -->
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5">
                            <i class="fas fa-exclamation-triangle fa-4x text-warning mb-4"></i>
                            <h3 class="mb-3">Product Not Found</h3>
                            <p class="text-muted mb-4"><?php echo htmlspecialchars($error_message); ?></p>
                            <div class="d-flex gap-3 justify-content-center">
                                <a href="listings.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Browse All Listings
                                </a>
                                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Go Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="listings.php">Listings</a></li>
                    <li class="breadcrumb-item"><a href="listings.php?category=<?php echo $listing['category_slug']; ?>"><?php echo htmlspecialchars($listing['category_name']); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($listing['title']); ?></li>
                </ol>
            </nav>

            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <?php if (!empty($listing_images)): ?>
                            <img src="<?php echo getListingImage($listing_images[0]['image_url']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                 style="height: 400px; object-fit: cover;">
                        <?php else: ?>
                            <img src="<?php echo getDefaultImage(); ?>" 
                                 class="card-img-top" alt="No image available"
                                 style="height: 400px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <!-- Thumbnail images -->
                    <?php if (count($listing_images) > 1): ?>
                        <div class="row g-2">
                            <?php foreach (array_slice($listing_images, 1, 4) as $image): ?>
                                <div class="col-3">
                                    <img src="<?php echo getListingImage($image['image_url']); ?>" 
                                         class="img-thumbnail w-100" alt="Product image"
                                         style="height: 80px; object-fit: cover; cursor: pointer;"
                                         onclick="changeMainImage('<?php echo getListingImage($image['image_url']); ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="mb-4">
                        <?php if ($listing['featured_until'] && strtotime($listing['featured_until']) > time()): ?>
                            <span class="badge bg-warning text-dark mb-2">
                                <i class="fas fa-star me-1"></i>Featured
                            </span>
                        <?php endif; ?>
                        
                        <h1 class="display-6 mb-3"><?php echo htmlspecialchars($listing['title']); ?></h1>
                        
                        <div class="d-flex align-items-center mb-3">
                            <h2 class="text-primary mb-0 me-3"><?php echo formatPrice($listing['price']); ?></h2>
                            <?php if ($listing['allow_barter']): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-exchange-alt me-1"></i>Open to Barter
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Location</small>
                                <strong><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($listing['location_city'] . ', ' . $listing['location_province']); ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Condition</small>
                                <strong><?php echo htmlspecialchars($listing['condition_type']); ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-2 mb-4">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                                <button class="btn btn-primary flex-grow-1 py-3" onclick="contactSeller()">
                                    <i class="fas fa-comment me-2"></i>Contact Seller
                                </button>
                                <button class="btn btn-outline-primary py-3" onclick="toggleFavorite()">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="btn btn-primary flex-grow-1 py-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Contact Seller
                                </a>
                            <?php else: ?>
                                <a href="edit-listing.php?id=<?php echo $listing['listing_id']; ?>" class="btn btn-secondary flex-grow-1 py-3">
                                    <i class="fas fa-edit me-2"></i>Edit Listing
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($listing['allow_barter'] && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                            <button class="btn btn-success w-100 py-3 mb-4" data-bs-toggle="modal" data-bs-target="#barterModal">
                                <i class="fas fa-exchange-alt me-2"></i>Make Barter Offer
                            </button>
                        <?php endif; ?>
                        
                        <!-- Listing Stats -->
                        <div class="row text-center border-top pt-3">
                            <div class="col-4">
                                <strong class="d-block"><?php echo number_format($listing['views_count']); ?></strong>
                                <small class="text-muted">Views</small>
                            </div>
                            <div class="col-4">
                                <strong class="d-block"><?php echo number_format($listing['favorites_count']); ?></strong>
                                <small class="text-muted">Favorites</small>
                            </div>
                            <div class="col-4">
                                <strong class="d-block"><?php echo timeAgo($listing['date_created']); ?></strong>
                                <small class="text-muted">Listed</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seller Info -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo !empty($listing['seller_avatar']) ? htmlspecialchars($listing['seller_avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($listing['seller_first_name'] . ' ' . $listing['seller_last_name']); ?>" 
                                     class="rounded-circle me-3" width="60" height="60" alt="Seller">
                                <div>
                                    <h5 class="mb-0">
                                        <?php echo htmlspecialchars($listing['seller_first_name'] . ' ' . substr($listing['seller_last_name'], 0, 1) . '.'); ?>
                                        <?php if ($listing['seller_verified']): ?>
                                            <i class="fas fa-check-circle text-success ms-1" title="Verified Seller"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted mb-1">Member since <?php echo date('Y', strtotime($listing['seller_member_since'])); ?></p>
                                    <div class="text-warning">
                                        <?php 
                                        $rating = round($listing['seller_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                        <span class="text-dark ms-2"><?php echo $rating; ?> (<?php echo $listing['seller_review_count']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="seller-profile.php?id=<?php echo $listing['seller_id']; ?>" class="btn btn-outline-primary flex-grow-1 me-2">
                                    <i class="fas fa-store me-2"></i>View Profile
                                </a>
                                <span class="btn btn-outline-secondary">
                                    <?php echo $listing['seller_total_listings']; ?> listings
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Listings -->
            <?php if (!empty($related_listings)): ?>
                <div class="row mt-5">
                    <div class="col-12">
                        <h3 class="mb-4">Related Listings</h3>
                        <div class="row">
                            <?php foreach ($related_listings as $related): ?>
                                <div class="col-md-6 col-lg-3 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo getListingImage($related['primary_image']); ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($related['title']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars(substr($related['title'], 0, 50)) . (strlen($related['title']) > 50 ? '...' : ''); ?></h6>
                                            <p class="text-primary fw-bold"><?php echo formatPrice($related['price']); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($related['location_city']); ?>
                                            </small>
                                        </div>
                                        <div class="card-footer">
                                            <a href="product-detail.php?id=<?php echo $related['listing_id']; ?>" class="btn btn-outline-primary btn-sm w-100">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Barter Modal -->
    <div class="modal fade" id="barterModal" tabindex="-1" aria-labelledby="barterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barterModalLabel">Make Barter Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>What would you like to trade for this item?</p>
                    <form id="barterForm">
                        <div class="mb-3">
                            <label for="barterItem" class="form-label">Item You're Offering</label>
                            <input type="text" class="form-control" id="barterItem" required>
                        </div>
                        <div class="mb-3">
                            <label for="barterDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="barterDescription" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitBarterOffer()">Send Offer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Change main image when thumbnail is clicked
        function changeMainImage(src) {
            document.querySelector('.card-img-top').src = src;
        }
        
        // Contact seller functionality
        function contactSeller() {
            <?php if (isset($_SESSION['user_id'])): ?>
                // In a real app, this would open a messaging interface
                alert('This would open the messaging interface to contact the seller.');
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
        
        // Toggle favorite functionality
        function toggleFavorite() {
            <?php if (isset($_SESSION['user_id'])): ?>
                // In a real app, this would toggle the favorite status
                alert('This would toggle the favorite status for this listing.');
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        }
        
        // Submit barter offer
        function submitBarterOffer() {
            const item = document.getElementById('barterItem').value;
            const description = document.getElementById('barterDescription').value;
            
            if (!item || !description) {
                alert('Please fill in all fields.');
                return;
            }
            
            // In a real app, this would submit the barter offer
            alert('Barter offer would be sent to the seller.');
            bootstrap.Modal.getInstance(document.getElementById('barterModal')).hide();
        }
    </script>
</body>
</html>