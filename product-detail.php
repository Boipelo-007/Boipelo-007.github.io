<?php
/**
 * Product Detail Page - Edu C2C Marketplace
 * Updated with database integration
 */

// Include database configuration
require_once 'config/database.php';

// Initialize variables
$listing = null;
$seller = null;
$images = [];
$error_message = '';
$listing_id = intval($_GET['id'] ?? 0);

if ($listing_id <= 0) {
    header('Location: listings.php');
    exit;
}

try {
    // Get database connection
    $pdo = getDatabase();
    
    // Get listing details with seller and category info
    $listing_query = "
        SELECT 
            l.*,
            u.first_name as seller_first_name,
            u.last_name as seller_last_name,
            u.is_verified as seller_verified,
            u.profile_image_url as seller_avatar,
            u.phone_number as seller_phone,
            u.date_created as seller_joined,
            c.category_name,
            c.category_slug,
            COALESCE(AVG(ur.rating), 0) as seller_rating,
            COUNT(DISTINCT ur.rating_id) as seller_review_count
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN user_ratings ur ON u.user_id = ur.rated_user_id
        WHERE l.listing_id = ? 
            AND l.status = 'active' 
            AND l.admin_approved = TRUE
            AND u.is_active = TRUE
        GROUP BY l.listing_id
    ";
    
    $listing_stmt = $pdo->prepare($listing_query);
    $listing_stmt->execute([$listing_id]);
    $listing = $listing_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$listing) {
        $error_message = "Listing not found or no longer available.";
    } else {
        // Get listing images
        $images_query = "
            SELECT image_url, alt_text, is_primary 
            FROM listing_images 
            WHERE listing_id = ? 
            ORDER BY is_primary DESC, display_order ASC
        ";
        $images_stmt = $pdo->prepare($images_query);
        $images_stmt->execute([$listing_id]);
        $images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no images, add default image
        if (empty($images)) {
            $images[] = [
                'image_url' => getDefaultImage($listing['category_slug']),
                'alt_text' => $listing['title'],
                'is_primary' => true
            ];
        }
        
        // Update view count
        $update_views = "UPDATE listings SET views_count = views_count + 1 WHERE listing_id = ?";
        $pdo->prepare($update_views)->execute([$listing_id]);
        $listing['views_count']++;
    }
    
} catch (Exception $e) {
    error_log("Error loading product detail: " . $e->getMessage());
    $error_message = "Unable to load product details. Please try again.";
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
        'produce' => 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=800',
        'handicrafts' => 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=800',
        'clothing' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800',
        'electronics' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=800',
        'home' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800',
        'default' => 'https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=800'
    ];
    return $images[$categorySlug] ?? $images['default'];
}

