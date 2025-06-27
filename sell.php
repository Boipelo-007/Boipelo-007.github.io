<?php
session_start();

// Database configuration
require_once 'config/database.php';

// Initialize database if needed

$error_message = '';
$success_message = '';
$user_logged_in = isset($_SESSION['user_id']);

// Get categories for dropdown
$categories = [];
try {
    $pdo = getDatabase();
    $stmt = $pdo->query("SELECT category_id, category_name, category_slug FROM categories WHERE is_active = TRUE ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to load categories: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user_logged_in) {
        $error_message = 'You must be logged in to create a listing.';
    } else {
        $seller_id = $_SESSION['user_id'];
        $title = trim($_POST['itemTitle'] ?? '');
        $category_id = intval($_POST['itemCategory'] ?? 0);
        $description = trim($_POST['itemDescription'] ?? '');
        $price = floatval($_POST['itemPrice'] ?? 0);
        $quantity = intval($_POST['itemQuantity'] ?? 1);
        $allow_offers = isset($_POST['allowOffers']);
        $allow_barter = isset($_POST['allowBarter']);
        $condition_type = $_POST['itemCondition'] ?? 'good';
        $location_city = $_POST['itemLocation'] ?? '';
        $location_area = trim($_POST['itemArea'] ?? '');
        $pickup_only = isset($_POST['pickupOnly']);
        
        // Validation
        if (empty($title) || empty($description) || $category_id <= 0 || $price < 0 || empty($location_city)) {
            $error_message = 'Please fill in all required fields.';
        } else {
            try {
                // Get province based on city (simplified mapping)
                $city_to_province = [
                    'Johannesburg' => 'Gauteng',
                    'Pretoria' => 'Gauteng',
                    'Cape Town' => 'Western Cape',
                    'Durban' => 'KwaZulu-Natal',
                    'Port Elizabeth' => 'Eastern Cape'
                ];
                $location_province = $city_to_province[$location_city] ?? 'Other';
                
                // Insert listing
                $insert_stmt = $pdo->prepare("
                    INSERT INTO listings (
                        seller_id, title, description, category_id, price, quantity_available,
                        allow_offers, allow_barter, condition_type, location_province, location_city,
                        location_area, pickup_available, delivery_available, status, date_created,
                        expiry_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))
                ");
                
                $insert_stmt->execute([
                    $seller_id,
                    $title,
                    $description,
                    $category_id,
                    $price,
                    $quantity,
                    $allow_offers ? 1 : 0,
                    $allow_barter ? 1 : 0,
                    $condition_type,
                    $location_province,
                    $location_city,
                    $location_area,
                    true, // pickup_available - always true for now
                    !$pickup_only // delivery_available - opposite of pickup_only
                ]);
                
                $listing_id = $pdo->lastInsertId();
                
                $success_message = 'Your listing has been created successfully! Listing ID: ' . $listing_id;
                
                // Clear form data after successful submission
                $_POST = [];
                
            } catch (PDOException $e) {
                error_log('Listing creation error: ' . $e->getMessage());
                $error_message = 'Failed to create listing. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell on Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <a class="nav-link active" href="sell.php">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($user_logged_in): ?>
                        <span class="navbar-text me-3">Hello, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</span>
                        <a href="logout.php" class="btn btn-outline-primary">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                    <button id="lowDataToggle" class="btn btn-sm btn-outline-secondary ms-2" title="Toggle Low Data Mode">
                        <i class="fas fa-network-wired"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        <?php if (!$user_logged_in): ?>
            <!-- Not logged in message -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm text-center">
                        <div class="card-body py-5">
                            <i class="fas fa-user-lock fa-4x text-muted mb-4"></i>
                            <h3 class="mb-3">Login Required</h3>
                            <p class="text-muted mb-4">You need to be logged in to create a listing. Join our community of sellers today!</p>
                            <div class="d-gap gap-3">
                                <a href="login.php" class="btn btn-primary btn-lg me-3">Login</a>
                                <a href="register.php" class="btn btn-outline-primary btn-lg">Create Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Listing form for logged in users -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-5">
                        <div class="card-header bg-white">
                            <h3 class="mb-0">List an Item for Sale</h3>
                            <p class="text-muted mb-0">Reach thousands of buyers in your community</p>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="listingForm">
                                <div class="mb-4">
                                    <h5 class="mb-3">Item Details</h5>
                                    <div class="mb-3">
                                        <label for="itemTitle" class="form-label">Title*</label>
                                        <input type="text" class="form-control" id="itemTitle" name="itemTitle" 
                                               placeholder="What are you selling?" 
                                               value="<?php echo isset($_POST['itemTitle']) ? htmlspecialchars($_POST['itemTitle']) : ''; ?>" required>
                                        <small class="text-muted">Be specific (e.g., "Fresh Organic Tomatoes" instead of "Vegetables")</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="itemCategory" class="form-label">Category*</label>
                                        <select class="form-select" id="itemCategory" name="itemCategory" required>
                                            <option value="" selected disabled>Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" 
                                                        <?php echo (isset($_POST['itemCategory']) && $_POST['itemCategory'] == $category['category_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="itemDescription" class="form-label">Description*</label>
                                        <textarea class="form-control" id="itemDescription" name="itemDescription" rows="4" 
                                                  placeholder="Describe your item in detail" required><?php echo isset($_POST['itemDescription']) ? htmlspecialchars($_POST['itemDescription']) : ''; ?></textarea>
                                        <small class="text-muted">Include condition, size, color, brand, and any flaws</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3">Pricing & Condition</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="itemPrice" class="form-label">Price (ZAR)*</label>
                                            <div class="input-group">
                                                <span class="input-group-text">R</span>
                                                <input type="number" class="form-control" id="itemPrice" name="itemPrice" 
                                                       placeholder="0.00" min="0" step="0.01" 
                                                       value="<?php echo isset($_POST['itemPrice']) ? $_POST['itemPrice'] : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="itemQuantity" class="form-label">Quantity Available*</label>
                                            <input type="number" class="form-control" id="itemQuantity" name="itemQuantity" 
                                                   min="1" value="<?php echo isset($_POST['itemQuantity']) ? $_POST['itemQuantity'] : '1'; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="itemCondition" class="form-label">Condition*</label>
                                        <select class="form-select" id="itemCondition" name="itemCondition" required>
                                            <option value="new" <?php echo (isset($_POST['itemCondition']) && $_POST['itemCondition'] === 'new') ? 'selected' : ''; ?>>New</option>
                                            <option value="like_new" <?php echo (isset($_POST['itemCondition']) && $_POST['itemCondition'] === 'like_new') ? 'selected' : ''; ?>>Like New</option>
                                            <option value="good" <?php echo (!isset($_POST['itemCondition']) || $_POST['itemCondition'] === 'good') ? 'selected' : ''; ?>>Good</option>
                                            <option value="fair" <?php echo (isset($_POST['itemCondition']) && $_POST['itemCondition'] === 'fair') ? 'selected' : ''; ?>>Fair</option>
                                            <option value="poor" <?php echo (isset($_POST['itemCondition']) && $_POST['itemCondition'] === 'poor') ? 'selected' : ''; ?>>Poor</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="allowOffers" name="allowOffers"
                                               <?php echo isset($_POST['allowOffers']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allowOffers">Allow buyers to make offers</label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allowBarter" name="allowBarter"
                                               <?php echo isset($_POST['allowBarter']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allowBarter">Open to barter/trade</label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h5 class="mb-3">Location & Delivery</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="itemLocation" class="form-label">Location*</label>
                                            <select class="form-select" id="itemLocation" name="itemLocation" required>
                                                <option value="" selected disabled>Select your location</option>
                                                <option value="Johannesburg" <?php echo (isset($_POST['itemLocation']) && $_POST['itemLocation'] === 'Johannesburg') ? 'selected' : ''; ?>>Johannesburg</option>
                                                <option value="Cape Town" <?php echo (isset($_POST['itemLocation']) && $_POST['itemLocation'] === 'Cape Town') ? 'selected' : ''; ?>>Cape Town</option>
                                                <option value="Durban" <?php echo (isset($_POST['itemLocation']) && $_POST['itemLocation'] === 'Durban') ? 'selected' : ''; ?>>Durban</option>
                                                <option value="Pretoria" <?php echo (isset($_POST['itemLocation']) && $_POST['itemLocation'] === 'Pretoria') ? 'selected' : ''; ?>>Pretoria</option>
                                                <option value="Port Elizabeth" <?php echo (isset($_POST['itemLocation']) && $_POST['itemLocation'] === 'Port Elizabeth') ? 'selected' : ''; ?>>Port Elizabeth</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="itemArea" class="form-label">Neighborhood/Area</label>
                                            <input type="text" class="form-control" id="itemArea" name="itemArea" 
                                                   placeholder="e.g., Soweto, Sandton" 
                                                   value="<?php echo isset($_POST['itemArea']) ? htmlspecialchars($_POST['itemArea']) : ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Delivery Options*</label>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="pickupOnly" name="pickupOnly"
                                                   <?php echo (!isset($_POST['pickupOnly']) || isset($_POST['pickupOnly'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pickupOnly">Local Pickup Available</label>
                                        </div>
                                        <small class="text-muted">Additional delivery options can be configured later</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-check-circle me-2"></i> Publish Listing
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()">
                                        Save Draft
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Selling Tips</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6><i class="fas fa-camera text-primary me-2"></i> Take Great Photos</h6>
                                <p class="text-muted">Use natural light and take photos from multiple angles. Show any flaws or special features.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-pencil-alt text-primary me-2"></i> Write Clear Descriptions</h6>
                                <p class="text-muted">Be honest about the condition and include measurements for clothing or furniture.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-tag text-primary me-2"></i> Price Competitively</h6>
                                <p class="text-muted">Check similar items on the platform and price accordingly. Consider allowing offers.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-shield-alt text-primary me-2"></i> Stay Safe</h6>
                                <p class="text-muted">Meet in public places for exchanges. Consider our secure payment options for safety.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Edu C2C</h5>
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
                        <li><a href="sell.php" class="text-white">Sell Products</a></li>
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
                    <p class="small mb-0">© 2025 Edu C2C Platform. All rights reserved.</p>
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
        function saveDraft() {
            // Save form data to localStorage
            const formData = new FormData(document.getElementById('listingForm'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('listingDraft', JSON.stringify(data));
            alert('Draft saved locally!');
        }

        // Load draft on page load
        document.addEventListener('DOMContentLoaded', function() {
            const draft = localStorage.getItem('listingDraft');
            if (draft && confirm('Load saved draft?')) {
                const data = JSON.parse(draft);
                for (const [key, value] of Object.entries(data)) {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = value === 'on';
                        } else {
                            element.value = value;
                        }
                    }
                }
            }
        });

        // Clear draft after successful submission
        <?php if (!empty($success_message)): ?>
        localStorage.removeItem('listingDraft');
        <?php endif; ?>
    </script>
</body>
</html>