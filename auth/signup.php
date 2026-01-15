<?php
session_start();
include("../db/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstname = mysqli_real_escape_string($conn, $_POST["firstname"] ?? '');
    $lastname  = mysqli_real_escape_string($conn, $_POST["lastname"] ?? '');
    $email     = mysqli_real_escape_string($conn, $_POST["email"] ?? '');
    $phone     = mysqli_real_escape_string($conn, $_POST["phone"] ?? '');
    $password  = $_POST["password"] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password) || empty($phone)) {
        echo "missing_fields";
        exit();
    }
    if (strlen($firstname) < 2) { echo "short_firstname"; exit(); }
    if (strlen($lastname) < 2) { echo "short_lastname"; exit(); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo "invalid_email"; exit(); }
    if (!str_ends_with(strtolower($email), '@gmail.com')) { echo "invalid_gmail"; exit(); }
    if (strlen($password) < 8) { echo "short_password"; exit(); }
    if (!preg_match('/[a-z]/', $password)) { echo "password_no_lowercase"; exit(); }
    if (!preg_match('/[A-Z]/', $password)) { echo "password_no_uppercase"; exit(); }
    if (!preg_match('/[0-9]/', $password)) { echo "password_no_number"; exit(); }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) { echo "password_no_special"; exit(); }
    $phone_clean = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^09[0-9]{9}$/', $phone_clean)) { echo "invalid_phone"; exit(); }

    $check_email_query = "SELECT * FROM users WHERE LOWER(email) = LOWER('$email')";
    $check_result = mysqli_query($conn, $check_email_query);
    if (!$check_result) {
        error_log("Check email query failed: " . mysqli_error($conn));
        $check_email_query = "SELECT * FROM Users WHERE LOWER(email) = LOWER('$email')";
        $check_result = mysqli_query($conn, $check_email_query);
    }
    if (mysqli_num_rows($check_result) > 0) {
        echo "email_exists";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_query = "INSERT INTO users (first_name, last_name, email, phone, password, role, created_at, source_system) 
                     VALUES ('$firstname', '$lastname', '$email', '$phone_clean', '$hashed_password', 'Client', NOW(), 'Airlyft')";
    $result = mysqli_query($conn, $insert_query);

    if (!$result) {
        error_log("Insert failed with 'users' table: " . mysqli_error($conn));
        $insert_query = "INSERT INTO Users (first_name, last_name, email, phone, password, role, created_at, source_system) 
                         VALUES ('$firstname', '$lastname', '$email', '$phone_clean', '$hashed_password', 'Client', NOW(), 'Airlyft')";
        $result = mysqli_query($conn, $insert_query);
    }

    if ($result) {
        $verify_query = "SELECT * FROM users WHERE email = '$email'";
        $verify_result = mysqli_query($conn, $verify_query);
        if (!$verify_result || mysqli_num_rows($verify_result) === 0) {
            $verify_query = "SELECT * FROM Users WHERE email = '$email'";
            $verify_result = mysqli_query($conn, $verify_query);
        }

        if ($verify_result && mysqli_num_rows($verify_result) > 0) {
            $user = mysqli_fetch_assoc($verify_result);
            $verify = password_verify($password, $user['password']);
            error_log("Password verification test: " . ($verify ? "SUCCESS" : "FAILED"));
            if (!$verify) {
                error_log("Password verification failed for new user!");
                echo "database_error: Password hash mismatch";
                exit();
            }
        }

        echo "success";

        $syncData = [
            "first_name"    => $firstname,
            "last_name"     => $lastname,
            "email"         => $email,
            "phone"         => $phone_clean,
            "password"      => $hashed_password, // hashed password
            "origin_system" => "Airlyft"
        ];

        $electripid_url = "http://192.168.18.63/Electripid/user/api/sync_user.php";
        $ch = curl_init($electripid_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-KEY: ' . getenv('GROUP1_API_KEY')
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($syncData));

        $sync_response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("Electripid sync HTTP code: $httpcode");
        error_log("Electripid sync response body: $sync_response");

        exit();
    } else {
        error_log("Final insert failed: " . mysqli_error($conn));
        echo "database_error: " . mysqli_error($conn);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AirLyft - Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/img/icon.png" type="image/png">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap"
        rel="stylesheet">

    <style>
        /* Your existing CSS styles remain the same */
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
            padding: 0.5rem 0;
        }

        .nav-logo {
            height: 50px;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
        }

        .nav-link:hover {
            color: var(--gold) !important;
        }

        .signup-page {
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
            border-radius: 18px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            animation: fadeInUp 0.5s ease-out;
        }

        .signup-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 18px;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .signup-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: var(--gold);
        }

        .signup-header h1 {
            color: white;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.4rem;
        }

        .signup-header p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.9rem;
            margin: 0;
        }

        .signup-body {
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

        .signup-btn {
            background: linear-gradient(135deg, var(--emerald), #258f6b);
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

        .signup-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #258f6b, var(--emerald));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(31, 122, 91, 0.25);
        }

        .signup-btn:disabled {
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

        .login-link {
            display: block;
            margin-top: 1rem;
            text-align: center;
            font-size: 0.85rem;
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
            .signup-page {
                padding: 70px 10px 30px;
            }

            .signup-card {
                max-width: 95%;
                border-radius: 15px;
            }

            .alert-container {
                left: 15px;
                right: 15px;
                top: 70px;
            }
        }

        .name-row {
            display: flex;
            gap: 1rem;
            width: 100%;
            margin-bottom: 1rem;
        }

        .name-input {
            flex: 1;
            min-width: 0;
        }

        @media (max-width: 576px) {
            .name-row {
                flex-direction: column;
                gap: 0;
            }

            .name-input:first-child {
                margin-bottom: 1rem;
            }
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

    <div class="signup-page">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="signup-card">
                        <div class="signup-header text-center">
                            <h1>Create Account</h1>
                            <p>Join AirLyft for exclusive luxury travel experiences</p>
                        </div>
                        <div class="signup-body">
                            <form id="signupForm" class="needs-validation form-center" novalidate>
                                <!-- First Name and Last Name Row -->
                                <div class="name-row">
                                    <div class="name-input">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control form-control-lg" id="firstname"
                                                placeholder="First Name" required minlength="2">
                                            <div class="invalid-feedback">First name must be at least 2 characters.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="name-input">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control form-control-lg" id="lastname"
                                                placeholder="Last Name" required minlength="2">
                                            <div class="invalid-feedback">Last name must be at least 2 characters.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control form-control-lg" id="email"
                                        placeholder="Gmail Address (e.g., user@gmail.com)" required>
                                    <div class="invalid-feedback">Please enter a valid Gmail address.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <div class="password-wrapper" style="flex: 1;">
                                        <input type="password" class="form-control form-control-lg" id="password"
                                            placeholder="Password" required minlength="8">
                                        <button type="button" class="password-toggle" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password must be at least 8 characters with uppercase,
                                        lowercase, number, and special character.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <div class="password-wrapper" style="flex: 1;">
                                        <input type="password" class="form-control form-control-lg" id="confirmPassword"
                                            placeholder="Confirm Password" required>
                                        <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Passwords do not match.</div>
                                </div>

                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control form-control-lg" id="phone"
                                        placeholder="Phone Number (e.g., 09123456789)" required>
                                    <div class="invalid-feedback">Please enter a valid 11-digit Philippine phone number.
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree to the <a href="terms.php" target="_blank">Terms & Conditions</a>
                                    </label>
                                    <div class="invalid-feedback">You must agree to the terms.</div>
                                </div>

                                <button type="submit" id="signupBtn" class="btn signup-btn">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>

                                <div class="login-link mt-3">
                                    Already have an account? <a href="login.php">Login Here</a>
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
        $(document).ready(function () {
            // Password toggle
            $('#togglePassword').click(function () {
                const passwordInput = $('#password');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            $('#toggleConfirmPassword').click(function () {
                const confirmPasswordInput = $('#confirmPassword');
                const type = confirmPasswordInput.attr('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.attr('type', type);
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

            // Password validation
            function validatePassword() {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();

                // Check requirements
                const hasLower = /[a-z]/.test(password);
                const hasUpper = /[A-Z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[^a-zA-Z0-9]/.test(password);
                const hasLength = password.length >= 8;
                const passwordsMatch = password === confirmPassword;

                return hasLower && hasUpper && hasNumber && hasSpecial && hasLength && passwordsMatch;
            }

            // Form validation
            function validateForm() {
                let isValid = true;

                // First name
                const firstname = $('#firstname').val().trim();
                if (firstname.length < 2) {
                    $('#firstname').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#firstname').removeClass('is-invalid').addClass('is-valid');
                }

                // Last name
                const lastname = $('#lastname').val().trim();
                if (lastname.length < 2) {
                    $('#lastname').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#lastname').removeClass('is-invalid').addClass('is-valid');
                }

                // Email
                const email = $('#email').val().trim().toLowerCase();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailRegex.test(email)) {
                    $('#email').addClass('is-invalid');
                    isValid = false;
                } else if (!email.endsWith('@gmail.com')) {
                    $('#email').addClass('is-invalid');
                    showAlert('Please use a Gmail address.', 'warning');
                    isValid = false;
                } else {
                    $('#email').removeClass('is-invalid').addClass('is-valid');
                }

                // Password
                if (!validatePassword()) {
                    $('#password').addClass('is-invalid');
                    $('#confirmPassword').addClass('is-invalid');
                    showAlert('Password must be at least 8 characters with uppercase, lowercase, number, and special character.', 'warning');
                    isValid = false;
                } else {
                    $('#password').removeClass('is-invalid').addClass('is-valid');
                    $('#confirmPassword').removeClass('is-invalid').addClass('is-valid');
                }

                // Phone
                const phone = $('#phone').val().replace(/\D/g, '');
                if (!/^09[0-9]{9}$/.test(phone)) {
                    $('#phone').addClass('is-invalid');
                    showAlert('Please enter a valid Philippine phone number (09XXXXXXXXX).', 'warning');
                    isValid = false;
                } else {
                    $('#phone').removeClass('is-invalid').addClass('is-valid');
                }

                // Terms
                if (!$('#agreeTerms').is(':checked')) {
                    $('#agreeTerms').addClass('is-invalid');
                    showAlert('You must agree to the terms and conditions.', 'warning');
                    isValid = false;
                } else {
                    $('#agreeTerms').removeClass('is-invalid');
                }

                return isValid;
            }

            // Real-time validation
            $('#firstname, #lastname, #email, #password, #confirmPassword, #phone').on('input', function () {
                $(this).removeClass('is-invalid is-valid');
            });

            $('#agreeTerms').on('change', function () {
                $(this).removeClass('is-invalid');
            });

            // Form submission
            $('#signupForm').submit(function (e) {
                e.preventDefault();

                if (!validateForm()) {
                    return;
                }

                // Get form data
                const formData = {
                    firstname: $('#firstname').val().trim(),
                    lastname: $('#lastname').val().trim(),
                    email: $('#email').val().trim(),
                    phone: $('#phone').val().replace(/\D/g, ''),
                    password: $('#password').val()
                };

                // Disable button and show loading
                const signupBtn = $('#signupBtn');
                const originalHtml = signupBtn.html();
                signupBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating Account...');

                // AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'signup.php',
                    data: formData,
                    dataType: 'text',
                    success: function (response) {
                        response = response.trim();
                        console.log('Response:', response);

                        if (response === 'success') {
                            showAlert('Account created successfully! Redirecting to login...', 'success');
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 1500);
                        } else if (response.startsWith('database_error')) {
                            showAlert('System error. Please try again.', 'danger');
                        } else {
                            switch (response) {
                                case 'email_exists':
                                    showAlert('Email already exists. Please use a different email.', 'warning');
                                    $('#email').focus().select();
                                    break;
                                case 'short_firstname':
                                    showAlert('First name must be at least 2 characters.', 'warning');
                                    $('#firstname').focus().select();
                                    break;
                                case 'short_lastname':
                                    showAlert('Last name must be at least 2 characters.', 'warning');
                                    $('#lastname').focus().select();
                                    break;
                                case 'invalid_email':
                                    showAlert('Please enter a valid email address.', 'warning');
                                    $('#email').focus().select();
                                    break;
                                case 'invalid_gmail':
                                    showAlert('Please use a Gmail address.', 'warning');
                                    $('#email').focus().select();
                                    break;
                                case 'short_password':
                                case 'password_no_lowercase':
                                case 'password_no_uppercase':
                                case 'password_no_number':
                                case 'password_no_special':
                                    showAlert('Password must be at least 8 characters with uppercase, lowercase, number, and special character.', 'warning');
                                    $('#password').focus().select();
                                    break;
                                case 'invalid_phone':
                                    showAlert('Please enter a valid Philippine phone number.', 'warning');
                                    $('#phone').focus().select();
                                    break;
                                case 'missing_fields':
                                    showAlert('Please fill in all fields.', 'warning');
                                    break;
                                default:
                                    showAlert('Registration failed: ' + response, 'danger');
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        showAlert('Connection error. Please check your internet.', 'danger');
                        console.error('AJAX Error:', status, error);
                    },
                    complete: function () {
                        signupBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>
</body>

</html>