<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database configuration
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Initialize database if needed

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_type = $_POST['userType'] ?? 'buyer';
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $location = $_POST['location'] ?? '';
    $terms_agreed = isset($_POST['termsAgree']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($phone) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!$terms_agreed) {
        $error_message = 'You must agree to the terms and conditions.';
    } else {
        try {
            $pdo = getDatabase();
            
            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) === 9) {
                $phone = '27' . $phone; // Add country code
            } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                $phone = '27' . substr($phone, 1); // Replace 0 with 27
            }
            $phone_display = '+' . $phone;
            
            // Check if email or phone already exists
            $check_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
            $check_stmt->execute([$email, $phone_display]);
            
            if ($check_stmt->fetch()) {
                $error_message = 'Email or phone number already registered.';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (first_name, last_name, email, phone_number, password_hash, user_type, date_created) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $insert_stmt->execute([
                    $first_name,
                    $last_name,
                    $email ?: null,
                    $phone_display,
                    $password_hash,
                    $user_type
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Store location if provided
                if (!empty($location) && $location !== 'Other') {
                    try {
                        // Create user_locations table if it doesn't exist
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS user_locations (
                                location_id INT PRIMARY KEY AUTO_INCREMENT,
                                user_id INT NOT NULL,
                                province VARCHAR(100),
                                city VARCHAR(100) NOT NULL,
                                area_neighborhood VARCHAR(200),
                                is_primary BOOLEAN DEFAULT TRUE,
                                date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                            )
                        ");
                        
                        $location_stmt = $pdo->prepare("
                            INSERT INTO user_locations (user_id, city, is_primary) 
                            VALUES (?, ?, TRUE)
                        ");
                        $location_stmt->execute([$user_id, $location]);
                    } catch (Exception $e) {
                        // Location storage failed, but user was created successfully
                        error_log("Failed to store user location: " . $e->getMessage());
                    }
                }
                
                $success_message = 'Account created successfully! You can now log in.';
                
                // Auto-login the user
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_type'] = $user_type;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                
                // Redirect after 2 seconds
                header("refresh:2;url=" . ($user_type === 'seller' ? 'sell.php' : 'index.php'));
            }
        } catch (PDOException $e) {
            error_log('Registration error: ' . $e->getMessage());
            $error_message = 'Registration failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Edu C2C Marketplace</title>
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
                    <a href="login.php" class="btn btn-outline-primary">Login</a>
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
                            <h2 class="mb-3">Create Your Account</h2>
                            <p class="text-muted">Join thousands of buyers and sellers in South Africa's informal economy</p>
                        </div>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                                <br><small>Redirecting you to the platform...</small>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="registerForm">
                            <div class="mb-4">
                                <label class="form-label">I want to join as*</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="userType" id="buyer" value="buyer" autocomplete="off" 
                                           <?php echo (!isset($_POST['userType']) || $_POST['userType'] === 'buyer') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary flex-grow-1" for="buyer">
                                        <i class="fas fa-shopping-cart me-2"></i> Buyer
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="userType" id="seller" value="seller" autocomplete="off"
                                           <?php echo (isset($_POST['userType']) && $_POST['userType'] === 'seller') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary flex-grow-1" for="seller">
                                        <i class="fas fa-store me-2"></i> Seller
                                    </label>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="firstName" class="form-label">First Name*</label>
                                    <input type="text" class="form-control form-control-lg" id="firstName" name="firstName" 
                                           value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label for="lastName" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control form-control-lg" id="lastName" name="lastName"
                                           value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <small class="text-muted">Optional but recommended for account recovery</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone" class="form-label">Phone Number*</label>
                                <div class="input-group">
                                    <span class="input-group-text">+27</span>
                                    <input type="tel" class="form-control form-control-lg" id="phone" name="phone" 
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                </div>
                                <small class="text-muted">Enter your number without the country code</small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password*</label>
                                <div class="position-relative">
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2" 
                                            onclick="togglePassword('password')" style="border: none; background: none;">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="progress mb-1" style="height: 5px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 25%" id="strengthBar"></div>
                                    </div>
                                    <small class="text-muted">Use 6+ characters with a mix of letters, numbers & symbols</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label">Confirm Password*</label>
                                <input type="password" class="form-control form-control-lg" id="confirmPassword" name="confirmPassword" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="location" class="form-label">Location*</label>
                                <select class="form-select form-select-lg" id="location" name="location" required>
                                    <option value="" selected disabled>Select your location</option>
                                    <option value="Johannesburg" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Johannesburg') ? 'selected' : ''; ?>>Johannesburg</option>
                                    <option value="Cape Town" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Cape Town') ? 'selected' : ''; ?>>Cape Town</option>
                                    <option value="Durban" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Durban') ? 'selected' : ''; ?>>Durban</option>
                                    <option value="Pretoria" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Pretoria') ? 'selected' : ''; ?>>Pretoria</option>
                                    <option value="Port Elizabeth" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Port Elizabeth') ? 'selected' : ''; ?>>Port Elizabeth</option>
                                    <option value="Other" <?php echo (isset($_POST['location']) && $_POST['location'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="termsAgree" name="termsAgree" required>
                                <label class="form-check-label" for="termsAgree">
                                    I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>*
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 mb-4">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="registerSpinner" role="status"></span>
                                Create Account
                            </button>
                        </form>
                    </div>
                    <div class="card-footer bg-white text-center py-3">
                        <p class="mb-0">Already have an account? <a href="login.php" class="text-primary">Sign in</a></p>
                    </div>
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

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;
            
            // Length check
            if (password.length > 5) strength += 25;
            
            // Contains number
            if (/\d/.test(password)) strength += 25;
            
            // Contains special char
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 25;
            
            // Contains upper and lower case
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            
            // Update progress bar
            strengthBar.style.width = strength + '%';
            
            // Update color
            if (strength < 50) {
                strengthBar.className = 'progress-bar bg-danger';
            } else if (strength < 75) {
                strengthBar.className = 'progress-bar bg-warning';
            } else {
                strengthBar.className = 'progress-bar bg-success';
            }
        });

        // Handle form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = document.getElementById('registerSpinner');
            
            // Check password match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                if (!alert.querySelector('small')) { // Don't hide success message with redirect info
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>