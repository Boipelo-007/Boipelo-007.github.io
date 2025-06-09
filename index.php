<?php
/**
 * Home Page - Edu C2C Marketplace
 * Updated with database integration
 */

// Include database configuration
require_once 'config/database.php';

// Initialize variables
$error_message = '';
$featured_listings = [];
$system_settings = [];

try {
    // Get database connection
    $pdo = getDatabase();
    
    // Get system settings
    $settings_query = "SELECT setting_key, setting_value FROM system_settings WHERE is_public = TRUE";
    $settings_stmt = $pdo->prepare($settings_query);
    $settings_stmt->execute();
    $settings_results = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($settings_results as $setting) {
        $system_settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Get featured listings
    $featured_query = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.price,
            l.location_city,
            l.location_province,
            l.date_created,
            l.allow_barter,
            l.views_count,
            l.favorites_count,
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
        LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
        LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
        WHERE l.status = 'active'
            AND l.admin_approved = TRUE
            AND u.is_active = TRUE
            AND (l.expiry_date IS NULL OR l.expiry_date > NOW())
            AND (l.featured_until IS NOT NULL AND l.featured_until > NOW())
        GROUP BY l.listing_id
        ORDER BY l.featured_until DESC, l.date_created DESC
        LIMIT 8
    ";
    
    $featured_stmt = $pdo->prepare($featured_query);
    $featured_stmt->execute();
    $featured_listings = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading home page data: " . $e->getMessage());
    $error_message = "Unable to load some content. Please try refreshing the page.";
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
    return date('M j, Y', strtotime($datetime));
}

function getDefaultImage($categorySlug) {
    $images = [
        'produce' => 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=400',
        'handicrafts' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400',
        'clothing' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
        'electronics' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=400',
        'home' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400',
        'default' => 'https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400'
    ];
    return $images[$categorySlug] ?? $images['default'];
}

$platform_name = $system_settings['platform_name'] ?? 'Edu C2C Marketplace';
$support_phone = $system_settings['support_phone'] ?? '+27831234567';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($platform_name); ?> - Home</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #4a6bff, #6b46ff);
            color: white;
            padding: 80px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        .feature-icon {
            background: linear-gradient(135deg, #4a6bff, #6b46ff);
            color: white;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .footer {
            background: #2d3748;
            color: white;
            padding: 40px 0 20px;
        }
        .badge-verified {
            background: #4a6bff;
        }
        .listing-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }
        .listing-skeleton .skeleton-img {
            background: #e2e8f0;
            height: 200px;
            border-radius: 8px 8px 0 0;
        }
        .listing-skeleton .skeleton-text {
            background: #e2e8f0;
            height: 20px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .listing-skeleton .skeleton-text.short {
            width: 60%;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <?php if ($error_message): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake me-2"></i><?php echo htmlspecialchars($platform_name); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="listings.php">Browse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.html">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.html">Contact</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.html" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.html" class="btn btn-primary">Register</a>
                    <button id="lowDataToggle" class="btn btn-sm btn-outline-secondary ms-2" title="Toggle Low Data Mode">
                        <i class="fas fa-network-wired"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Empowering South Africa's Informal Economy</h1>
            <p class="lead mb-5">Buy and sell directly with local vendors. No middlemen, more profits.</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="listings.php" method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control form-control-lg" placeholder="What are you looking for?">
                            <select name="location" class="form-select" style="max-width: 150px;">
                                <option value="">Location</option>
                                <option value="Johannesburg">Johannesburg</option>
                                <option value="Cape Town">Cape Town</option>
                                <option value="Durban">Durban</option>
                                <option value="Pretoria">Pretoria</option>
                            </select>
                            <button class="btn btn-primary btn-lg" type="submit">Search</button>
                        </div>
                    </form>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="listings.php?category=produce" class="btn btn-outline-light">Fresh Produce</a>
                        <a href="listings.php?category=handicrafts" class="btn btn-outline-light">Handicrafts</a>
                        <a href="listings.php?category=clothing" class="btn btn-outline-light">Clothing</a>
                        <a href="listings.php?category=electronics" class="btn btn-outline-light">Electronics</a>
                        <a href="listings.php?category=barter" class="btn btn-outline-light">Barter Offers</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container my-5">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile First</h3>
                <p>Designed for smartphones with low data consumption in mind.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Payments</h3>
                <p>Integrated mobile money and escrow for safe transactions.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Community Trust</h3>
                <p>Verified sellers and buyer reviews build confidence.</p>
            </div>
        </div>
    </section>

    <!-- Featured Listings -->
    <section class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Featured Listings</h2>
            <a href="listings.php" class="btn btn-outline-primary">View All</a>
        </div>
        <div class="row" id="featuredListings">
            <?php if (!empty($featured_listings)): ?>
                <?php foreach ($featured_listings as $listing): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($listing['primary_image'] ?: getDefaultImage($listing['category_slug'])); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                 style="height: 200px; object-fit: cover;"
                                 loading="lazy">
                            <?php if ($listing['allow_barter']): ?>
                            <span class="badge bg-success position-absolute top-0 end-0 m-2">Barter Available</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title"><?php echo htmlspecialchars(substr($listing['title'], 0, 30) . (strlen($listing['title']) > 30 ? '...' : '')); ?></h5>
                                <span class="text-primary fw-bold"><?php echo formatPrice($listing['price']); ?></span>
                            </div>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars($listing['location_city']); ?> • <?php echo rand(1, 20); ?>km away</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars($listing['category_name']); ?></span>
                                    <?php if ($listing['seller_verified']): ?>
                                    <span class="badge badge-verified text-white ms-1"><i class="fas fa-check-circle"></i> Verified</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo timeAgo($listing['date_created']); ?></small>
                            </div>
                            <a href="product-detail.php?id=<?php echo $listing['listing_id']; ?>" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Featured Listings Available</h4>
                    <p class="text-muted">Check back later for featured items from our community sellers.</p>
                    <a href="listings.php" class="btn btn-primary">Browse All Listings</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How It Works -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">How It Works</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </div>
                            <h4 class="my-3">1. Create Account</h4>
                            <p>Register as a buyer or seller in minutes. Verification adds trust to your profile.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-camera fa-2x"></i>
                            </div>
                            <h4 class="my-3">2. List Items</h4>
                            <p>Sellers can easily upload products with photos and descriptions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-hand-holding-usd fa-2x"></i>
                            </div>
                            <h4 class="my-3">3. Transact Safely</h4>
                            <p>Use our secure payment system or arrange local pickup with cash.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="container my-5">
        <h2 class="text-center mb-5">What Our Users Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h5 class="mb-0">Nomsa D.</h5>
                                <small class="text-muted">Street Vendor, Johannesburg</small>
                            </div>
                        </div>
                        <p class="card-text">"Since joining <?php echo htmlspecialchars($platform_name); ?>, I've doubled my customers. Now people from other neighborhoods buy my vegetables!"</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h5 class="mb-0">Thabo M.</h5>
                                <small class="text-muted">Buyer, Cape Town</small>
                            </div>
                        </div>
                        <p class="card-text">"I found authentic handmade crafts from local artists that I would never have discovered otherwise."</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h5 class="mb-0">Lerato K.</h5>
                                <small class="text-muted">Artisan, Durban</small>
                            </div>
                        </div>
                        <p class="card-text">"The digital literacy workshops helped me understand online selling. Now I manage my whole business from my phone."</p>
                        <div class="text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Join the Community?</h2>
            <p class="lead mb-5">Whether you want to buy local or grow your small business, we make it simple and safe.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.html" class="btn btn-light btn-lg px-4">Sign Up Free</a>
                <a href="about.html" class="btn btn-outline-light btn-lg px-4">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3"><?php echo htmlspecialchars($platform_name); ?></h5>
                    <p>Empowering South Africa's informal economy through technology.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="listings.php" class="text-white">Browse Listings</a></li>
                        <li><a href="sell.html" class="text-white">Sell Products</a></li>
                        <li><a href="about.html" class="text-white">How It Works</a></li>
                        <li><a href="contact.html" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="listings.php?category=produce" class="text-white">Fresh Produce</a></li>
                        <li><a href="listings.php?category=handicrafts" class="text-white">Handicrafts</a></li>
                        <li><a href="listings.php?category=clothing" class="text-white">Clothing</a></li>
                        <li><a href="listings.php?category=electronics" class="text-white">Electronics</a></li>
                        <li><a href="listings.php?category=barter" class="text-white">Barter Offers</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Help Center</a></li>
                        <li><a href="#" class="text-white">Safety Tips</a></li>
                        <li><a href="#" class="text-white">Digital Literacy</a></li>
                        <li><a href="#" class="text-white">Payment Options</a></li>
                        <li><a href="#" class="text-white">Community Guidelines</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="row">
                <div class="col-md-6">
                    <p class="small mb-0">© 2025 <?php echo htmlspecialchars($platform_name); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white small me-3">Privacy Policy</a>
                    <a href="#" class="text-white small me-3">Terms of Service</a>
                    <a href="#" class="text-white small">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        // Low Data Mode Toggle
        document.getElementById('lowDataToggle').addEventListener('click', function() {
            const isLowDataMode = localStorage.getItem('lowDataMode') === 'true';
            localStorage.setItem('lowDataMode', !isLowDataMode);
            
            if (!isLowDataMode) {
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-wifi"></i>';
                console.log('Low data mode enabled');
            } else {
                this.classList.remove('active');
                this.innerHTML = '<i class="fas fa-network-wired"></i>';
                console.log('Low data mode disabled');
            }
        });

        // Initialize low data mode state
        if (localStorage.getItem('lowDataMode') === 'true') {
            document.getElementById('lowDataToggle').classList.add('active');
            document.getElementById('lowDataToggle').innerHTML = '<i class="fas fa-wifi"></i>';
        }
    </script>
</body>
</html>