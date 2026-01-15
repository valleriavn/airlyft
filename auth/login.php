<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] === "Admin") {
        header("Location: ../admin/admin_dashboard.php");
    } else {
        header("Location: ../booking/destinations.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AirLyft - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/img/icon.png" type="image/png">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0047ab;
            --primary-dark: #002d72;
            --accent: #e31837;
            --emerald: #00a86b;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --gold: #d4af37;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            font-family: "Montserrat", sans-serif;
            background: var(--light);
            color: var(--dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(0, 71, 171, 0.92);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            padding: 0.5rem 0;
        }

        .navbar.scrolled {
            background: rgba(0, 45, 114, 0.98);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
        }

        .nav-logo {
            height: 50px;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            position: relative;
            font-size: 0.9rem;
        }

        .nav-link:hover {
            color: var(--gold) !important;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 15px 40px;
            position: relative;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(7, 61, 136, 0.55), rgba(0, 46, 114, 0.4));
            z-index: -1;
        }

        .login-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../assets/img/loginbg.png') center/cover fixed;
            z-index: -2;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            animation: fadeInUp 0.5s ease-out;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 2rem 1.5rem;
            border-radius: 18px;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--gold);
        }

        .login-header h1 {
            color: white;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.4rem;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9rem;
            margin: 0;
        }

        .login-body {
            padding: 2rem 1.8rem;
        }

        .form-center {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-group-lg {
            height: 54px;
            margin-bottom: 1.2rem;
        }

        .form-control-lg {
            padding: 0.8rem 1.2rem;
            border: 1.5px solid #e0e0e0;
            font-size: 0.9rem;
            background: #f8fbff;
            height: 54px;
            border-radius: 8px;
        }

        .form-control-lg::placeholder {
            font-size: 0.85rem;
            color: #888;
        }

        .form-control-lg:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 71, 171, 0.15);
            background: white;
        }

        .input-group-text {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: 1.5px solid #e0e0e0;
            border-right: none;
            color: white;
            padding: 0.8rem 1.2rem;
            font-size: 0.9rem;
            height: 54px;
            border-radius: 8px 0 0 8px;
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.9rem 1.8rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            margin-top: 1.2rem;
            height: 54px;
            transition: all 0.3s ease;
        }

        .login-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 71, 171, 0.25);
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .signup-link {
            display: block;
            margin-top: 1rem;
            text-align: center;
            font-size: 0.85rem;
        }

        .signup-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert-container {
            position: fixed;
            top: 80px;
            right: 15px;
            z-index: 9999;
            max-width: 350px;
        }

        .custom-alert {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: none;
            animation: slideInRight 0.3s ease-out;
            font-size: 0.85rem;
            padding: 0.75rem 1rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 576px) {
            .login-page {
                padding: 70px 10px 30px;
            }

            .login-header {
                padding: 1.5rem 1rem;
            }

            .login-header h1 {
                font-size: 1.6rem;
            }

            .login-body {
                padding: 1.5rem 1.2rem;
            }

            .login-card {
                max-width: 95%;
                border-radius: 15px;
            }

            .alert-container {
                left: 15px;
                right: 15px;
                top: 70px;
            }
        }

        .invalid-feedback,
        .valid-feedback {
            font-size: 0.75rem;
            margin-top: 0.2rem;
        }
    </style>
</head>