function generateStars($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }
    if ($halfStar) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = $fullStars + ($halfStar ? 1 : 0); $i < 5; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }
    
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $listing ? htmlspecialchars($listing['title']) : 'Product Not Found'; ?> - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="sell.php">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        <?php if ($error_message): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger">
                        <h5>Error</h5>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <a href="listings.php" class="btn btn-primary">Browse Other Listings</a>
                    </div>
                </div>
            </div>
        <?php elseif ($listing): ?>
            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <?php $mainImage = $images[0] ?? ['image_url' => getDefaultImage('default'), 'alt_text' => 'Product image']; ?>
                        <img src="<?php echo htmlspecialchars($mainImage['image_url']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($mainImage['alt_text']); ?>"
                             id="mainImage">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="row g-2">
                        <?php foreach (array_slice($images, 0, 4) as $index => $image): ?>
                        <div class="col-3">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                 class="img-thumbnail thumbnail-image" 
                                 alt="<?php echo htmlspecialchars($image['alt_text']); ?>"
                                 onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>')"
                                 style="cursor: pointer;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Details -->
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h2 class="mb-1"><?php echo htmlspecialchars($listing['title']); ?></h2>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($listing['location_city'] . ', ' . $listing['location_province']); ?>
                                    </p>
                                    <?php if ($listing['seller_verified']): ?>
                                    <span class="badge badge-verified text-white">
                                        <i class="fas fa-check-circle"></i> Verified Seller
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($listing['allow_barter']): ?>
                                <span class="badge bg-success">Barter Available</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-4">
                                <h3 class="text-primary"><?php echo formatPrice($listing['price']); ?></h3>
                                <small class="text-muted">Condition: <?php echo ucfirst(str_replace('_', ' ', $listing['condition_type'])); ?></small>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($listing['description'])); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Details</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Category:</th>
                                        <td><?php echo htmlspecialchars($listing['category_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Condition:</th>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $listing['condition_type'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Quantity Available:</th>
                                        <td><?php echo $listing['quantity_available']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Listed:</th>
                                        <td><?php echo timeAgo($listing['date_created']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Views:</th>
                                        <td><?php echo number_format($listing['views_count']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <?php if ($listing['quantity_available'] > 0): ?>
                            <div class="mb-4">
                                <h5>Quantity</h5>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-outline-secondary px-3" id="decreaseQty">-</button>
                                    <input type="number" class="form-control text-center mx-2" value="1" min="1" max="<?php echo $listing['quantity_available']; ?>" id="quantity" style="width: 80px;">
                                    <button class="btn btn-outline-secondary px-3" id="increaseQty">+</button>
                                    <small class="text-muted ms-3"><?php echo $listing['quantity_available']; ?> available</small>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mb-3">
                                <button class="btn btn-primary flex-grow-1 py-3" onclick="contactSeller()">
                                    <i class="fas fa-comment me-2"></i> Contact Seller
                                </button>
                                <button class="btn btn-outline-primary py-3" onclick="toggleFavorite(<?php echo $listing['listing_id']; ?>)">
                                    <i class="far fa-heart me-2"></i> Save
                                </button>
                            </div>
                            
                            <?php if ($listing['allow_barter']): ?>
                            <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#barterModal">
                                <i class="fas fa-exchange-alt me-2"></i> Make Barter Offer
                            </button>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                This item is currently out of stock.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Seller Info -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $listing['seller_avatar'] ?: 'https://randomuser.me/api/portraits/men/32.jpg'; ?>" 
                                     class="rounded-circle me-3" width="60" height="60" alt="Seller">
                                <div>
                                    <h5 class="mb-0">
                                        <?php echo htmlspecialchars($listing['seller_first_name'] . ' ' . substr($listing['seller_last_name'], 0, 1) . '.'); ?>
                                        <?php if ($listing['seller_verified']): ?>
                                        <i class="fas fa-check-circle text-primary ms-1" title="Verified Seller"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted mb-1">Member since <?php echo date('Y', strtotime($listing['seller_joined'])); ?></p>
                                    <div class="text-warning">
                                        <?php echo generateStars($listing['seller_rating']); ?>
                                        <span class="text-dark ms-2"><?php echo number_format($listing['seller_rating'], 1); ?> (<?php echo $listing['seller_review_count']; ?>)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-primary flex-grow-1 me-2" onclick="contactSeller()">
                                    <i class="fas fa-comment me-2"></i> Chat
                                </button>
                                <button class="btn btn-outline-success" onclick="callSeller()">
                                    <i class="fas fa-phone me-2"></i> Call
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Barter Modal -->
    <?php if ($listing && $listing['allow_barter']): ?>
    <div class="modal fade" id="barterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Make Barter Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Make a barter offer for: <strong><?php echo htmlspecialchars($listing['title']); ?></strong></p>
                    <div class="mb-3">
                        <label for="barterItem" class="form-label">What are you offering?</label>
                        <input type="text" class="form-control" id="barterItem" placeholder="e.g., Handmade blanket, Used bicycle">
                    </div>
                    <div class="mb-3">
                        <label for="barterDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="barterDescription" rows="3" placeholder="Describe your item's condition and details"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="barterValue" class="form-label">Estimated Value</label>
                        <div class="input-group">
                            <span class="input-group-text">R</span>
                            <input type="number" class="form-control" id="barterValue" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="submitBarterOffer()">Send Offer</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Edu C2C</h5>
                    <p>Empowering South Africa's informal economy through technology.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white small me-3">Privacy Policy</a>
                    <a href="#" class="text-white small">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quantity controls
        document.getElementById('decreaseQty')?.addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
        
        document.getElementById('increaseQty')?.addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            const maxQty = parseInt(quantityInput.getAttribute('max'));
            let value = parseInt(quantityInput.value);
            if (value < maxQty) {
                quantityInput.value = value + 1;
            }
        });
        
        // Change main image when thumbnail is clicked
        function changeMainImage(imageUrl) {
            document.getElementById('mainImage').src = imageUrl;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail-image').forEach(thumb => {
                thumb.classList.remove('border-primary');
                thumb.style.borderWidth = '1px';
            });
            
            event.target.classList.add('border-primary');
            event.target.style.borderWidth = '3px';
        }
        
        // Contact seller function
        function contactSeller() {
            <?php if ($listing): ?>
            const sellerPhone = '<?php echo htmlspecialchars($listing['seller_phone']); ?>';
            const listingTitle = '<?php echo htmlspecialchars($listing['title']); ?>';
            const message = `Hi! I'm interested in your listing: ${listingTitle}`;
            
            // Create contact modal
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Contact Seller</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Send a message about: <strong>${listingTitle}</strong></label>
                                <textarea class="form-control" rows="3" id="contactMessage" placeholder="Hi! I'm interested in your listing...">${message}</textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" onclick="sendWhatsApp()">
                                    <i class="fab fa-whatsapp me-2"></i> Send via WhatsApp
                                </button>
                                <button class="btn btn-primary" onclick="sendSMS()">
                                    <i class="fas fa-sms me-2"></i> Send SMS
                                </button>
                                <button class="btn btn-outline-secondary" onclick="callDirectly()">
                                    <i class="fas fa-phone me-2"></i> Call Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Clean up modal when closed
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
            
            // WhatsApp function
            window.sendWhatsApp = function() {
                const message = document.getElementById('contactMessage').value;
                const whatsappUrl = `https://wa.me/${sellerPhone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(message)}`;
                window.open(whatsappUrl, '_blank');
                bsModal.hide();
            };
            
            // SMS function
            window.sendSMS = function() {
                const message = document.getElementById('contactMessage').value;
                const smsUrl = `sms:${sellerPhone}?body=${encodeURIComponent(message)}`;
                window.location.href = smsUrl;
                bsModal.hide();
            };
            
            // Call function
            window.callDirectly = function() {
                const telUrl = `tel:${sellerPhone}`;
                window.location.href = telUrl;
                bsModal.hide();
            };
            <?php else: ?>
            alert('Unable to contact seller at this time.');
            <?php endif; ?>
        }
        
        // Call seller directly
        function callSeller() {
            <?php if ($listing): ?>
            const sellerPhone = '<?php echo htmlspecialchars($listing['seller_phone']); ?>';
            if (confirm('Call seller now?')) {
                window.location.href = `tel:${sellerPhone}`;
            }
            <?php endif; ?>
        }
        
        // Toggle favorite
        function toggleFavorite(listingId) {
            // Check if user is logged in (you can implement proper session checking)
            const isLoggedIn = false; // Replace with actual login check
            
            if (!isLoggedIn) {
                alert('Please log in to save favorites');
                window.location.href = 'login.php';
                return;
            }
            
            // Send AJAX request to toggle favorite
            fetch('server/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ listing_id: listingId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const heartIcon = event.target.querySelector('i');
                    if (data.favorited) {
                        heartIcon.className = 'fas fa-heart me-2 text-danger';
                        showToast('Added to favorites', 'success');
                    } else {
                        heartIcon.className = 'far fa-heart me-2';
                        showToast('Removed from favorites', 'info');
                    }
                } else {
                    showToast('Unable to update favorites', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error', 'error');
            });
        }
        
        // Submit barter offer
        function submitBarterOffer() {
            const item = document.getElementById('barterItem').value.trim();
            const description = document.getElementById('barterDescription').value.trim();
            const value = document.getElementById('barterValue').value;
            
            if (!item || !description) {
                alert('Please fill in all required fields');
                return;
            }
            
            const offerData = {
                listing_id: <?php echo $listing['listing_id']; ?>,
                offered_item: item,
                description: description,
                estimated_value: value || 0
            };
            
            // Send AJAX request
            fetch('server/submit_barter_offer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(offerData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Barter offer sent successfully!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('barterModal')).hide();
                    
                    // Reset form
                    document.getElementById('barterItem').value = '';
                    document.getElementById('barterDescription').value = '';
                    document.getElementById('barterValue').value = '';
                } else {
                    showToast(data.error || 'Failed to send barter offer', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            });
        }
        
        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        // Report listing function
        function reportListing() {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Report Listing</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Reason for reporting:</label>
                                <select class="form-select" id="reportReason">
                                    <option value="">Select a reason</option>
                                    <option value="inappropriate">Inappropriate content</option>
                                    <option value="fraud">Suspected fraud</option>
                                    <option value="spam">Spam</option>
                                    <option value="fake">Fake listing</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Additional details:</label>
                                <textarea class="form-control" id="reportDetails" rows="3" placeholder="Please provide more details about the issue..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" onclick="submitReport()">Submit Report</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
            
            window.submitReport = function() {
                const reason = document.getElementById('reportReason').value;
                const details = document.getElementById('reportDetails').value;
                
                if (!reason) {
                    alert('Please select a reason for reporting');
                    return;
                }
                
                // Submit report (implement server endpoint)
                fetch('server/submit_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        listing_id: <?php echo $listing['listing_id']; ?>,
                        reason: reason,
                        details: details
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Report submitted successfully. Thank you for helping keep our platform safe.', 'success');
                        bsModal.hide();
                    } else {
                        showToast('Failed to submit report. Please try again.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error. Please try again.', 'error');
                });
            };
        }
        
        // Share listing function
        function shareListing() {
            const title = '<?php echo addslashes($listing['title'] ?? ''); ?>';
            const url = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    text: `Check out this listing on Edu C2C: ${title}`,
                    url: url
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    showToast('Link copied to clipboard!', 'success');
                }).catch(() => {
                    showToast('Unable to copy link', 'error');
                });
            }
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Product detail page loaded');
            
            // Set first thumbnail as active
            const firstThumbnail = document.querySelector('.thumbnail-image');
            if (firstThumbnail) {
                firstThumbnail.classList.add('border-primary');
                firstThumbnail.style.borderWidth = '3px';
            }
            
            // Track page view for analytics
            if (typeof Analytics !== 'undefined') {
                Analytics.sendEvent('product_view', {
                    listing_id: <?php echo $listing['listing_id']; ?>,
                    category: '<?php echo addslashes($listing['category_slug']); ?>',
                    price: <?php echo $listing['price']; ?>
                });
            }
        });
    </script>
</body>
</html>