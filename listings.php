<?php
/**
 * Listings Page - Edu C2C Marketplace
 * Updated with database integration
 */

// Include database configuration
require_once 'config/database.php';

// Initialize variables
$error_message = '';
$categories = [];

try {
    // Get database connection
    $pdo = getDatabase();
    
    // Get categories for filter
    $categories_query = "
        SELECT 
            c.category_id,
            c.category_name,
            c.category_slug,
            COUNT(l.listing_id) as listing_count
        FROM categories c
        LEFT JOIN listings l ON c.category_id = l.category_id 
            AND l.status = 'active' 
            AND l.admin_approved = TRUE
        WHERE c.is_active = TRUE
        GROUP BY c.category_id
        ORDER BY c.sort_order ASC, c.category_name ASC
    ";
    
    $categories_stmt = $pdo->prepare($categories_query);
    $categories_stmt->execute();
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error loading listings page data: " . $e->getMessage());
    $error_message = "Unable to load some content. Please try refreshing the page.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Listings - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .filter-active {
            background-color: #e3f2fd !important;
            border-color: #2196f3 !important;
        }
        
        .list-view .col-md-6,
        .list-view .col-lg-4 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        
        .list-view .card {
            flex-direction: row !important;
        }
        
        .list-view .card-img-top {
            width: 200px !important;
            height: 150px !important;
            object-fit: cover;
            border-radius: 0.375rem 0 0 0.375rem !important;
        }
        
        .list-view .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .badge-verified {
            background: #4a6bff;
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
                        <a class="nav-link active" href="listings.php">Browse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.php">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                    <button id="lowDataToggle" class="btn btn-sm btn-outline-secondary ms-2" title="Toggle Low Data Mode">
                        <i class="fas fa-network-wired"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <section class="bg-light py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg" placeholder="Search for products..." id="searchInput">
                        <select class="form-select" style="max-width: 200px;" id="locationFilter">
                            <option value="">All Locations</option>
                            <option value="Johannesburg">Johannesburg</option>
                            <option value="Cape Town">Cape Town</option>
                            <option value="Durban">Durban</option>
                            <option value="Pretoria">Pretoria</option>
                        </select>
                        <button class="btn btn-primary" type="button" id="searchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Filters</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="clearFilters">Clear All</button>
                    </div>
                    <div class="card-body">
                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="mb-3">Categories</h6>
                            <div id="categoryFilters">
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                    <div class="form-check">
                                        <input class="form-check-input category-filter" type="checkbox" 
                                               id="category-<?php echo htmlspecialchars($category['category_slug']); ?>" 
                                               value="<?php echo $category['category_id']; ?>">
                                        <label class="form-check-label" for="category-<?php echo htmlspecialchars($category['category_slug']); ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                            <?php if ($category['listing_count'] > 0): ?>
                                                <small class="text-muted">(<?php echo $category['listing_count']; ?>)</small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center">
                                        <small class="text-muted">Loading categories...</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="mb-3">Price Range</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>R0</span>
                                <span>R5000+</span>
                            </div>
                            <input type="range" class="form-range" min="0" max="5000" step="50" id="priceRange" value="5000">
                            <div class="d-flex justify-content-between mt-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control" placeholder="Min" id="minPrice" min="0">
                                </div>
                                <span class="mx-2 align-self-center">-</span>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control" placeholder="Max" id="maxPrice" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Condition -->
                        <div class="mb-4">
                            <h6 class="mb-3">Condition</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-new" value="new">
                                <label class="form-check-label" for="condition-new">New</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-like-new" value="like_new">
                                <label class="form-check-label" for="condition-like-new">Like New</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-good" value="good">
                                <label class="form-check-label" for="condition-good">Good</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-fair" value="fair">
                                <label class="form-check-label" for="condition-fair">Fair</label>
                            </div>
                        </div>
                        
                        <!-- Seller Type -->
                        <div class="mb-4">
                            <h6 class="mb-3">Seller Type</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="seller-verified">
                                <label class="form-check-label" for="seller-verified">Verified Sellers Only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="barter-available">
                                <label class="form-check-label" for="barter-available">Barter Available</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="offers-accepted">
                                <label class="form-check-label" for="offers-accepted">Accepts Offers</label>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100" id="applyFilters">Apply Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- Listings -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">All Listings</h2>
                        <small class="text-muted" id="resultsCount">Loading...</small>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                Sort by: Newest
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-sort="newest">Newest</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="oldest">Oldest</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="price_low">Price: Low to High</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="price_high">Price: High to Low</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="distance">Distance</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="popular">Most Popular</a></li>
                            </ul>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary active" id="gridView">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="listView">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading listings...</span>
                    </div>
                    <p class="mt-2">Loading listings...</p>
                </div>
                
                <!-- Listings Grid -->
                <div class="row" id="listingsGrid">
                    <!-- Listings will be loaded dynamically -->
                </div>
                
                <!-- No Results -->
                <div class="no-results d-none" id="noResults">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No listings found</h5>
                    <p class="text-muted">Try adjusting your search criteria or filters</p>
                    <button class="btn btn-primary" id="clearAllFilters">Clear All Filters</button>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Listings pagination" id="paginationContainer" class="d-none">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- Pagination will be generated dynamically -->
                    </ul>
                </nav>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Edu C2C</h5>
                    <p>Empowering South Africa's informal economy through technology.</p>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="listings.php" class="text-white">Browse Listings</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="listings.php?category=produce" class="text-white">Fresh Produce</a></li>
                        <li><a href="listings.php?category=handicrafts" class="text-white">Handicrafts</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Help Center</a></li>
                        <li><a href="#" class="text-white">Safety Tips</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        // Initialize listings manager when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing listings page...');
            
            // Test if we can access the server
            fetch('server/get_listings.php?test=1')
                .then(response => {
                    console.log('Server response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text(); // Get as text first to see what we're receiving
                })
                .then(text => {
                    console.log('Server response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed JSON:', data);
                    } catch (e) {
                        console.error('Failed to parse JSON:', e);
                        console.error('Response was:', text);
                    }
                })
                .catch(error => {
                    console.error('Error testing server connection:', error);
                });
                
            // Initialize enhanced listings manager if available
            if (typeof EnhancedListingsManager !== 'undefined') {
                window.listingsManager = new EnhancedListingsManager();
            } else {
                console.warn('EnhancedListingsManager not found, using basic functionality');
                // Load listings using basic approach
                loadBasicListings();
            }
        });
        
        function loadBasicListings() {
            console.log('Loading basic listings...');
            
            const grid = document.getElementById('listingsGrid');
            const spinner = document.getElementById('loadingSpinner');
            const resultsCount = document.getElementById('resultsCount');
            
            spinner.style.display = 'block';
            
            fetch('server/get_listings.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text(); // Get as text first
                })
                .then(text => {
                    console.log('Response text:', text);
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data);
                        
                        if (data.success && data.data && data.data.listings) {
                            displayListings(data.data.listings);
                            resultsCount.textContent = `${data.data.total} listings found`;
                        } else {
                            throw new Error(data.error || 'Invalid response format');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                })
                .catch(error => {
                    console.error('Error loading listings:', error);
                    grid.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-danger">
                                <h5>Error Loading Listings</h5>
                                <p>${error.message}</p>
                                <button class="btn btn-primary" onclick="loadBasicListings()">Try Again</button>
                            </div>
                        </div>
                    `;
                    resultsCount.textContent = 'Error loading listings';
                })
                .finally(() => {
                    spinner.style.display = 'none';
                });
        }
        
        function displayListings(listings) {
            const grid = document.getElementById('listingsGrid');
            
            if (!listings || listings.length === 0) {
                grid.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>No listings found</h5>
                            <p class="text-muted">Be the first to list an item!</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = listings.map(listing => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="${listing.image_url || 'https://via.placeholder.com/400x300?text=No+Image'}" 
                                 class="card-img-top" alt="${listing.title}" 
                                 style="height: 200px; object-fit: cover;"
                                 loading="lazy">
                            ${listing.allow_barter ? '<span class="badge bg-success position-absolute top-0 end-0 m-2">Barter Available</span>' : ''}
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title">${listing.title}</h5>
                                <span class="text-primary fw-bold">R${parseFloat(listing.price).toFixed(0)}</span>
                            </div>
                            <p class="card-text text-muted small">${listing.location}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-dark">${listing.category_name}</span>
                                    ${listing.seller_verified ? '<span class="badge badge-verified text-white ms-1"><i class="fas fa-check-circle"></i> Verified</span>' : ''}
                                </div>
                                <small class="text-muted">${listing.time_ago}</small>
                            </div>
                            <a href="product-detail.php?id=${listing.listing_id}" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    </script>
</body>
</html>