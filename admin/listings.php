<?php
/**
 * Admin Listings Management - Edu C2C Marketplace
 * Database Integration with InfinityFree MySQL
 */

// Include database configuration
require_once '../config/database.php';

// Simple authentication check
session_start();

// Initialize variables
$listings = [];
$categories = [];
$totalListings = 0;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter parameters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$locationFilter = $_GET['location'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

try {
    // Build WHERE clause for filters
    $whereConditions = ["1=1"]; // Base condition
    $params = [];

    if ($statusFilter) {
        $whereConditions[] = "l.status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($categoryFilter) {
        $whereConditions[] = "l.category_id = :category_id";
        $params[':category_id'] = $categoryFilter;
    }

    if ($locationFilter) {
        $whereConditions[] = "l.location_city LIKE :location";
        $params[':location'] = '%' . $locationFilter . '%';
    }

    if ($searchQuery) {
        $whereConditions[] = "(l.title LIKE :search OR l.description LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(DISTINCT l.listing_id) as total
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        WHERE $whereClause
    ";
    
    $totalResult = getSingleRecord($countQuery, $params);
    $totalListings = $totalResult['total'] ?? 0;

    // Get listings with pagination
    $listingsQuery = "
        SELECT 
            l.listing_id,
            l.title,
            l.description,
            l.price,
            l.status,
            l.location_city,
            l.location_province,
            l.date_created,
            l.admin_approved,
            l.views_count,
            l.favorites_count,
            u.first_name as seller_first_name,
            u.last_name as seller_last_name,
            u.is_verified as seller_verified,
            u.profile_image_url as seller_avatar,
            c.category_name,
            c.category_slug,
            li.image_url as primary_image
        FROM listings l
        INNER JOIN users u ON l.seller_id = u.user_id
        INNER JOIN categories c ON l.category_id = c.category_id
        LEFT JOIN listing_images li ON l.listing_id = li.listing_id AND li.is_primary = TRUE
        WHERE $whereClause
        ORDER BY l.date_created DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = executeQuery($listingsQuery, array_merge($params, [
        ':limit' => $itemsPerPage,
        ':offset' => $offset
    ]));
    $listings = $stmt->fetchAll();

    // Get categories for filter dropdown
    $categoriesQuery = "
        SELECT category_id, category_name 
        FROM categories 
        WHERE is_active = TRUE 
        ORDER BY category_name
    ";
    $categories = getMultipleRecords($categoriesQuery);

} catch (Exception $e) {
    error_log("Admin listings error: " . $e->getMessage());
    $listings = [];
}

// Calculate pagination
$totalPages = ceil($totalListings / $itemsPerPage);

// Helper functions
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'expired': return 'bg-secondary';
        case 'sold': return 'bg-info';
        case 'removed': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getDefaultImage($categorySlug = 'default') {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listings Management - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .admin-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar flex-shrink-0 p-3" style="width: 280px;">
            <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="fas fa-handshake me-2"></i>
                <span class="fs-4">Admin Portal</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="listings.php" class="nav-link active">
                        <i class="fas fa-list me-2"></i>
                        Listings
                    </a>
                </li>
                <li>
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="transactions.php" class="nav-link">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Transactions
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reports
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Admin" width="32" height="32" class="rounded-circle me-2">
                    <strong>Admin User</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="#">Profile</a></li>
                    <li><a class="dropdown-item" href="#">Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Sign out</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <!-- Admin Header -->
            <header class="admin-header py-3 px-4 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Listings Management</h4>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-question-circle"></i>
                    </button>
                </div>
            </header>

            <!-- Admin Content -->
            <main class="p-4">
                <!-- Search and Filters -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <form method="GET" class="d-flex me-3">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search listings..." 
                                       value="<?= escapeHtml($searchQuery) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Preserve other filters -->
                            <?php if ($statusFilter): ?>
                                <input type="hidden" name="status" value="<?= escapeHtml($statusFilter) ?>">
                            <?php endif; ?>
                            <?php if ($categoryFilter): ?>
                                <input type="hidden" name="category" value="<?= escapeHtml($categoryFilter) ?>">
                            <?php endif; ?>
                            <?php if ($locationFilter): ?>
                                <input type="hidden" name="location" value="<?= escapeHtml($locationFilter) ?>">
                            <?php endif; ?>
                        </form>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                    id="filterDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i> Filters
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3" style="width: 250px;">
                                <form method="GET">
                                    <li>
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select mb-2">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                                            <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                                            <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Sold</option>
                                            <option value="removed" <?= $statusFilter === 'removed' ? 'selected' : '' ?>>Removed</option>
                                        </select>
                                    </li>
                                    <li>
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select mb-2">
                                            <option value="">All Categories</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['category_id'] ?>" 
                                                        <?= $categoryFilter == $category['category_id'] ? 'selected' : '' ?>>
                                                    <?= escapeHtml($category['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </li>
                                    <li>
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control mb-3" 
                                               placeholder="City or Province" 
                                               value="<?= escapeHtml($locationFilter) ?>">
                                    </li>
                                    <li>
                                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                    </li>
                                    <!-- Preserve search query -->
                                    <?php if ($searchQuery): ?>
                                        <input type="hidden" name="search" value="<?= escapeHtml($searchQuery) ?>">
                                    <?php endif; ?>
                                </form>
                            </ul>
                        </div>
                    </div>
                    <div class="text-muted">
                        Showing <?= number_format($totalListings) ?> listings
                    </div>
                </div>
                
                <!-- Listings Table -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Listing</th>
                                        <th>Seller</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($listings)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5>No listings found</h5>
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($listings as $listing): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           value="<?= $listing['listing_id'] ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= escapeHtml($listing['primary_image'] ?: getDefaultImage($listing['category_slug'])) ?>" 
                                                         class="rounded me-2" width="40" height="40" alt=""
                                                         onerror="this.src='<?= getDefaultImage() ?>'">
                                                    <div>
                                                        <div class="fw-bold"><?= escapeHtml($listing['title']) ?></div>
                                                        <small class="text-muted">ID: #LST-<?= $listing['listing_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= escapeHtml($listing['seller_avatar'] ?: 'https://randomuser.me/api/portraits/women/44.jpg') ?>" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="">
                                                    <div>
                                                        <span><?= escapeHtml($listing['seller_first_name'] . ' ' . substr($listing['seller_last_name'], 0, 1) . '.') ?></span>
                                                        <?php if ($listing['seller_verified']): ?>
                                                            <i class="fas fa-check-circle text-primary ms-1" title="Verified"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= escapeHtml($listing['category_name']) ?></td>
                                            <td><?= escapeHtml($listing['location_city']) ?></td>
                                            <td>R<?= number_format($listing['price'], 2) ?></td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($listing['status']) ?>">
                                                    <?= ucfirst($listing['status']) ?>
                                                </span>
                                                <?php if (!$listing['admin_approved']): ?>
                                                    <small class="text-warning d-block">Pending Approval</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?= date('M j, Y', strtotime($listing['date_created'])) ?></div>
                                                <small class="text-muted"><?= timeAgo($listing['date_created']) ?></small>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="view_listing.php?id=<?= $listing['listing_id'] ?>">
                                                            <i class="fas fa-eye me-2"></i> View</a></li>
                                                        <li><a class="dropdown-item" href="edit_listing.php?id=<?= $listing['listing_id'] ?>">
                                                            <i class="fas fa-edit me-2"></i> Edit</a></li>
                                                        <?php if (!$listing['admin_approved']): ?>
                                                        <li><a class="dropdown-item text-success" href="approve_listing.php?id=<?= $listing['listing_id'] ?>">
                                                            <i class="fas fa-check me-2"></i> Approve</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($listing['status'] === 'active'): ?>
                                                        <li><a class="dropdown-item text-warning" href="deactivate_listing.php?id=<?= $listing['listing_id'] ?>">
                                                            <i class="fas fa-ban me-2"></i> Deactivate</a></li>
                                                        <?php endif; ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="delete_listing.php?id=<?= $listing['listing_id'] ?>"
                                                               onclick="return confirm('Are you sure you want to delete this listing?')">
                                                            <i class="fas fa-trash me-2"></i> Delete</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Showing <?= number_format(($currentPage - 1) * $itemsPerPage + 1) ?> to 
                                    <?= number_format(min($currentPage * $itemsPerPage, $totalListings)) ?> of 
                                    <?= number_format($totalListings) ?> entries
                                </small>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?><?= $locationFilter ? '&location=' . urlencode($locationFilter) : '' ?>">Previous</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?><?= $locationFilter ? '&location=' . urlencode($locationFilter) : '' ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $categoryFilter ? '&category=' . urlencode($categoryFilter) : '' ?><?= $locationFilter ? '&location=' . urlencode($locationFilter) : '' ?>">Next</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>