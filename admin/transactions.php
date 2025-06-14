<?php
/**
 * Admin Transactions Management - Edu C2C Marketplace
 * Database Integration with InfinityFree MySQL
 */

// Include database configuration
require_once '../config/database.php';

// Simple authentication check
session_start();

// Initialize variables
$transactions = [];
$totalTransactions = 0;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 25;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filter parameters
$statusFilter = $_GET['status'] ?? '';
$paymentMethodFilter = $_GET['payment_method'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

// Summary statistics
$summaryStats = [
    'total_transactions' => 0,
    'total_volume' => 0,
    'commission_earned' => 0,
    'failed_transactions' => 0
];

try {
    // Build WHERE clause for filters
    $whereConditions = ["1=1"]; // Base condition
    $params = [];

    if ($statusFilter) {
        $whereConditions[] = "t.transaction_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($paymentMethodFilter) {
        $whereConditions[] = "t.payment_method = :payment_method";
        $params[':payment_method'] = $paymentMethodFilter;
    }

    if ($dateFrom) {
        $whereConditions[] = "DATE(t.date_created) >= :date_from";
        $params[':date_from'] = $dateFrom;
    }

    if ($dateTo) {
        $whereConditions[] = "DATE(t.date_created) <= :date_to";
        $params[':date_to'] = $dateTo;
    }

    if ($searchQuery) {
        $whereConditions[] = "(CONCAT('TRX-', t.transaction_id) LIKE :search OR l.title LIKE :search OR CONCAT(buyer.first_name, ' ', buyer.last_name) LIKE :search OR CONCAT(seller.first_name, ' ', seller.last_name) LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get summary statistics
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_transactions,
            FORMAT(COALESCE(SUM(CASE WHEN transaction_status = 'completed' THEN total_amount END), 0), 2) as total_volume,
            FORMAT(COALESCE(SUM(CASE WHEN transaction_status = 'completed' THEN total_amount * 0.05 END), 0), 2) as commission_earned,
            COUNT(CASE WHEN transaction_status = 'failed' THEN 1 END) as failed_transactions
        FROM transactions t
        INNER JOIN listings l ON t.listing_id = l.listing_id
        INNER JOIN users buyer ON t.buyer_id = buyer.user_id
        INNER JOIN users seller ON t.seller_id = seller.user_id
        WHERE $whereClause
    ";
    
    $summaryResult = getSingleRecord($summaryQuery, $params);
    if ($summaryResult) {
        $summaryStats = $summaryResult;
    }

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total
        FROM transactions t
        INNER JOIN listings l ON t.listing_id = l.listing_id
        INNER JOIN users buyer ON t.buyer_id = buyer.user_id
        INNER JOIN users seller ON t.seller_id = seller.user_id
        WHERE $whereClause
    ";
    
    $totalResult = getSingleRecord($countQuery, $params);
    $totalTransactions = $totalResult['total'] ?? 0;

    // Get transactions with pagination
    $transactionsQuery = "
        SELECT 
            t.transaction_id,
            t.transaction_type,
            t.quantity,
            t.total_amount,
            t.delivery_fee,
            t.payment_method,
            t.payment_status,
            t.transaction_status,
            t.delivery_method,
            t.completion_date,
            t.date_created,
            t.notes,
            l.title as item_title,
            l.price as item_price,
            buyer.first_name as buyer_first_name,
            buyer.last_name as buyer_last_name,
            buyer.profile_image_url as buyer_avatar,
            seller.first_name as seller_first_name,
            seller.last_name as seller_last_name,
            seller.profile_image_url as seller_avatar,
            seller.is_verified as seller_verified
        FROM transactions t
        INNER JOIN listings l ON t.listing_id = l.listing_id
        INNER JOIN users buyer ON t.buyer_id = buyer.user_id
        INNER JOIN users seller ON t.seller_id = seller.user_id
        WHERE $whereClause
        ORDER BY t.date_created DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = executeQuery($transactionsQuery, array_merge($params, [
        ':limit' => $itemsPerPage,
        ':offset' => $offset
    ]));
    $transactions = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Admin transactions error: " . $e->getMessage());
    $transactions = [];
}

// Calculate pagination
$totalPages = ceil($totalTransactions / $itemsPerPage);

// Helper functions
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'completed': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'failed': return 'bg-danger';
        case 'cancelled': return 'bg-secondary';
        case 'in_progress': return 'bg-info';
        case 'disputed': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function getPaymentMethodBadgeClass($method) {
    switch (strtolower($method)) {
        case 'mobile_money': return 'payment-method-mobile';
        case 'cash': return 'payment-method-cash';
        case 'bank_transfer': return 'payment-method-bank';
        case 'escrow': return 'payment-method-escrow';
        case 'barter': return 'payment-method-barter';
        default: return 'bg-secondary';
    }
}

function formatPaymentMethod($method) {
    $methods = [
        'mobile_money' => 'Mobile Money',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'escrow' => 'Escrow',
        'barter' => 'Barter'
    ];
    
    return $methods[$method] ?? ucfirst(str_replace('_', ' ', $method));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions Management - Edu C2C Marketplace</title>
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
        .payment-method-cash { background: #28a745; }
        .payment-method-mobile { background: #17a2b8; }
        .payment-method-bank { background: #6f42c1; }
        .payment-method-escrow { background: #fd7e14; }
        .payment-method-barter { background: #20c997; }
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
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li>
                    <a href="transactions.php" class="nav-link active">
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
                <h4 class="mb-0">Transaction Management</h4>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger badge-sm">3</span>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-question-circle"></i>
                    </button>
                </div>
            </header>

            <!-- Admin Content -->
            <main class="p-4">
                <!-- Transaction Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Volume</h6>
                                        <h3 class="mb-0">R<?= $summaryStats['total_volume'] ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-coins text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 15.7%</span>
                                    <span class="text-muted">vs last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Commission Earned</h6>
                                        <h3 class="mb-0">R<?= $summaryStats['commission_earned'] ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-percentage text-warning"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 12.3%</span>
                                    <span class="text-muted">vs last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Failed Transactions</h6>
                                        <h3 class="mb-0"><?= number_format($summaryStats['failed_transactions']) ?></h3>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-exclamation-triangle text-danger"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-danger"><i class="fas fa-arrow-down"></i> 2.1%</span>
                                    <span class="text-muted">vs last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <form method="GET" class="d-flex me-3">
                            <div class="input-group" style="width: 300px;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search transactions..." 
                                       value="<?= escapeHtml($searchQuery) ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <!-- Preserve other filters -->
                            <?php if ($statusFilter): ?>
                                <input type="hidden" name="status" value="<?= escapeHtml($statusFilter) ?>">
                            <?php endif; ?>
                            <?php if ($paymentMethodFilter): ?>
                                <input type="hidden" name="payment_method" value="<?= escapeHtml($paymentMethodFilter) ?>">
                            <?php endif; ?>
                            <?php if ($dateFrom): ?>
                                <input type="hidden" name="date_from" value="<?= escapeHtml($dateFrom) ?>">
                            <?php endif; ?>
                            <?php if ($dateTo): ?>
                                <input type="hidden" name="date_to" value="<?= escapeHtml($dateTo) ?>">
                            <?php endif; ?>
                        </form>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                    id="filterDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i> Filters
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-3" style="width: 350px;">
                                <form method="GET">
                                    <li>
                                        <label class="form-label">Date Range</label>
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <input type="date" name="date_from" class="form-control form-control-sm" 
                                                       value="<?= escapeHtml($dateFrom) ?>" placeholder="From">
                                            </div>
                                            <div class="col-6">
                                                <input type="date" name="date_to" class="form-control form-control-sm" 
                                                       value="<?= escapeHtml($dateTo) ?>" placeholder="To">
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <label class="form-label">Transaction Status</label>
                                        <select name="status" class="form-select mb-2">
                                            <option value="">All Statuses</option>
                                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option value="disputed" <?= $statusFilter === 'disputed' ? 'selected' : '' ?>>Disputed</option>
                                        </select>
                                    </li>
                                    <li>
                                        <label class="form-label">Payment Method</label>
                                        <select name="payment_method" class="form-select mb-3">
                                            <option value="">All Methods</option>
                                            <option value="cash" <?= $paymentMethodFilter === 'cash' ? 'selected' : '' ?>>Cash</option>
                                            <option value="mobile_money" <?= $paymentMethodFilter === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
                                            <option value="bank_transfer" <?= $paymentMethodFilter === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                            <option value="escrow" <?= $paymentMethodFilter === 'escrow' ? 'selected' : '' ?>>Escrow</option>
                                            <option value="barter" <?= $paymentMethodFilter === 'barter' ? 'selected' : '' ?>>Barter</option>
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
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <!-- Transactions Table -->
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
                                        <th>Transaction ID</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Item</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                            <h5>No transactions found</h5>
                                            <p class="text-muted">Try adjusting your search criteria</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           value="<?= $transaction['transaction_id'] ?>">
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">#TRX-<?= $transaction['transaction_id'] ?></div>
                                                    <?php if ($transaction['transaction_type'] === 'barter'): ?>
                                                        <small class="text-info">Barter Trade</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Purchase</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= escapeHtml($transaction['buyer_avatar'] ?: 'https://randomuser.me/api/portraits/men/22.jpg') ?>" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="">
                                                    <span><?= escapeHtml($transaction['buyer_first_name'] . ' ' . substr($transaction['buyer_last_name'], 0, 1) . '.') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= escapeHtml($transaction['seller_avatar'] ?: 'https://randomuser.me/api/portraits/women/44.jpg') ?>" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="">
                                                    <div>
                                                        <span><?= escapeHtml($transaction['seller_first_name'] . ' ' . substr($transaction['seller_last_name'], 0, 1) . '.') ?></span>
                                                        <?php if ($transaction['seller_verified']): ?>
                                                            <i class="fas fa-check-circle text-primary ms-1" title="Verified"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?= escapeHtml($transaction['item_title']) ?></div>
                                                    <small class="text-muted">Qty: <?= $transaction['quantity'] ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($transaction['transaction_type'] === 'barter'): ?>
                                                    <div>
                                                        <div class="fw-bold">R0.00</div>
                                                        <small class="text-muted">Barter Value: ~R<?= number_format($transaction['item_price'] * $transaction['quantity'], 0) ?></small>
                                                    </div>
                                                <?php else: ?>
                                                    <div>
                                                        <div class="fw-bold">R<?= number_format($transaction['total_amount'], 2) ?></div>
                                                        <?php if ($transaction['delivery_fee'] > 0): ?>
                                                            <small class="text-muted">+ R<?= number_format($transaction['delivery_fee'], 2) ?> delivery</small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= getPaymentMethodBadgeClass($transaction['payment_method']) ?> text-white">
                                                    <?= formatPaymentMethod($transaction['payment_method']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($transaction['transaction_status']) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $transaction['transaction_status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?= date('M j, Y', strtotime($transaction['date_created'])) ?></div>
                                                    <small class="text-muted"><?= timeAgo($transaction['date_created']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-h"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="view_transaction.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-eye me-2"></i> View Details</a></li>
                                                        <li><a class="dropdown-item" href="download_receipt.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-download me-2"></i> Download Receipt</a></li>
                                                        <?php if ($transaction['transaction_status'] === 'pending'): ?>
                                                        <li><a class="dropdown-item text-success" href="approve_transaction.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-check me-2"></i> Approve</a></li>
                                                        <?php endif; ?>
                                                        <?php if (in_array($transaction['transaction_status'], ['completed', 'failed'])): ?>
                                                        <li><a class="dropdown-item text-warning" href="refund_transaction.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-undo me-2"></i> Initiate Refund</a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="contact_parties.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-envelope me-2"></i> Contact Parties</a></li>
                                                        <?php if ($transaction['transaction_status'] === 'disputed'): ?>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-info" href="resolve_dispute.php?id=<?= $transaction['transaction_id'] ?>">
                                                            <i class="fas fa-gavel me-2"></i> Resolve Dispute</a></li>
                                                        <?php endif; ?>
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
                                    <?= number_format(min($currentPage * $itemsPerPage, $totalTransactions)) ?> of 
                                    <?= number_format($totalTransactions) ?> transactions
                                </small>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= http_build_query($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">Previous</a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= http_build_query($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= http_build_query($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">Next</a>
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

        // Export functionality
        document.querySelector('.btn-outline-primary').addEventListener('click', function() {
            // Create CSV export URL with current filters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('export', 'csv');
            window.location.href = 'export_transactions.php?' + urlParams.toString();
        });
    </script>
</body>
</html>