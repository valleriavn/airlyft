<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AirLyft - Terms & Privacy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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
            border-radius: 6px;
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

        .terms-page {
            padding: 120px 20px 60px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        }

        .terms-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 4rem 2rem;
            text-align: center;
            color: white;
            border-radius: 20px;
            margin-bottom: 3rem;
            box-shadow: 0 20px 50px rgba(0, 71, 171, 0.2);
        }

        .terms-header h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .terms-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto;
        }

        .terms-content {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: var(--primary-dark);
            border-bottom: 3px solid var(--gold);
            padding-bottom: 10px;
            margin-bottom: 2rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        .section-content {
            line-height: 1.8;
            margin-bottom: 3rem;
        }

        .section-content h3 {
            color: var(--primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .section-content ul {
            padding-left: 1.5rem;
        }

        .section-content li {
            margin-bottom: 0.5rem;
        }

        .highlight-box {
            background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0 10px 10px 0;
            margin: 2rem 0;
        }

        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 5px 20px rgba(0, 71, 171, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            color: white;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            color: var(--primary) !important;
            background: none;
            border: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem !important;
        }

        .nav-tabs .nav-link.active {
            color: white !important;
            background: var(--primary);
            border-radius: 8px 8px 0 0;
        }

        @media (max-width: 768px) {
            .terms-header h1 {
                font-size: 2.5rem;
            }

            .terms-header {
                padding: 3rem 1.5rem;
            }

            .terms-content {
                padding: 2rem;
            }

            .terms-page {
                padding: 100px 15px 40px;
            }
        }
    </style>
</head>

<body>
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
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                </ul>
            </div>
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
                    <button class="nav-link" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab" id="privacy">Privacy Policy</button>
                </li>
            </ul>

            <div class="tab-content" id="termsTabsContent">
                <div class="tab-pane fade show active" id="terms" role="tabpanel">
                    <div class="terms-content">
                        <h2 class="section-title">Terms & Conditions</h2>

                        <div class="section-content">
                            <p>Welcome to AirLyft Travel. By accessing our website and using our services, you agree to be bound by these Terms & Conditions. Please read them carefully.</p>

                            <h3>1. Acceptance of Terms</h3>
                            <p>By accessing and using AirLyft Travel services, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by these terms, please do not use our services.</p>

                            <h3>2. Service Description</h3>
                            <p>AirLyft Travel provides luxury travel services including flight bookings, hotel reservations, and travel packages. All services are subject to availability and may be modified or discontinued at our discretion.</p>

                            <div class="highlight-box">
                                <p><strong>Important:</strong> All bookings are subject to confirmation and availability. Prices are subject to change without notice until booking is confirmed.</p>
                            </div>

                            <h3>3. User Account</h3>
                            <ul>
                                <li>You must be at least 18 years old to create an account</li>
                                <li>You are responsible for maintaining the confidentiality of your account</li>
                                <li>You agree to provide accurate and complete information</li>
                                <li>You are responsible for all activities that occur under your account</li>
                            </ul>

                            <h3>4. Booking and Payments</h3>
                            <ul>
                                <li>Full payment is required at the time of booking</li>
                                <li>We accept major credit cards and other payment methods as indicated</li>
                                <li>All prices are in USD unless otherwise specified</li>
                                <li>Additional fees may apply for special requests</li>
                            </ul>

                            <h3>5. Cancellation and Refunds</h3>
                            <p>Cancellation policies vary by service type. Please review the specific cancellation terms at the time of booking. Refunds, when applicable, will be processed within 7-14 business days.</p>

                            <h3>6. Limitation of Liability</h3>
                            <p>AirLyft Travel shall not be liable for any direct, indirect, incidental, special, or consequential damages resulting from the use or inability to use our services.</p>

                            <h3>7. Changes to Terms</h3>
                            <p>We reserve the right to modify these terms at any time. Continued use of our services after changes constitutes acceptance of the new terms.</p>

                            <h3>8. Contact Information</h3>
                            <p>For questions about these Terms & Conditions, please contact us at <a href="mailto:AirLyft16@gmail.com">AirLyft16@gmail.com</a>.</p>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="privacy" role="tabpanel">
                    <div class="terms-content">
                        <h2 class="section-title">Privacy Policy</h2>

                        <div class="section-content">
                            <p>At AirLyft Travel, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your personal information.</p>

                            <h3>1. Information We Collect</h3>
                            <p>We collect information you provide directly to us, including:</p>
                            <ul>
                                <li>Personal information (name, email, phone number)</li>
                                <li>Account credentials</li>
                                <li>Payment information</li>
                                <li>Travel preferences and booking details</li>
                                <li>Communication preferences</li>
                            </ul>

                            <h3>2. How We Use Your Information</h3>
                            <p>We use your information to:</p>
                            <ul>
                                <li>Provide and improve our services</li>
                                <li>Process transactions</li>
                                <li>Send booking confirmations and updates</li>
                                <li>Respond to your inquiries</li>
                                <li>Send promotional communications (with your consent)</li>
                                <li>Ensure security and prevent fraud</li>
                            </ul>

                            <div class="highlight-box">
                                <p><strong>Your Rights:</strong> You have the right to access, correct, or delete your personal information at any time by contacting us or through your account settings.</p>
                            </div>

                            <h3>3. Information Sharing</h3>
                            <p>We do not sell your personal information. We may share information with:</p>
                            <ul>
                                <li>Service providers necessary for travel arrangements</li>
                                <li>Legal authorities when required by law</li>
                                <li>Affiliated companies for business operations</li>
                            </ul>

                            <h3>4. Data Security</h3>
                            <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, or destruction.</p>

                            <h3>5. Cookies and Tracking</h3>
                            <p>We use cookies and similar technologies to enhance your experience, analyze usage, and deliver personalized content. You can control cookie settings through your browser.</p>

                            <h3>6. International Transfers</h3>
                            <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers.</p>

                            <h3>7. Children's Privacy</h3>
                            <p>Our services are not intended for children under 13. We do not knowingly collect personal information from children under 13.</p>

                            <h3>8. Policy Updates</h3>
                            <p>We may update this Privacy Policy periodically. We will notify you of significant changes by posting the new policy on our website.</p>

                            <h3>9. Contact Us</h3>
                            <p>If you have questions about this Privacy Policy, contact us at <a href="mailto:AirLyft16@gmail.com">AirLyft16@gmail.com</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }

                const backToTop = document.getElementById('backToTop');
                if (window.scrollY > 300) {
                    backToTop.style.display = 'flex';
                } else {
                    backToTop.style.display = 'none';
                }
            });

            $('#backToTop').click(function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

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