<?php
session_start();
include("../db/connect.php");

// Handle AJAX POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = $_POST["name"]     ?? '';
    $email    = $_POST["email"]    ?? '';
    $phone    = $_POST["phone"]    ?? '';
    $password = $_POST["password"] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        echo "missing_fields";
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT 1 FROM Users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "exists";
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $role = "Client";
    $stmt = $conn->prepare("
        INSERT INTO Users (name, email, phone, password, role) 
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("sssss", $name, $email, $phone, $hashed_password, $role);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>  
    <meta charset="UTF-8">
    <title>AirLyft - Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Your existing <style> block remains unchanged -->
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

        .signup-page {
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

        .signup-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../assets/img/loginbg.png') center/cover fixed;
            z-index: -2;
        }

        .signup-card {
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

        .signup-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .signup-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--gold);
        }

        .signup-header h1 {
            color: white;
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.8rem;
            margin-bottom: 0.6rem;
            letter-spacing: 0.5px;
        }

        .signup-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.15rem;
            margin: 0;
            font-weight: 400;
        }

        .signup-body {
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
            margin-bottom: 1.5rem;
        }

        .form-center .signup-btn {
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
            flex: 1;
            min-width: 0;
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
            min-width: 60px;
            justify-content: center;
        }

        .signup-btn {
            background: linear-gradient(135deg, var(--emerald), #258f6b);
            color: white;
            border: none;
            padding: 1.1rem 2.2rem;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 16px;
            transition: all 0.35s ease;
            width: 100%;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
            height: 68px;
        }

        .signup-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .signup-btn:hover {
            background: linear-gradient(135deg, #258f6b, var(--emerald));
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(31, 122, 91, 0.35);
        }

        .signup-btn:hover::before {
            left: 100%;
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
            .signup-page {
                padding: 100px 15px 40px;
            }

            .signup-header {
                padding: 2.5rem 1.5rem;
            }

            .signup-header h1 {
                font-size: 2.3rem;
            }

            .signup-body {
                padding: 2rem 1.5rem;
            }

            .signup-card {
                border-radius: 20px;
            }
        }

        @media (max-width: 768px) {
            .signup-header h1 {
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

        .signup-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .login-link {
            display: block;
            margin-top: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .terms-check {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
            max-width: 400px;
        }

        .terms-check .form-check-input {
            margin-right: 8px;
        }

        .terms-check .form-check-label {
            font-size: 0.9rem;
        }

        .terms-check a {
            color: var(--primary);
            text-decoration: none;
        }

        .terms-check a:hover {
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

    <div class="signup-page">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="signup-card">
                        <div class="signup-header text-center">
                            <h1>Create Account</h1>
                            <p>Join AirLyft for exclusive luxury travel experiences</p>
                        </div>
                        <div class="signup-body">
                            <form id="signupForm" class="needs-validation form-center" novalidate>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                    <input type="text" class="form-control form-control-lg" id="name" placeholder="Name" required>
                                    <div class="invalid-feedback text-center">Please enter your name.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control form-control-lg" id="email" placeholder="Email Address" required>
                                    <div class="invalid-feedback text-center">Please enter a valid email address.</div>
                                </div>

                                <!-- Removed duplicate name/username field -->

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <div class="password-wrapper" style="flex: 1;">
                                        <input type="password" class="form-control form-control-lg" id="password" placeholder="Password" required>
                                        <button type="button" class="password-toggle" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback text-center">Please create a password.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <div class="password-wrapper" style="flex: 1;">
                                        <input type="password" class="form-control form-control-lg" id="confirmPassword" placeholder="Confirm Password" required>
                                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback text-center">Please confirm your password.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control form-control-lg" id="phone" placeholder="Phone Number" required>
                                    <div class="invalid-feedback text-center">Please enter your phone number.</div>
                                </div>

                                <div class="terms-check">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                        <label class="form-check-label" for="agreeTerms">
                                            I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="terms.php#privacy" target="_blank">Privacy Policy</a>
                                        </label>
                                        <div class="invalid-feedback">You must agree to the terms to continue.</div>
                                    </div>
                                </div>

                                <button type="submit" id="signup" class="btn signup-btn">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>

                                <div class="login-link">
                                    Already have an account? <a href="login.php">Login Here</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal - unchanged -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="mt-3 text-white">Creating Account...</h5>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));

            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });

            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            $('#toggleConfirmPassword').click(function() {
                const confirmPasswordInput = $('#confirmPassword');
                const type = confirmPasswordInput.attr('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            function validatePassword() {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();

                if (password !== confirmPassword) {
                    $('#confirmPassword').addClass('is-invalid').removeClass('is-valid');
                    $('#confirmPassword').next('.invalid-feedback').text('Passwords do not match');
                    return false;
                } else if (confirmPassword) {
                    $('#confirmPassword').addClass('is-valid').removeClass('is-invalid');
                    return true;
                }
                return true;
            }

            $('#password, #confirmPassword').on('input', function() {
                validatePassword();
            });

            $('#signupForm').submit(function(e) {
                e.preventDefault();

                if (!this.checkValidity() || !validatePassword()) {
                    this.reportValidity();
                    return;
                }

                loadingModal.show();

                $.ajax({
                    type: "POST",
                    url: "signup.php",
                    data: {
                        name:     $('#name').val().trim(),
                        email:    $('#email').val().trim(),
                        phone:    $('#phone').val().trim(),
                        password: $('#password').val()
                    },
                    success: function(response) {
                        loadingModal.hide();
                        response = $.trim(response);

                        if (response === "success") {
                            alert("Account created successfully! Please login.");
                            window.location.href = "login.php";
                        } 
                        else if (response === "exists") {
                            alert("Email already exists! Please use a different email.");
                        } 
                        else if (response === "missing_fields") {
                            alert("Please fill in all required fields.");
                        }
                        else {
                            alert("Registration failed: " + response);
                        }
                    },
                    error: function() {
                        loadingModal.hide();
                        alert("Connection error. Please try again.");
                    }
                });
            }); 
        });
    </script>
</body>
</html>

    
