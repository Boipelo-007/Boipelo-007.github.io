


<?php
/**
 * Admin Users Management - Edu C2C Marketplace
 * Database Integration with InfinityFree MySQL
 */

// Include database configuration
require_once '../config/database.php';

// Simple authentication check
session_start();

// Initialize variables
$users = [];
$totalUsers = 0;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter parameters
$userTypeFilter = $_GET['user_type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$verificationFilter = $_GET['verification'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

try {
    // Build WHERE clause for filters
    $whereConditions = ["1=1"]; // Base condition
    $params = [];

    if ($userTypeFilter) {
        $whereConditions[] = "u.user_type = :user_type";
        $params[':user_type'] = $userTypeFilter;
    }

    if ($statusFilter === 'active') {
        $whereConditions[] = "u.is_active = TRUE";
    } elseif ($statusFilter === 'inactive') {
        $whereConditions[] = "u.is_active = FALSE";
    }

    if ($verificationFilter === 'verified') {
        $whereConditions[] = "u.is_verified = TRUE";
    } elseif ($verificationFilter === 'unverified') {
        $whereConditions[] = "u.is_verified = FALSE";
    }

    if ($searchQuery) {
        $whereConditions[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone_number LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total
        FROM users u
        WHERE $whereClause
    ";
    
    $totalResult = getSingleRecord($countQuery, $params);
    $totalUsers = $totalResult['total'] ?? 0;

    // Get users with pagination
    $usersQuery = "
        SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone_number,
            u.user_type,
            u.is_verified,
            u.is_active,
            u.date_created,
            u.last_login,
            u.profile_image_url,
            u.preferred_language,
            ul.city,
            ul.province,
            -- User statistics
            (SELECT COUNT(*) FROM listings WHERE seller_id = u.user_id AND status = 'active') as active_listings,
            (SELECT COUNT(*) FROM transactions WHERE seller_id = u.user_id AND transaction_status = 'completed') as completed_sales,
            (SELECT COUNT(*) FROM transactions WHERE buyer_id = u.user_id AND transaction_status = 'completed') as completed_purchases,
            (SELECT AVG(rating) FROM user_ratings WHERE rated_user_id = u.user_id) as average_rating,
            (SELECT COUNT(*) FROM user_ratings WHERE rated_user_id = u.user_id) as total_ratings
        FROM users u
        LEFT JOIN user_locations ul ON u.user_id = ul.user_id AND ul.is_primary = TRUE
        WHERE $whereClause
        ORDER BY u.date_created DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = executeQuery($usersQuery, array_merge($params, [
        ':limit' => $itemsPerPage,
        ':offset' => $offset
    ]));
    $users = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    $users = [];
}

// Calculate pagination
$totalPages = ceil($totalUsers / $itemsPerPage);

// Helper functions
function getUserTypeBadgeClass($userType) {
    switch (strtolower($userType)) {
        case 'seller': return 'bg-success';
        case 'buyer': return 'bg-info';
        case 'admin': return 'bg-primary';
        default: return 'bg-secondary';
    }
}

function getStatusBadgeClass($isActive) {
    return $isActive ? 'bg-success' : 'bg-danger';
}

function formatRating($rating) {
    if (!$rating) return 'No ratings';
    return number_format($rating, 1) . '/5';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Edu C2C Marketplace</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
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
                    <a href="listings.php" class="nav-link">
                        <i class="fas fa-list me-2"></i>
                        Listings
                    </a>
                </li>
                <li>
                    <a href="users.php" class="nav-link active">
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
                <h4 class="mb-0">Users Management</h4>
                <div>
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
                                       placeholder="Search users..." 
                                       value="<?= escapeHtml($searchQuery) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Preserve other filters -->
                            <?php if ($userTypeFilter): ?>
                                <input type="hidden" name="user_type" value="<?= escapeHtml($userTypeFilter) ?>">
                            <?php endif; ?>
                            <?php if ($statusFilter): ?>
                                <input type="hidden" name="status" value="<?= escapeHtml($statusFilter) ?>">
                            <?php endif; ?>
                            <?php if ($verificationFilter): ?>
                                <input type="hidden" name="verification" value="<?= escapeHtml($verificationFilter) ?>">
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
                                        <label class="form-label">User Type</label>
                                        <select name="user_type" class="form-select mb-2">
                                            <option value="">All Users</option>
                                            <option value="buyer" <?= $userTypeFilter === 'buyer' ? 'selected' : '' ?>>Buyers</option>
                                            <option value="seller" <?= $userTypeFilter === 'seller' ? 'selected' : '' ?>>Sellers</option>
                                            <option value="admin" <?= $userTypeFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                                        </select>
                                    </li>
                                    <li>
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select mb-2">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        </select>
                                    </li>
                                    <li>
                                        <label class="form-label">Verification</label>
                                        <select name="verification" class="form-select mb-3">
                                            <option value="">All</option>
                                            <option value="verified" <?= $verificationFilter === 'verified' ? 'selected' : '' ?>>Verified</option>
                                            <option value="unverified" <?= $verificationFilter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                                        </select>
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
                        Showing <?= number_format($totalUsers) ?> users
                    </div>
                </div>
                
                <!-- Users Table -->
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
                                        <th>User</th>
                                        <th>Contact</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Activity</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h5>No users found</h5>
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           value="<?= $user['user_id'] ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= escapeHtml($user['profile_image_url'] ?: 'https://randomuser.me/api/portraits/women/44.jpg') ?>" 
                                                         class="rounded-circle me-2 user-avatar" alt="">
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?= escapeHtml($user['first_name'] . ' ' . $user['last_name']) ?>
                                                            <?php if ($user['is_verified']): ?>
                                                                <i class="fas fa-check-circle text-primary ms-1" title="Verified"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">ID: #USR-<?= $user['user_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= escapeHtml($user['email']) ?></div>
                                                <small class="text-muted"><?= escapeHtml($user['phone_number']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= getUserTypeBadgeClass($user['user_type']) ?>">
                                                    <?= ucfirst($user['user_type']) ?>
                                                </span>
                                                <?php if ($user['preferred_language'] !== 'en'): ?>
                                                    <small class="text-muted d-block">
                                                        Lang: <?= strtoupper($user['preferred_language']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['city'] || $user['province']): ?>
                                                    <?= escapeHtml(trim($user['city'] . ', ' . $user['province'], ', ')) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($user['is_active']) ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                                <?php if (!$user['is_verified']): ?>
                                                    <small class="text-warning d-block">Unverified</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['user_type'] === 'seller'): ?>
                                                    <small class="text-muted">
                                                        <?= $user['active_listings'] ?> listings<br>
                                                        <?= $user['completed_sales'] ?> sales<br>
                                                        <?= formatRating($user['average_rating']) ?>
                                                    </small>
                                                <?php elseif ($user['user_type'] === 'buyer'): ?>
                                                    <small class="text-muted">
                                                        <?= $user['completed_purchases'] ?> purchases
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">Administrator</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?= date('M j, Y', strtotime($user['date_created'])) ?></div>
                                                <small class="text-muted">
                                                    <?php if ($user['last_login']): ?>
                                                        Last: <?= timeAgo($user['last_login']) ?>
                                                    <?php else: ?>
                                                        Never logged in
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="view_user.php?id=<?= $user['user_id'] ?>">
                                                            <i class="fas fa-eye me-2"></i> View Profile</a></li>
                                                        <li><a class="dropdown-item" href="edit_user.php?id=<?= $user['user_id'] ?>">
                                                            <i class="fas fa-edit me-2"></i> Edit</a></li>
                                                        <?php if (!$user['is_verified']): ?>
                                                        <li><a class="dropdown-item text-success" href="verify_user.php?id=<?= $user['user_id'] ?>">
                                                            <i class="fas fa-check me-2"></i> Verify</a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="message_user.php?id=<?= $user['user_id'] ?>">
                                                            <i class="fas fa-envelope me-2"></i> Send Message</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <?php if ($user['is_active']): ?>
                                                        <li><a class="dropdown-item text-warning" href="suspend_user.php?id=<?= $user['user_id'] ?>"
                                                               onclick="return confirm('Are you sure you want to suspend this user?')">
                                                            <i class="fas fa-user-slash me-2"></i> Suspend</a></li>
                                                        <?php else: ?>
                                                        <li><a class="dropdown-item text-success" href="activate_user.php?id=<?= $user['user_id'] ?>">
                                                            <i class="fas fa-user-check me-2"></i> Activate</a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item text-danger" href="delete_user.php?id=<?= $user['user_id'] ?>"
                                                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
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
                                    <?= number_format(min($currentPage * $itemsPerPage, $totalUsers)) ?> of 
                                    <?= number_format($totalUsers) ?> entries
                                </small>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $userTypeFilter ? '&user_type=' . urlencode($userTypeFilter) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $verificationFilter ? '&verification=' . urlencode($verificationFilter) : '' ?>">Previous</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $userTypeFilter ? '&user_type=' . urlencode($userTypeFilter) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $verificationFilter ? '&verification=' . urlencode($verificationFilter) : '' ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $userTypeFilter ? '&user_type=' . urlencode($userTypeFilter) : '' ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?><?= $verificationFilter ? '&verification=' . urlencode($verificationFilter) : '' ?>">Next</a>
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