<body>
    <!-- Alert Container -->
    <div class="alert-container"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo.png" alt="Airlyft Logo" class="nav-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-1 gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="../index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#destinations">Destinations</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#fleet">Our Fleet</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="login-page">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="login-card">
                        <div class="login-header text-center">
                            <h1>Welcome Back</h1>
                            <p>Elevate your journey with luxury access</p>
                        </div>
                        <div class="login-body">
                            <form id="loginForm" class="needs-validation form-center" novalidate>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control form-control-lg" id="email" placeholder="Email Address" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <div class="password-wrapper" style="flex: 1;">
                                        <input type="password" class="form-control form-control-lg" id="password" placeholder="Password" required>
                                        <button type="button" class="password-toggle" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password is required.</div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3 w-100">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe">
                                        <label class="form-check-label" for="rememberMe">Remember me</label>
                                    </div>
                                </div>

                                <button type="submit" id="loginBtn" class="btn login-btn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>

                                <div class="signup-link mt-3">
                                    Don't have an account? <a href="signup.php">Sign Up Now</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize variables
            let isSubmitting = false;

            // Navbar scroll effect
            $(window).scroll(function() {
                $('.navbar').toggleClass('scrolled', $(window).scrollTop() > 50);
            });

            // Password toggle
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Show alert function
            function showAlert(message, type = 'info') {
                const alertContainer = $('.alert-container');
                const icons = {
                    success: 'fa-check-circle',
                    danger: 'fa-exclamation-circle',
                    warning: 'fa-exclamation-triangle',
                    info: 'fa-info-circle'
                };

                const alertHtml = `
                    <div class="alert alert-${type} custom-alert alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>
                            <div>${message}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;

                alertContainer.append(alertHtml);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    alertContainer.find('.alert').first().alert('close');
                }, 5000);
            }

            // Form validation
            function validateForm() {
                let isValid = true;

                // Email validation
                const email = $('#email').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!email) {
                    $('#email').addClass('is-invalid');
                    isValid = false;
                } else if (!emailRegex.test(email)) {
                    $('#email').addClass('is-invalid');
                    showAlert('Please enter a valid email address.', 'warning');
                    isValid = false;
                } else if (!email.toLowerCase().endsWith('@gmail.com')) {
                    $('#email').addClass('is-invalid');
                    showAlert('Please use a Gmail address (user@gmail.com)', 'warning');
                    isValid = false;
                } else {
                    $('#email').removeClass('is-invalid').addClass('is-valid');
                }

                // Password validation
                const password = $('#password').val();
                if (!password) {
                    $('#password').addClass('is-invalid');
                    isValid = false;
                } else if (password.length < 8) {
                    $('#password').addClass('is-invalid');
                    showAlert('Password must be at least 8 characters long.', 'warning');
                    isValid = false;
                } else {
                    $('#password').removeClass('is-invalid').addClass('is-valid');
                }

                return isValid;
            }

            // Form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();

                if (isSubmitting) return;

                // Validate form
                if (!validateForm()) {
                    return;
                }

                // Get form data
                const formData = {
                    email: $('#email').val().trim(),
                    password: $('#password').val(),
                    remember: $('#rememberMe').is(':checked') ? '1' : '0'
                };

                // Disable button and show loading
                const loginBtn = $('#loginBtn');
                const originalHtml = loginBtn.html();
                loginBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Logging in...');
                isSubmitting = true;

                // AJAX request
                $.ajax({
                    type: 'POST',
                    url: '../auth/jslogin.php',
                    data: formData,
                    dataType: 'text',
                    success: function(response) {
                        response = response.trim();

                        switch (response) {
                            case 'admin':
                                showAlert('Welcome back, Admin! Redirecting to dashboard...', 'success');
                                setTimeout(() => window.location.href = '../admin/admin_dashboard.php', 1500);
                                break;

                            case 'user':
                                showAlert('Login successful! Redirecting...', 'success');
                                setTimeout(() => window.location.href = '../booking/destinations.php', 1500);
                                break;

                            case 'user_not_found':
                                showAlert('Account not found. Please check your email or <a href="signup.php">sign up</a>.', 'warning');
                                $('#password').val('').removeClass('is-valid is-invalid');
                                $('#password').focus();
                                break;

                            case 'invalid_password':
                                showAlert('Incorrect password. Please try again.', 'warning');
                                $('#password').val('').removeClass('is-valid').addClass('is-invalid');
                                $('#password').focus();
                                break;

                            case 'invalid_email':
                                showAlert('Invalid email format.', 'warning');
                                $('#email').removeClass('is-valid').addClass('is-invalid');
                                $('#email').focus();
                                break;

                            case 'invalid_gmail':
                                showAlert('Please use a Gmail address.', 'warning');
                                $('#email').removeClass('is-valid').addClass('is-invalid');
                                $('#email').focus();
                                break;

                            case 'short_password':
                                showAlert('Password must be at least 8 characters.', 'warning');
                                $('#password').removeClass('is-valid').addClass('is-invalid');
                                $('#password').focus();
                                break;

                            case 'missing_fields':
                                showAlert('Please fill in all fields.', 'warning');
                                break;

                            case 'database_error':
                                showAlert('System error. Please try again later.', 'danger');
                                break;

                            default:
                                showAlert('Login failed. Please try again.', 'danger');
                                console.error('Unexpected response:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('Connection error. Please check your internet connection.', 'danger');
                        console.error('AJAX Error:', status, error);
                    },
                    complete: function() {
                        // Re-enable button
                        loginBtn.prop('disabled', false).html(originalHtml);
                        isSubmitting = false;
                    }
                });
            });

            // Real-time validation
            $('#email, #password').on('input', function() {
                $(this).removeClass('is-invalid is-valid');
            });

            // Check for remember me cookie
            function getCookie(name) {
                const cookies = document.cookie.split(';');
                for (let cookie of cookies) {
                    const [cookieName, cookieValue] = cookie.trim().split('=');
                    if (cookieName === name) {
                        try {
                            const decoded = atob(cookieValue);
                            const parts = decoded.split('|');
                            if (parts.length === 2) {
                                return parts[0]; // Return email
                            }
                        } catch (e) {
                            return null;
                        }
                    }
                }
                return null;
            }

            // Auto-fill email from cookie
            const rememberedEmail = getCookie('remember_email');
            if (rememberedEmail) {
                $('#email').val(rememberedEmail);
                $('#rememberMe').prop('checked', true);
            }
        });
    </script>
</body>

</html>