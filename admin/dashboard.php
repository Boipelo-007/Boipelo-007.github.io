<?php
/**
 * Admin Dashboard - Edu C2C Marketplace
 * Database Integration with InfinityFree MySQL
 */

// Include database configuration
require_once '../config/database.php';

// Simple authentication check (you should implement proper session management)
session_start();

// Initialize dashboard data
$dashboardData = [
    'total_users' => 0,
    'active_listings' => 0,
    'total_transactions' => 0,
    'pending_approvals' => 0,
    'recent_activity' => [],
    'recent_transactions' => [],
    'user_registrations' => [],
    'system_status' => []
];

try {
    // Get summary statistics
    $statsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = TRUE) as total_users,
            (SELECT COUNT(*) FROM listings WHERE status = 'active' AND admin_approved = TRUE) as active_listings,
            (SELECT FORMAT(COALESCE(SUM(total_amount), 0), 2) FROM transactions WHERE transaction_status = 'completed') as total_revenue,
            (SELECT COUNT(*) FROM listings WHERE admin_approved = FALSE AND status = 'active') as pending_approvals
    ";
    
    $stats = getSingleRecord($statsQuery);
    if ($stats) {
        $dashboardData['total_users'] = $stats['total_users'];
        $dashboardData['active_listings'] = $stats['active_listings'];
        $dashboardData['total_revenue'] = $stats['total_revenue'];
        $dashboardData['pending_approvals'] = $stats['pending_approvals'];
    }

    // Get recent activity
    $activityQuery = "
        SELECT 
            u.first_name,
            u.last_name,
            u.profile_image_url,
            al.action_type,
            al.table_name,
            al.date_created,
            CASE 
                WHEN al.action_type = 'listing_created' THEN 'New Listing'
                WHEN al.action_type = 'transaction_completed' THEN 'Purchase'
                WHEN al.action_type = 'user_registered' THEN 'New User'
                WHEN al.action_type = 'user_verified' THEN 'Account Verified'
                ELSE al.action_type
            END as action_display,
            CASE 
                WHEN al.table_name = 'listings' THEN (SELECT title FROM listings WHERE listing_id = al.record_id)
                WHEN al.table_name = 'transactions' THEN 'Transaction'
                ELSE 'System'
            END as item_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.user_id
        ORDER BY al.date_created DESC
        LIMIT 10
    ";
    
    $dashboardData['recent_activity'] = getMultipleRecords($activityQuery);

    // Get recent transactions
    $transactionsQuery = "
        SELECT 
            t.transaction_id,
            t.total_amount,
            t.transaction_status,
            t.date_created,
            buyer.first_name as buyer_name,
            seller.first_name as seller_name,
            l.title as item_title
        FROM transactions t
        LEFT JOIN users buyer ON t.buyer_id = buyer.user_id
        LEFT JOIN users seller ON t.seller_id = seller.user_id
        LEFT JOIN listings l ON t.listing_id = l.listing_id
        ORDER BY t.date_created DESC
        LIMIT 10
    ";
    
    $dashboardData['recent_transactions'] = getMultipleRecords($transactionsQuery);

    // Get user registration data for chart (last 7 months)
    $registrationQuery = "
        SELECT 
            DATE_FORMAT(date_created, '%b') as month,
            user_type,
            COUNT(*) as count
        FROM users 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(date_created, '%Y-%m'), user_type
        ORDER BY date_created ASC
    ";
    
    $dashboardData['user_registrations'] = getMultipleRecords($registrationQuery);

    // System status checks
    $dashboardData['system_status'] = [
        'server_load' => rand(35, 50), // Simulated
        'database_status' => 'Healthy',
        'storage_used' => rand(60, 75) // Simulated
    ];

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    // Continue with default values
}

// Helper functions
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'completed': return 'bg-success';
        case 'pending': return 'bg-warning';
        case 'failed': return 'bg-danger';
        case 'in_progress': return 'bg-info';
        default: return 'bg-secondary';
    }
}

