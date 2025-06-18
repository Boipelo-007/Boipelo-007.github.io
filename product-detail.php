<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Vegetables - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation (same as index.php) -->
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
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.html" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                    <button id="lowDataToggle" class="btn btn-sm btn-outline-secondary ms-2" title="Toggle Low Data Mode">
                        <i class="fas fa-network-wired"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <img src="https://images.unsplash.com/photo-1606787366850-de6330128bfc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Fresh Vegetables">
                </div>
                <div class="row g-2">
                    <div class="col-3">
                        <img src="https://images.unsplash.com/photo-1518977676601-b53f82aba655?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="img-thumbnail" alt="Vegetables">
                    </div>
                    <div class="col-3">
                        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" class="img-thumbnail" alt="Vegetables">
                    </div>
                    <div class="col-3">
                        <img src="https://images.unsplash.com/photo-1444459094717-a39f1e3e0903?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="img-thumbnail" alt="Vegetables">
                    </div>
                    <div class="col-3">
                        <img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80" class="img-thumbnail" alt="Vegetables">
                    </div>
                </div>
            </div>
            
            <!-- Product Details -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="mb-1">Fresh Vegetables Basket</h2>
                                <p class="text-muted mb-2">Johannesburg • 2km away</p>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="text-warning me-2">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <small class="text-muted">(24 reviews)</small>
                                    <span class="badge badge-verified text-white ms-2"><i class="fas fa-check-circle"></i> Verified Seller</span>
                                </div>
                            </div>
                            <span class="badge bg-success">Barter Available</span>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="text-primary">R85</h3>
                            <small class="text-success"><i class="fas fa-tag"></i> Competitive price compared to local stores</small>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Description</h5>
                            <p>Freshly harvested vegetables from my garden. This basket includes tomatoes, onions, carrots, spinach, and green beans. Grown organically without pesticides. Harvested today, perfect for your family's healthy meals.</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Organic farming methods</li>
                                <li><i class="fas fa-check text-success me-2"></i> Harvested fresh daily</li>
                                <li><i class="fas fa-check text-success me-2"></i> No chemical pesticides</li>
                                <li><i class="fas fa-check text-success me-2"></i> Supports local small farmer</li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Delivery Options</h5>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="deliveryOption" id="pickup" checked>
                                <label class="form-check-label" for="pickup">
                                    <strong>Local Pickup</strong> - Free (Johannesburg CBD)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="deliveryOption" id="delivery5km">
                                <label class="form-check-label" for="delivery5km">
                                    <strong>Delivery (within 5km)</strong> - R15
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="deliveryOption" id="delivery10km">
                                <label class="form-check-label" for="delivery10km">
                                    <strong>Delivery (5-10km)</strong> - R25
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Quantity</h5>
                            <div class="d-flex align-items-center">
                                <button class="btn btn-outline-secondary px-3" id="decreaseQty">-</button>
                                <input type="number" class="form-control text-center mx-2" value="1" min="1" max="10" id="quantity">
                                <button class="btn btn-outline-secondary px-3" id="increaseQty">+</button>
                                <small class="text-muted ms-3">10 available</small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mb-3">
                            <button class="btn btn-primary flex-grow-1 py-3">
                                <i class="fas fa-shopping-cart me-2"></i> Buy Now
                            </button>
                            <button class="btn btn-outline-primary py-3">
                                <i class="fas fa-heart me-2"></i> Save
                            </button>
                        </div>
                        
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#barterModal">
                            <i class="fas fa-exchange-alt me-2"></i> Make Barter Offer
                        </button>
                    </div>
                </div>
                
                <!-- Seller Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" class="rounded-circle me-3" width="60" height="60" alt="Seller">
                            <div>
                                <h5 class="mb-0">Nomsa D.</h5>
                                <p class="text-muted mb-1">Member since 2023</p>
                                <div class="text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <span class="text-dark ms-2">4.9 (42)</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-outline-primary flex-grow-1 me-2">
                                <i class="fas fa-store me-2"></i> View Shop
                            </button>
                            <button class="btn btn-outline-secondary">
                                <i class="fas fa-comment me-2"></i> Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button">Reviews (24)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button">Shipping & Returns</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="productTabsContent">
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-body">
                                <h5 class="mb-3">Product Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Category</th>
                                                    <td>Fresh Produce</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Weight</th>
                                                    <td>Approx. 2kg</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Harvest Date</th>
                                                    <td>Today</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Organic</th>
                                                    <td>Yes</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Pesticide Free</th>
                                                    <td>Yes</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Shelf Life</th>
                                                    <td>5-7 days when refrigerated</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <h5 class="mt-4 mb-3">What's Included</h5>
                                <ul>
                                    <li>4 large tomatoes</li>
                                    <li>3 medium onions</li>
                                    <li>5 carrots</li>
                                    <li>1 bunch of spinach</li>
                                    <li>200g green beans</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center mb-4">
                                            <h2 class="mb-0">4.8</h2>
                                            <div class="text-warning mb-2">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star-half-alt"></i>
                                            </div>
                                            <p class="text-muted">24 reviews</p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <small class="me-2">5 stars</small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: 80%"></div>
                                                </div>
                                                <small class="ms-2">19</small>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <small class="me-2">4 stars</small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: 15%"></div>
                                                </div>
                                                <small class="ms-2">3</small>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <small class="me-2">3 stars</small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: 5%"></div>
                                                </div>
                                                <small class="ms-2">1</small>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <small class="me-2">2 stars</small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: 0%"></div>
                                                </div>
                                                <small class="ms-2">0</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <small class="me-2">1 star</small>
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: 0%"></div>
                                                </div>
                                                <small class="ms-2">1</small>
                                            </div>
                                        </div>
                                        
                                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                            Write a Review
                                        </button>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="review">
                                            <div class="d-flex mb-3">
                                                <img src="https://randomuser.me/api/portraits/men/32.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                                                <div>
                                                    <h6 class="mb-0">Thabo M.</h6>
                                                    <div class="text-warning mb-1">
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                    </div>
                                                    <small class="text-muted">2 days ago</small>
                                                </div>
                                            </div>
                                            <p>The vegetables were fresh and exactly as described. Will definitely buy again!</p>
                                        </div>
                                        
                                        <div class="review">
                                            <div class="d-flex mb-3">
                                                <img src="https://randomuser.me/api/portraits/women/44.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                                                <div>
                                                    <h6 class="mb-0">Lerato K.</h6>
                                                    <div class="text-warning mb-1">
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                    </div>
                                                    <small class="text-muted">1 week ago</small>
                                                </div>
                                            </div>
                                            <p>Excellent quality and the seller was very friendly. The spinach was especially fresh.</p>
                                        </div>
                                        
                                        <div class="review">
                                            <div class="d-flex mb-3">
                                                <img src="https://randomuser.me/api/portraits/men/22.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                                                <div>
                                                    <h6 class="mb-0">Sipho N.</h6>
                                                    <div class="text-warning mb-1">
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star"></i>
                                                        <i class="fas fa-star-half-alt"></i>
                                                    </div>
                                                    <small class="text-muted">2 weeks ago</small>
                                                </div>
                                            </div>
                                            <p>Good vegetables overall, though the tomatoes were a bit smaller than I expected.</p>
                                        </div>
                                        
                                        <nav aria-label="Reviews pagination">
                                            <ul class="pagination justify-content-center">
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                                                </li>
                                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                                <li class="page-item">
                                                    <a class="page-link" href="#">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="shipping" role="tabpanel">
                        <div class="card border-0">
                            <div class="card-body">
                                <h5 class="mb-3">Shipping Policy</h5>
                                <p>This seller offers the following shipping options:</p>
                                <ul>
                                    <li><strong>Local Pickup:</strong> Free collection from Johannesburg CBD. Address will be provided after purchase.</li>
                                    <li><strong>Delivery (within 5km):</strong> R15 delivery fee. Usually delivered within 24 hours.</li>
                                    <li><strong>Delivery (5-10km):</strong> R25 delivery fee. Usually delivered within 48 hours.</li>
                                </ul>
                                
                                <h5 class="mt-4 mb-3">Returns & Refunds</h5>
                                <p>Due to the perishable nature of fresh produce, returns are not accepted unless the product arrives damaged or significantly different from the description. In such cases, please contact the seller within 12 hours of delivery to arrange for a refund or replacement.</p>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-info-circle me-2"></i> For any issues with your order, please contact the seller directly through the platform's messaging system.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                    <p>The seller is open to bartering for this item. Please describe what you'd like to trade:</p>
                    <div class="mb-3">
                        <label for="barterItem" class="form-label">Item You're Offering</label>
                        <input type="text" class="form-control" id="barterItem" placeholder="e.g., Handmade blanket, Used bicycle, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="barterDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="barterDescription" rows="3" placeholder="Describe your item's condition, age, and any other relevant details"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="barterPhotos" class="form-label">Upload Photos (optional)</label>
                        <input class="form-control" type="file" id="barterPhotos" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Send Barter Offer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h6>How would you rate this product?</h6>
                        <div class="rating-stars">
                            <i class="far fa-star fa-2x" data-rating="1"></i>
                            <i class="far fa-star fa-2x" data-rating="2"></i>
                            <i class="far fa-star fa-2x" data-rating="3"></i>
                            <i class="far fa-star fa-2x" data-rating="4"></i>
                            <i class="far fa-star fa-2x" data-rating="5"></i>
                        </div>
                        <small class="text-muted" id="ratingText">Tap to rate</small>
                    </div>
                    <div class="mb-3">
                        <label for="reviewTitle" class="form-label">Review Title</label>
                        <input type="text" class="form-control" id="reviewTitle" placeholder="Summarize your experience">
                    </div>
                    <div class="mb-3">
                        <label for="reviewText" class="form-label">Your Review</label>
                        <textarea class="form-control" id="reviewText" rows="4" placeholder="What did you like or dislike about this product?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="reviewPhotos" class="form-label">Upload Photos (optional)</label>
                        <input class="form-control" type="file" id="reviewPhotos" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Submit Review</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer (same as index.php) -->
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
        // Quantity controls
        document.getElementById('decreaseQty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
        
        document.getElementById('increaseQty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            let value = parseInt(quantityInput.value);
            if (value < 10) {
                quantityInput.value = value + 1;
            }
        });
        
        // Star rating for review modal
        const stars = document.querySelectorAll('.rating-stars i');
        const ratingText = document.getElementById('ratingText');
        const ratingMessages = [
            "Poor",
            "Fair",
            "Good",
            "Very Good",
            "Excellent"
        ];
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                
                // Update stars
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
                
                // Update rating text
                ratingText.textContent = ratingMessages[rating - 1];
            });
        });
    </script>
</body>
</html>