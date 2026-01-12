<?php
// destinations.php (updated with DB integration)
session_start();
require_once '../db/connect.php';

// Security: User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$pageTitle = "Select Destination";
$activePage = $activePage ?? 'destinations';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airlyft | <?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Choose from our curated collection of luxury destinations across the Philippines">
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0047ab;
            --primary-dark: #002d72;
            --accent: #e31837;
            --emerald: #00a86b;
            --luxury-gold: #d4af37;
            --dark: #1a1a1a;
            --light: #f8f9fa;
        }

        body {
            font-family: "Montserrat", sans-serif;
            color: var(--dark);
            background: #fff;
            line-height: 1.7;
            scroll-behavior: smooth;
            padding-top: 85px;
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: "Playfair Display", serif;
        }

        /* Navbar */
        .navbar {
            background: rgba(0, 71, 171, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.4s ease;
            padding: 0.5rem 0;
            min-height: 85px;
        }

        .navbar.scrolled {
            background: rgba(0, 45, 114, 0.98);
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
        }

        .nav-logo {
            height: 50px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .nav-logo:hover {
            transform: scale(1.05);
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            position: relative;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--luxury-gold) !important;
        }

        .nav-link::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--luxury-gold);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 70%;
        }

        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.3);
        }

        .btn-logout {
            background: transparent;
            border: 2px solid white;
            color: white !important;
            padding: 0.45rem 1.4rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn-logout:hover {
            background: white;
            color: var(--primary) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
        }

        /* Hero Section with Background Image - Clean & Lighter */
        .destinations-hero {
            background: linear-gradient(rgba(0, 71, 171, 0.2), rgba(0, 71, 171, 0.1)),
                url('../assets/img/destination.png') center/cover no-repeat fixed;
            height: 70vh;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            color: white;
            text-align: center;
        }

        /* Fallback gradient if image doesn't load */
        .destinations-hero.fallback-bg {
            background: linear-gradient(135deg, #0047ab, #002d72);
        }

        .hero-content {
            z-index: 2;
            padding: 0 1rem;
            max-width: 800px;
        }

        .destinations-hero h1 {
            font-family: "Playfair Display", serif;
            font-size: clamp(3.2rem, 8vw, 6rem);
            font-weight: 900;
            line-height: 1.05;
            margin-bottom: 1.2rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .destinations-hero p {
            font-size: clamp(1.2rem, 4vw, 1.6rem);
            font-weight: 300;
            margin-bottom: 2.5rem;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
        }

        /* Animation */
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

        /* Animation */
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

        /* Destinations Grid */
        .section-padding {
            padding: 6rem 0;
        }

        .section-label {
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 0.95rem;
            display: block;
            margin-bottom: 0.8rem;
        }

        .section-title {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .section-subtitle {
            color: #555;
            font-size: 1.15rem;
            max-width: 680px;
            margin: 0 auto 3rem;
        }

        .destination-card {
            border-radius: 16px;
            overflow: hidden;
            background: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            cursor: pointer;
            height: 100%;
            border: 3px solid transparent;
            position: relative;
        }

        .destination-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 60px rgba(0, 71, 171, 0.18);
            border-color: var(--luxury-gold);
        }

        .destination-card.selected {
            border-color: var(--emerald);
            box-shadow: 0 20px 50px rgba(0, 168, 107, 0.3);
            transform: translateY(-8px);
        }

        .destination-image {
            height: 240px;
            width: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }

        .destination-card:hover .destination-image {
            transform: scale(1.1);
        }

        .destination-content {
            padding: 1.8rem 1.6rem;
            position: relative;
            z-index: 2;
        }

        .destination-content h3 {
            font-size: 1.6rem;
            margin-bottom: 0.7rem;
            color: var(--primary-dark);
            font-family: "Playfair Display", serif;
            font-weight: 700;
        }

        .destination-content p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1.2rem;
            line-height: 1.6;
        }

        .select-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0.7rem 1.8rem;
            font-weight: 600;
            border-radius: 50px;
            color: #fff;
            text-decoration: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 8px 25px rgba(0, 71, 171, 0.3);
            transition: all 0.3s ease;
            border: none;
            width: 100%;
            font-size: 0.9rem;
        }

        .select-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 71, 171, 0.4);
            color: #fff;
        }

        .select-btn.selected {
            background: var(--emerald);
            box-shadow: 0 8px 25px rgba(0, 168, 107, 0.4);
        }

        .select-btn.selected:hover {
            background: #258f6b;
        }

        .select-btn i {
            font-size: 1.1rem;
        }

        /* Footer */
        .footer {
            background: var(--primary-dark);
            color: #ffffff;
            padding: 5rem 0 3rem;
        }

        .footer-brand {
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: 3px;
            font-family: "Playfair Display", serif;
        }

        .footer-tagline {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .footer-title {
            font-size: 0.95rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .footer-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .footer-link:hover {
            color: var(--luxury-gold);
        }

        .footer-link i {
            font-size: 1.1rem;
        }

        .footer-info li {
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.9rem;
            margin-bottom: 0.6rem;
            list-style: none;
            padding-left: 0;
        }

        .footer-info li:before {
            content: "✔";
            color: var(--luxury-gold);
            margin-right: 10px;
        }

        .footer-divider {
            margin: 3.5rem 0 1.5rem;
            height: 1px;
            background: rgba(255, 255, 255, 0.12);
        }

        .footer-copy {
            color: rgba(255, 255, 255, 0.45);
            font-size: 0.8rem;
        }

        /* Modal */
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-bottom: none;
            padding: 1.5rem 2rem;
        }

        .modal-header h5 {
            font-family: "Playfair Display", serif;
            font-weight: 700;
        }

        .modal-body {
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .confirm-icon {
            font-size: 4rem;
            color: var(--emerald);
            margin-bottom: 1.5rem;
        }

        .modal-footer {
            border-top: none;
            padding: 1.5rem 2rem;
            justify-content: center;
            gap: 1rem;
        }

        .btn-modal-secondary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 0.7rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-modal-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .btn-modal-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            color: white;
            padding: 0.7rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-modal-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 71, 171, 0.3);
        }

        /* Scroll indicator */
        .scroll-down-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            animation: bounce 2s infinite;
            z-index: 2;
        }

        .scroll-down-link {
            color: white;
            font-size: 2.5rem;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }

        .scroll-down-link:hover {
            color: var(--luxury-gold);
            transform: translateY(5px);
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0) translateX(-50%);
            }

            40% {
                transform: translateY(-10px) translateX(-50%);
            }

            60% {
                transform: translateY(-5px) translateX(-50%);
            }
        }

        /* Responsive */
        @media (max-width: 992px) {
            .section-padding {
                padding: 4rem 0;
            }

            .section-title {
                font-size: 2.8rem;
            }

            .destinations-hero h1 {
                font-size: 4rem;
            }

            .destinations-hero {
                background-attachment: scroll;
                /* Remove fixed on mobile for performance */
            }

            .destination-image {
                height: 200px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .navbar {
                min-height: 70px;
            }

            .nav-logo {
                height: 40px;
            }

            .destinations-hero {
                height: 50vh;
                min-height: 400px;
            }

            .destinations-hero h1 {
                font-size: 3rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .destination-content h3 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 576px) {
            .section-title {
                font-size: 2rem;
            }

            .destinations-hero h1 {
                font-size: 2.8rem;
            }

            .destinations-hero p {
                font-size: 1.2rem;
            }

            .destination-card {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo.png" alt="AirLyft Logo" class="nav-logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 gap-lg-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="destinations.php">Destinations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#fleet">Our Fleet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-logout px-4" href="../auth/logout.php">
                            <i class='bx bx-log-out'></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Background Image -->
    <header class="destinations-hero" id="hero">
        <div class="hero-content">
            <h1>Choose Your Paradise</h1>
            <p>Discover exclusive luxury destinations across the Philippines, accessible only by private air</p>
        </div>

        <div class="scroll-down-indicator">
            <a href="#destinations-section" class="scroll-down-link">
                <i class='bx bx-chevron-down'></i>
            </a>
        </div>
    </header>

    <!-- Destinations Section -->
    <section class="section-padding" id="destinations-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label">Exclusive Getaways</span>
                <h2 class="section-title">Curated Destinations</h2>
                <p class="section-subtitle">Discover the Philippines' most luxurious, secluded, and breathtaking resorts and retreats</p>
            </div>

            <div class="row g-4">
                <?php
                $destinations = [
                    "Amanpulo" => "Secluded Luxury in Pamalican Island",
                    "Balesin Island" => "Exclusive Island Getaway",
                    "Amorita Resort" => "Bohol's Cliffside Sanctuary",
                    "Huma Island Resort" => "Overwater Villas in Coron",
                    "El Nido Resorts" => "Palawan's Pristine Beauty",
                    "Banwa" => "Ultimate Exclusive Retreat",
                    "Nay Palad" => "Barefoot Luxury in Siargao",
                    "Alphaland Baguio" => "Mountain Serenity in Baguio",
                    "Shangri-La Boracay" => "Boracay's Premier Resort",
                    "Farm San Benito Lipa" => "Holistic Wellness Retreat",
                    "Aureo La Union" => "Coastal Charm in La Union",
                    "Eagle Point Beach" => "Seaside Dive & Leisure Resort"
                ];

                $images = [
                    "amanpulo.png",
                    "balesin.png",
                    "amorita.png",
                    "huma.png",
                    "elnido.png",
                    "banwa.png",
                    "naypalad.png",
                    "alphaland.png",
                    "shangrila.png",
                    "thefarmbenito.png",
                    "aureolu.png",
                    "eaglepoint.png"
                ];

                foreach ($destinations as $name => $desc) {
                    $index = array_search($name, array_keys($destinations));
                    $imagePath = '../assets/img/' . $images[$index];
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="destination-card" data-destination="<?= htmlspecialchars($name) ?>">
                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                alt="<?= htmlspecialchars($name) ?>"
                                class="destination-image"
                                loading="lazy">
                            <div class="destination-content text-center">
                                <h3><?= htmlspecialchars($name) ?></h3>
                                <p><?= htmlspecialchars($desc) ?></p>
                                <button class="btn select-btn">
                                    <i class='bx bx-check'></i> Select Destination
                                </button>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row gy-5 align-items-start">
                <div class="col-md-4 text-center text-md-start">
                    <h3 class="footer-brand">AirLyft</h3>
                    <p class="footer-tagline mt-3">
                        Luxury Private Air Travel<br>
                        Seamless access to exclusive destinations across the Philippines.
                    </p>
                </div>

                <div class="col-md-4">
                    <h5 class="footer-title">Get In Touch</h5>
                    <ul class="list-unstyled contact-list mt-4">
                        <li class="mb-3">
                            <a href="mailto:AirLyft16@gmail.com" class="footer-link">
                                <i class="bx bxs-envelope"></i>
                                AirLyft16@gmail.com
                            </a>
                        </li>
                        <li>
                            <a href="tel:+639232912527" class="footer-link">
                                <i class="bx bxs-phone"></i>
                                +63 923 291 2527
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-4 text-center text-md-start">
                    <h5 class="footer-title">Why Fly With Us</h5>
                    <ul class="list-unstyled footer-info mt-4">
                        <li>Private Aircraft & Helicopter Charter</li>
                        <li>Direct Resort & Helipad Access</li>
                        <li>Discreet, Secure & Personalized Flights</li>
                        <li>Curated Luxury Destinations</li>
                    </ul>
                </div>
            </div>

            <div class="footer-divider"></div>

            <div class="text-center">
                <small class="footer-copy">
                    © <?= date("Y") ?> Airlyft Travel Co. All rights reserved.
                </small>
            </div>
        </div>
    </footer>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="destinationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class='bx bx-plane me-2'></i>Confirm Destination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="confirm-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                    <h4 class="mb-3">Proceed with <span id="selectedDestination" class="text-primary fw-bold"></span>?</h4>
                    <p class="text-muted">You will be redirected to complete your booking details.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-modal-secondary" data-bs-dismiss="modal">
                        <i class='bx bx-x me-2'></i>Cancel
                    </button>
                    <button type="button" class="btn btn-modal-primary" id="confirmDestination">
                        <i class='bx bx-check me-2'></i>Confirm & Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                navbar.classList.toggle('scrolled', window.scrollY > 100);
            });

            // Check if background image loads correctly
            const hero = document.querySelector('.destinations-hero');
            const bgImage = new Image();

            // Try different image paths
            const imagePaths = [
                '../assets/img/destination.png',
                '../assets/img/destination.jpg',
                '../assets/img/destinations.png',
                '../assets/img/destinations.jpg',
                'assets/img/destination.png',
                'assets/img/destination.jpg'
            ];

            let currentPathIndex = 0;

            function testImageLoad(path) {
                return new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(path);
                    img.onerror = () => reject();
                    img.src = path;
                });
            }

            // Try to load the image from possible paths
            async function loadBackgroundImage() {
                for (const path of imagePaths) {
                    try {
                        await testImageLoad(path);
                        // Update the background image
                        hero.style.backgroundImage = `linear-gradient(rgba(0, 71, 171, 0.85), rgba(0, 45, 114, 0.85)), url('${path}')`;
                        console.log('Background image loaded:', path);
                        return;
                    } catch (error) {
                        console.log('Failed to load:', path);
                    }
                }

                // If all paths fail, use fallback gradient
                console.log('All image paths failed, using fallback gradient');
                hero.classList.add('fallback-bg');
                hero.style.backgroundImage = 'none';
            }

            loadBackgroundImage();

            // Destination selection
            let selectedDestination = null;
            const destinationCards = document.querySelectorAll('.destination-card');
            const selectButtons = document.querySelectorAll('.select-btn');

            destinationCards.forEach((card, index) => {
                card.addEventListener('click', function() {
                    // Remove previous selection
                    destinationCards.forEach(c => c.classList.remove('selected'));
                    selectButtons.forEach(btn => {
                        btn.classList.remove('selected');
                        btn.innerHTML = '<i class="bx bx-check"></i> Select Destination';
                    });

                    // Set new selection
                    this.classList.add('selected');
                    const button = this.querySelector('.select-btn');
                    button.classList.add('selected');
                    button.innerHTML = '<i class="bx bx-check-circle"></i> Selected';

                    selectedDestination = this.dataset.destination;

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('destinationModal'));
                    document.getElementById('selectedDestination').textContent = selectedDestination;
                    modal.show();
                });
            });

            // Confirm destination
            document.getElementById('confirmDestination').addEventListener('click', function() {
                if (selectedDestination) {
                    // Store in sessionStorage for booking page
                    sessionStorage.setItem('selectedDestination', selectedDestination);
                    // Redirect to booking page
                    window.location.href = '../booking/booking.php?destination=' + encodeURIComponent(selectedDestination);
                }
            });

            // Smooth scroll
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    if (this.getAttribute('href') === '#') return;

                    e.preventDefault();
                    const targetElement = document.querySelector(this.getAttribute('href'));
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Check for destination parameter in URL
            const urlParams = new URLSearchParams(window.location.search);
            const preSelected = urlParams.get('destination');
            if (preSelected) {
                const targetCard = document.querySelector(`[data-destination="${preSelected}"]`);
                if (targetCard) {
                    targetCard.click();
                }
            }
        });
    </script>
</body>

</html>