<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AirLyft - Terms & Privacy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="../assets/img/icon.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        }

        body {
            font-family: "Montserrat", sans-serif;
            background: var(--light);
            color: var(--dark);
            min-height: 100vh;
            font-size: 0.95rem;
        }

        .navbar {
            background: rgba(0, 71, 171, 0.95);
            backdrop-filter: blur(8px);
            padding: 0.4rem 0;
        }

        .nav-logo {
            height: 50px;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.4rem 0.8rem !important;
            font-size: 0.9rem;
        }

        .terms-page {
            padding: 100px 15px 40px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        }

        .terms-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 3rem 1.5rem;
            text-align: center;
            color: white;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 71, 171, 0.15);
        }

        .terms-header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 0.8rem;
        }

        .terms-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto;
        }

        .terms-content {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .section-title {
            color: var(--primary-dark);
            border-bottom: 2px solid var(--gold);
            padding-bottom: 8px;
            margin-bottom: 1.5rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .section-content {
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .section-content h3 {
            color: var(--primary);
            margin-top: 1.5rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .section-content ul {
            padding-left: 1.2rem;
        }

        .section-content li {
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
        }

        .highlight-box {
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-left: 3px solid var(--primary);
            padding: 1.2rem;
            border-radius: 0 8px 8px 0;
            margin: 1.5rem 0;
            font-size: 0.95rem;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1.5rem;
        }

        .nav-tabs .nav-link {
            color: var(--primary) !important;
            background: none;
            border: none;
            font-weight: 600;
            padding: 0.7rem 1.2rem !important;
            font-size: 0.95rem;
        }

        .nav-tabs .nav-link.active {
            color: white !important;
            background: var(--primary);
            border-radius: 6px 6px 0 0;
        }

        @media (max-width: 768px) {
            .terms-header h1 {
                font-size: 2rem;
            }

            .terms-header {
                padding: 2.5rem 1rem;
            }

            .terms-content {
                padding: 2rem 1.5rem;
            }

            .terms-page {
                padding: 90px 10px 30px;
            }
        }

        @media (max-width: 576px) {
            .terms-content {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo.png" alt="Airlyft Logo" class="nav-logo">
            </a>
        </div>
    </nav>

    <div class="terms-page">
        <div class="container">
            <div class="terms-header">
                <h1>Terms & Privacy</h1>
                <p>Your trust is important to us. Please read our terms and privacy policy carefully.</p>
            </div>

            <ul class="nav nav-tabs" id="termsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">Terms & Conditions</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">Privacy Policy</button>
                </li>
            </ul>

            <div class="tab-content" id="termsTabsContent">
                <div class="tab-pane fade show active" id="terms" role="tabpanel">
                    <div class="terms-content">
                        <h2 class="section-title">Terms & Conditions</h2>
                        <div class="section-content">
                            <p>Welcome to AirLyft Travel. By accessing our website and using our services, you agree to be bound by these Terms & Conditions.</p>
                            <h3>1. Acceptance of Terms</h3>
                            <p>By accessing and using AirLyft Travel services, you accept and agree to be bound by the terms and provision of this agreement.</p>
                            <h3>2. Service Description</h3>
                            <p>AirLyft Travel provides luxury travel services including flight bookings, hotel reservations, and travel packages.</p>
                            <div class="highlight-box">
                                <p><strong>Important:</strong> All bookings are subject to confirmation and availability. Prices are subject to change without notice until booking is confirmed.</p>
                            </div>
                            <h3>3. User Account</h3>
                            <ul>
                                <li>You must be at least 18 years old to create an account</li>
                                <li>You are responsible for maintaining the confidentiality of your account</li>
                                <li>You agree to provide accurate and complete information</li>
                            </ul>
                            <h3>4. Booking and Payments</h3>
                            <ul>
                                <li>Full payment is required at the time of booking</li>
                                <li>We accept major credit cards and other payment methods</li>
                                <li>All prices are in USD unless otherwise specified</li>
                            </ul>
                            <h3>5. Cancellation and Refunds</h3>
                            <p>Cancellation policies vary by service type. Please review the specific cancellation terms at the time of booking.</p>
                            <h3>6. Limitation of Liability</h3>
                            <p>AirLyft Travel shall not be liable for any direct, indirect, incidental, special, or consequential damages.</p>
                            <h3>7. Changes to Terms</h3>
                            <p>We reserve the right to modify these terms at any time.</p>
                            <h3>8. Contact Information</h3>
                            <p>For questions, contact us at <a href="mailto:AirLyft16@gmail.com">AirLyft16@gmail.com</a>.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="privacy" role="tabpanel">
                    <div class="terms-content">
                        <h2 class="section-title">Privacy Policy</h2>
                        <div class="section-content">
                            <p>At AirLyft Travel, we are committed to protecting your privacy.</p>
                            <h3>1. Information We Collect</h3>
                            <p>We collect information you provide directly to us, including:</p>
                            <ul>
                                <li>Personal information (name, email, phone number)</li>
                                <li>Account credentials</li>
                                <li>Payment information</li>
                                <li>Travel preferences</li>
                            </ul>
                            <h3>2. How We Use Your Information</h3>
                            <p>We use your information to provide and improve our services, process transactions, and ensure security.</p>
                            <div class="highlight-box">
                                <p><strong>Your Rights:</strong> You have the right to access, correct, or delete your personal information at any time.</p>
                            </div>
                            <h3>3. Information Sharing</h3>
                            <p>We do not sell your personal information. We may share information with service providers necessary for travel arrangements.</p>
                            <h3>4. Data Security</h3>
                            <p>We implement appropriate measures to protect your personal information.</p>
                            <h3>5. Cookies and Tracking</h3>
                            <p>We use cookies to enhance your experience and deliver personalized content.</p>
                            <h3>6. International Transfers</h3>
                            <p>Your information may be transferred to and processed in countries other than your own.</p>
                            <h3>7. Children's Privacy</h3>
                            <p>Our services are not intended for children under 13.</p>
                            <h3>8. Policy Updates</h3>
                            <p>We may update this Privacy Policy periodically.</p>
                            <h3>9. Contact Us</h3>
                            <p>If you have questions, contact us at <a href="mailto:AirLyft16@gmail.com">AirLyft16@gmail.com</a>.</p>
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
            const triggerTabList = document.querySelectorAll('#termsTabs button');
            triggerTabList.forEach(triggerEl => {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', event => {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });

            if (window.location.hash === '#privacy') {
                const privacyTab = new bootstrap.Tab(document.querySelector('#privacy-tab'));
                privacyTab.show();
            }
        });
    </script>
</body>

</html>