<?php
session_start();

// If already logged in, redirect based on role
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
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Your original styles remain unchanged -->
    <style>
        :root {
            --primary: #0047ab;
            --primary-dark: #002d72;
            --accent: #e31837;
            --emerald: #00a86b;
            --dark: #1a1a1a;
            --light: #f8f9fa;
            --gold: #d4af37;
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
            height: 60px;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem !important;
            position: relative;
        }

        .nav-link:hover {
            color: var(--gold) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 70%;
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.3);
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 60px;
            position: relative;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                    rgba(7, 61, 136, 0.55),
                    rgba(0, 46, 114, 0.4));
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
            border-radius: 24px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.25);
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--gold);
        }

        .login-header h1 {
            color: white;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.8rem;
            margin-bottom: 0.6rem;
            letter-spacing: 0.5px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.15rem;
            margin: 0;
            font-weight: 400;
        }

        .login-body {
            padding: 3rem 2.5rem;
        }

        .form-center {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-center .input-group {
            width: 100%;
            max-width: 400px;
            min-height: 68px;
        }

        .form-center .form-check {
            width: 100%;
            max-width: 400px;
        }

        .form-center .login-btn {
            max-width: 400px;
        }

        .form-center {
            width: 100%;
            max-width: 400px;
        }

        .form-control-lg {
            padding: 1.1rem 1.6rem;
            border: 2px solid #e0e0e0;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            background: #f8fbff;
            height: 68px;
            box-sizing: border-box;
        }

        .form-control-lg:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.3rem rgba(0, 71, 171, 0.15);
            background: white;
        }

        .input-group-text {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: 2px solid #e0e0e0;
            border-right: none;
            color: white;
            padding: 1.1rem 1.6rem;
            font-size: 1.1rem;
            height: 68px;
            display: flex;
            align-items: center;
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1.1rem 2.2rem;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 16px;
            transition: all 0.35s ease;
            width: 100%;
            margin-top: 1.5rem;
            position: relative;
            overflow: hidden;
            height: 68px;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 71, 171, 0.35);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .remember-section {
            width: 100%;
            max-width: 400px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .password-field {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
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

        @media (max-width: 576px) {
            .login-page {
                padding: 100px 15px 40px;
            }

            .login-header {
                padding: 2.5rem 1.5rem;
            }

            .login-header h1 {
                font-size: 2.3rem;
            }

            .login-body {
                padding: 2rem 1.5rem;
            }

            .login-card {
                border-radius: 20px;
            }

            .remember-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .login-header h1 {
                font-size: 2.5rem;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .signup-link {
            display: block;
            margin-top: 1rem;
            text-align: center;
            font-weight: 500;
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
    </style>
</head>

<body>
    <!-- Navbar - unchanged -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo.png" alt="Airlyft Logo" class="nav-logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 gap-lg-4">
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
                <div class="col-xl-8 col-lg-10">
                    <div class="login-card">
                        <div class="login-header text-center">
                            <h1>Welcome Back</h1>
                            <p>Elevate your journey with luxury access</p>
                        </div>
                        <div class="login-body">
                            <form id="loginForm" class="needs-validation form-center" novalidate>
                                <div class="input-group mb-4 input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control form-control-lg" id="email" placeholder="Email Address" required>
                                    <div class="invalid-feedback text-center">Please enter your email.</div>
                                </div>

                                <div class="password-field mb-4">
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <div class="password-wrapper" style="flex: 1;">
                                            <input type="password" class="form-control form-control-lg" id="password" placeholder="Password" required>
                                            <button type="button" class="password-toggle" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback text-center">Please enter your password.</div>
                                </div>

                                <div class="remember-section">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="rememberMe">
                                        <label class="form-check-label" for="rememberMe">Remember me</label>
                                    </div>
                                </div>

                                <button type="submit" id="loginBtn" class="btn login-btn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>

                                <div class="signup-link">
                                    Don't have an account? <a href="../auth/signup.php">Sign Up Now</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mt-3 text-white">Authenticating...</h5>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            // Password visibility toggle
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Real-time form validation feedback
            $('#loginForm input').on('input', function() {
                if (this.checkValidity()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            // Form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();

                if (!this.checkValidity()) {
                    this.reportValidity();
                    return;
                }

                const email = $('#email').val().trim();
                const password = $('#password').val();
                const rememberMe = $('#rememberMe').is(':checked');

                loadingModal.show();

                $.ajax({
                    type: 'POST',
                    url: '../auth/jslogin.php',
                    data: {
                        email: email,
                        password: password,
                        remember: rememberMe ? '1' : '0'
                    },
                    success: function(response) {
                        loadingModal.hide();
                        response = $.trim(response);

                        if (response === "admin") {
                            window.location.href = "../admin/admin_dashboard.php";
                        } else if (response === "user") {
                            window.location.href = "../booking/destinations.php";
                        } else if (response === "User not found" || response === "Invalid password") {
                            alert("Invalid email or password. Please try again.");
                            $('#password').val('').focus();
                        } else if (response === "missing_fields") {
                            alert("Please fill in all required fields.");
                        } else {
                            alert("Login failed: " + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        loadingModal.hide();
                        alert("Connection error. Please check your internet and try again.");
                        console.error("AJAX error:", status, error);
                    }
                });
            });
        });
    </script>
</body>
</html>

    
