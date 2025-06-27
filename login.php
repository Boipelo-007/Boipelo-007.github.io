<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: listings.php');
    exit();
}

// Database configuration
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];
    $remember_me = isset($_POST['rememberMe']);
    
    // Validation
    if (empty($email_or_phone) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Check if input is email or phone
            $is_email = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL);
            
            if ($is_email) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            } else {
                // Clean phone number (remove +27 prefix if present)
                $phone = preg_replace('/^\+27/', '', $email_or_phone);
                $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND status = 'active'");
            }
            
            $stmt->execute([$email_or_phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                // Update last login
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Handle remember me
                if ($remember_me) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    
                    // Store token in database
                    $token_stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $token_stmt->execute([hash('sha256', $token), $user['id']]);
                }
                
                // Redirect based on user type
                if ($user['user_type'] === 'seller') {
                    header('Location: https://edumarket.lovestoblog.com/listings.php');
                } else {
                    header('Location: https://edumarket.lovestoblog.com/listings.php');
                }
                exit();
            } else {
                $error_message = 'Invalid credentials. Please try again.';
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred. Please try again later.';
            error_log('Login error: ' . $e->getMessage());
        }
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
    <!-- Navigation (minimal version) -->
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
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm">
                            <div class="mb-4">
                                <label for="loginEmail" class="form-label">Email or Phone Number*</label>
                                <input type="text" class="form-control form-control-lg" id="loginEmail" name="loginEmail" 
                                       value="<?php echo isset($_POST['loginEmail']) ? htmlspecialchars($_POST['loginEmail']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="loginPassword" class="form-label">Password*</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control form-control-lg" id="loginPassword" name="loginPassword" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2" 
                                            onclick="togglePassword('loginPassword')" style="border: none; background: none;">
                                        <i class="fas fa-eye" id="loginPassword-icon"></i>
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                                        <label class="form-check-label" for="rememberMe">Remember me</label>
                                    </div>
                                    <a href="forgot-password.php" class="text-primary">Forgot password?</a>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 mb-4">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="loginSpinner" role="status"></span>
                                Sign In
                            </button>
                            
                            <div class="text-center mb-4 position-relative">
                                <hr>
                                <span class="bg-white px-3 position-absolute top-50 start-50 translate-middle">Or</span>
                            </div>
                            
                            <div class="d-grid gap-3">
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="socialLogin('google')">
                                    <i class="fab fa-google me-2"></i> Continue with Google
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="socialLogin('facebook')">
                                    <i class="fab fa-facebook-f me-2"></i> Continue with Facebook
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="socialLogin('whatsapp')">
                                    <i class="fab fa-whatsapp me-2"></i> Continue with WhatsApp
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary">Sign up</a></p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">By continuing, you agree to our <a href="terms.php" class="text-primary">Terms of Service</a> and <a href="privacy.php" class="text-primary">Privacy Policy</a></small>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (minimal version) -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <div class="social-icons mb-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                    <p class="small mb-0">© 2025 Edu C2C Platform. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    
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

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = document.getElementById('loginSpinner');
            
            // Show loading state
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
        });

        // Social login handlers (placeholder functions)
        function socialLogin(provider) {
            alert('Social login with ' + provider + ' will be implemented with OAuth integration.');
            // Implement actual social login here
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>