function formatCurrency($amount) {
    return 'R' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
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
        .metric-card {
            transition: all 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
                    <a href="dashboard.php" class="nav-link active">
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
                <h4 class="mb-0">Dashboard</h4>
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
                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm metric-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Users</h6>
                                        <h3 class="mb-0"><?= number_format($dashboardData['total_users']) ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-users text-primary"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 12.5%</span>
                                    <span class="text-muted">since last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm metric-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Active Listings</h6>
                                        <h3 class="mb-0"><?= number_format($dashboardData['active_listings']) ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-list text-success"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 8.3%</span>
                                    <span class="text-muted">since last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm metric-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Revenue</h6>
                                        <h3 class="mb-0">R<?= $dashboardData['total_revenue'] ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-coins text-warning"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> 15.2%</span>
                                    <span class="text-muted">since last month</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm metric-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Pending Approvals</h6>
                                        <h3 class="mb-0"><?= number_format($dashboardData['pending_approvals']) ?></h3>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-clock text-danger"></i>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <span class="text-warning"><i class="fas fa-exclamation-triangle"></i></span>
                                    <span class="text-muted">requires attention</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Recent Activity</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Action</th>
                                                <th>Item</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dashboardData['recent_activity'] as $activity): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $activity['profile_image_url'] ?: 'https://randomuser.me/api/portraits/women/44.jpg' ?>" 
                                                             class="rounded-circle me-2" width="30" height="30" alt="">
                                                        <span><?= escapeHtml($activity['first_name'] . ' ' . substr($activity['last_name'], 0, 1) . '.') ?></span>
                                                    </div>
                                                </td>
                                                <td><?= escapeHtml($activity['action_display']) ?></td>
                                                <td><?= escapeHtml($activity['item_name'] ?: '-') ?></td>
                                                <td><?= timeAgo($activity['date_created']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary text-start">
                                        <i class="fas fa-user-check me-2"></i> Verify User
                                    </button>
                                    <button class="btn btn-outline-success text-start">
                                        <i class="fas fa-check-circle me-2"></i> Approve Listing
                                    </button>
                                    <button class="btn btn-outline-warning text-start">
                                        <i class="fas fa-exclamation-triangle me-2"></i> Flagged Content
                                    </button>
                                    <button class="btn btn-outline-info text-start">
                                        <i class="fas fa-chart-line me-2"></i> Generate Report
                                    </button>
                                    <button class="btn btn-outline-secondary text-start">
                                        <i class="fas fa-bullhorn me-2"></i> Send Announcement
                                    </button>
                                </div>
                                <hr>
                                <h6 class="mb-3">System Status</h6>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Server Load</span>
                                        <span><?= $dashboardData['system_status']['server_load'] ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?= $dashboardData['system_status']['server_load'] ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Database</span>
                                        <span><?= $dashboardData['system_status']['database_status'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Storage</span>
                                        <span><?= $dashboardData['system_status']['storage_used'] ?>% used</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?= $dashboardData['system_status']['storage_used'] ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Recent Transactions</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($dashboardData['recent_transactions'], 0, 5) as $transaction): ?>
                                            <tr>
                                                <td>#TRX-<?= $transaction['transaction_id'] ?></td>
                                                <td>R<?= number_format($transaction['total_amount'], 0) ?></td>
                                                <td>
                                                    <span class="badge <?= getStatusBadgeClass($transaction['transaction_status']) ?>">
                                                        <?= ucfirst($transaction['transaction_status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= timeAgo($transaction['date_created']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="transactions.php" class="btn btn-sm btn-outline-primary w-100 mt-2">View All Transactions</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">User Registrations</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="userRegistrationsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    <script>
        // User registrations chart
        const ctx = document.getElementById('userRegistrationsChart').getContext('2d');
        
        // Process PHP data for chart
        const registrationData = <?= json_encode($dashboardData['user_registrations']) ?>;
        
        // Create chart data structure
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
        const buyerData = new Array(labels.length).fill(0);
        const sellerData = new Array(labels.length).fill(0);
        
        // Fill with actual data
        registrationData.forEach(item => {
            const monthIndex = labels.indexOf(item.month);
            if (monthIndex !== -1) {
                if (item.user_type === 'buyer') {
                    buyerData[monthIndex] = parseInt(item.count);
                } else if (item.user_type === 'seller') {
                    sellerData[monthIndex] = parseInt(item.count);
                }
            }
        });
        
        const userRegistrationsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Buyers',
                    data: buyerData,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Sellers',
                    data: sellerData,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>