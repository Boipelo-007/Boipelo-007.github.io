<?php
/**
 * Fixed Login System - Edu C2C Marketplace
 * Handles authentication with proper redirects and session management
 */

session_start();

// Database configuration
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Check if already logged in and redirect appropriately
if (isset($_SESSION['user_id'])) {
    $redirect_url = $_SESSION['user_type'] === 'seller' ? 'seller-dashboard.php' : 'index.php';
    header('Location: ' . $redirect_url);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['loginEmail'] ?? '');
    $password = $_POST['loginPassword'] ?? '';
    $remember_me = isset($_POST['rememberMe']);
    
    // Validation
    if (empty($email_or_phone) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $pdo = getDatabase();
            
            // Check if input is email or phone
            $is_email = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL);
            
            if ($is_email) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            } else {
                // Clean phone number (remove +27 prefix if present)
                $phone = preg_replace('/^\+27/', '', $email_or_phone);
                $stmt = $pdo->prepare("SELECT * FROM users WHERE phone_number = ? AND is_active = 1");
                $email_or_phone = $phone; // Use cleaned phone number
            }
            
            $stmt->execute([$email_or_phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful - Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_verified'] = $user['is_verified'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update last login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->execute([$user['user_id']]);
                
                // Handle remember me functionality
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    // Set secure cookie
                    setcookie('remember_token', $token, $expires, '/', '', 
                        isset($_SERVER['HTTPS']), true);
                    
                    // Store hashed token in database
                    $token_stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expires = FROM_UNIXTIME(?) WHERE user_id = ?");
                    $token_stmt->execute([hash('sha256', $token), $expires, $user['user_id']]);
                }
                
                // Determine redirect URL
                $redirect_url = 'index.php'; // Default redirect
                
                // Check for intended destination
                if (isset($_SESSION['intended_url'])) {
                    $redirect_url = $_SESSION['intended_url'];
                    unset($_SESSION['intended_url']);
                } else {
                    // Redirect based on user type
                    switch ($user['user_type']) {
                        case 'seller':
                            $redirect_url = 'seller-dashboard.php';
                            break;
                        case 'admin':
                            $redirect_url = 'admin/dashboard.php';
                            break;
                        default:
                            $redirect_url = 'index.php';
                            break;
                    }
                }
                
                // Redirect with success
                header('Location: ' . $redirect_url);
                exit();
                
            } else {
                $error_message = 'Invalid email/phone or password. Please try again.';
            }
            
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error_message = 'A system error occurred. Please try again later.';
        }
    }
}

// Check for remember me token on page load
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $pdo = getDatabase();
        $token_hash = hash('sha256', $_COOKIE['remember_token']);
        
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE remember_token = ? 
            AND remember_token_expires > NOW() 
            AND is_active = TRUE
        ");
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Auto-login user
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_verified'] = $user['is_verified'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Update last login
            $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->execute([$user['user_id']]);
            
            // Redirect to appropriate page
            $redirect_url = $user['user_type'] === 'seller' ? 'seller-dashboard.php' : 'index.php';
            header('Location: ' . $redirect_url);
            exit();
        }
    } catch (Exception $e) {
        error_log('Remember token error: ' . $e->getMessage());
        // Clear invalid cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Edu C2C Marketplace</title>
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
                </ul>
                <div class="d-flex">
                    <a href="register.php" class="btn btn-outline-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h2 class="mb-3">Welcome Back</h2>
                            <p class="text-muted">Sign in to access your account</p>
                        </div>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form id="loginForm" method="POST" action="login.php">
                            <div class="mb-4">
                                <label for="loginEmail" class="form-label">Email or Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="loginEmail" name="loginEmail" 
                                           value="<?php echo htmlspecialchars($_POST['loginEmail'] ?? ''); ?>"
                                           placeholder="Enter your email or phone number" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="loginPassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="loginPassword" name="loginPassword" 
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('loginPassword')">
                                        <i class="fas fa-eye" id="loginPassword-icon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                                        <label class="form-check-label" for="rememberMe">Remember me</label>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <a href="forgot-password.php" class="text-primary text-decoration-none">Forgot password?</a>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3 mb-4">
                                <span class="d-none spinner-border spinner-border-sm me-2" id="loginSpinner"></span>
                                Sign In
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary">Sign up</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">By continuing, you agree to our <a href="terms.php" class="text-primary">Terms of Service</a> and <a href="privacy.php" class="text-primary">Privacy Policy</a></small>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="small mb-0">© 2025 Edu C2C Platform. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Handle form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = document.getElementById('loginSpinner');
            
            // Show loading state
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';
        });
    </script>
</body>
</html>