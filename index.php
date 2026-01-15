<?php
// index.php - Main landing page with conditional navbar
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['name'] ?? $_SESSION['email'] ?? 'User') : '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-spy="scroll" data-bs-target="#navbar-example3" data-bs-offset="80">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Airlyft - Luxury private air travel to the most exclusive destinations in the Philippines. Premium service, modern fleet, curated getaways." />
    <title>Airlyft Travel</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/img/icon.png" type="image/png">
    <link rel="shortcut icon" href="assets/img/icon.png" type="image/png">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/index.css">

    <!-- Index-specific CSS -->
    <style>
        /* User dropdown styles */
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0047ab, #002d72);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .user-dropdown .dropdown-toggle {
            color: white !important;
            font-weight: 500;
            transition: all 0.3s;
            padding: 0.5rem 1rem !important;
        }

        .user-dropdown .dropdown-toggle:hover {
            color: var(--luxury-gold) !important;
        }

        .user-dropdown .dropdown-menu {
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 200px;
        }

        .user-dropdown .dropdown-item {
            padding: 0.7rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .user-dropdown .dropdown-item:hover {
            background: linear-gradient(135deg, #0047ab, #002d72);
            color: white;
        }

        .user-dropdown .dropdown-item i {
            width: 20px;
            margin-right: 10px;
        }

        .btn-login {
            background: var(--emerald);
            border: 2px solid var(--emerald);
            color: white !important;
            padding: 0.45rem 1.4rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn-login:hover {
            background: #258f6b !important;
            border-color: #258f6b !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(31, 122, 91, 0.4);
            color: white !important;
        }

        @media (max-width: 768px) {
            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation with Scrollspy -->
    <nav id="navbar-example3" class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-3 px-lg-4">
            <!-- Brand / Logo -->
            <a class="navbar-brand d-flex align-items-center" href="#home">
                <img src="assets/img/logo.png" alt="AirLyft Logo" class="nav-logo" loading="lazy">
            </a>

            <!-- Toggler for mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav"
                aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Scrollspy Menu items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 gap-lg-4">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#destinations">Destinations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#fleet">Our Fleet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>

                    <!-- Conditional Login/User Dropdown -->
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown user-dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($userName, 0, 1)) ?>
                                </div>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="booking/transactionHistory.php"><i class='bx bx-history'></i> Transaction History</a></li>
                                <li><a class="dropdown-item" href="booking/destinations.php"><i class='bx bx-send'></i> Book a Flight</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="auth/logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-login px-4" href="auth/login.php">Log in / Sign up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Progress Indicator -->
    <div class="progress-indicator d-none d-lg-block">
        <a href="#home" class="progress-dot" data-bs-toggle="tooltip" title="Home"></a>
        <a href="#destinations" class="progress-dot" data-bs-toggle="tooltip" title="Destinations"></a>
        <a href="#fleet" class="progress-dot" data-bs-toggle="tooltip" title="Our Fleet"></a>
        <a href="#about" class="progress-dot" data-bs-toggle="tooltip" title="About"></a>
        <a href="#contact" class="progress-dot" data-bs-toggle="tooltip" title="Contact"></a>
    </div>

    <!-- Hero -->
    <section class="hero" id="home">
        <video autoplay muted loop playsinline class="hero-video" poster="assets/img/hero-poster.jpg">
            <source src="assets/vid/montage.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-overlay"></div>

        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">Elevate Your Journey</h1>
                <p class="hero-subtitle">Experience luxury redefined with exclusive Philippine destinations and premium private air travel</p>
                <?php if ($isLoggedIn): ?>
                    <a href="booking/destinations.php" class="btn-green">Book Your Private Flight</a>
                <?php else: ?>
                    <a href="auth/signup.php" class="btn-green">Join AirLyft Today</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scroll Down Indicator -->
        <div class="scroll-down-indicator">
            <a href="#destinations" class="scroll-down-link">
                <i class='bx bx-chevron-down'></i>
            </a>
        </div>
    </section>

    <!-- Destinations -->
    <section class="section-padding" id="destinations">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label">Exclusive Getaways</span>
                <h2 class="section-title">Curated Destinations</h2>
                <p class="section-subtitle">Discover the Philippines' most luxurious, secluded, and breathtaking resorts and retreats</p>
            </div>

            <div class="row g-4">
                <!-- All 12 Destinations Displayed -->
                <?php
                $destinations = [
                    [
                        "name" => "Amanpulo Palawan",
                        "image" => "destinations/amanpulo.png",
                        "description" => "Secluded Luxury in Pamalican Island",
                        "link" => "https://www.aman.com/resorts/amanpulo"
                    ],
                    [
                        "name" => "Balesin Island Quezon Province",
                        "image" => "destinations/balesin.png",
                        "description" => "Exclusive Island Getaway",
                        "link" => "https://balesin.com/island/"
                    ],
                    [
                        "name" => "Amorita Resort Panglao",
                        "image" => "destinations/amorita.png",
                        "description" => "Bohol's Cliffside Sanctuary",
                        "link" => "https://www.amoritaresort.com/"
                    ],
                    [
                        "name" => "Huma Island Resort Palawan",
                        "image" => "destinations/huma.png",
                        "description" => "Overwater Villas in Coron",
                        "link" => "https://www.agoda.com/huma-island-resort-and-spa/hotel/palawan-ph.html?cid=1844104&ds=LrwZ%2BFU2lzJitzEk"
                    ],
                    [
                        "name" => "El Nido Resorts Apulit Island",
                        "image" => "destinations/elnido.png",
                        "description" => "Palawan's Pristine Beauty",
                        "link" => "https://www.oyster.com/philippines/hotels/el-nido-resorts-apulit-island/"
                    ],
                    [
                        "name" => "Banwa Private Island",
                        "image" => "destinations/banwa.png",
                        "description" => "Ultimate Exclusive Retreat",
                        "link" => "https://www.banwaprivateisland.com/island"
                    ],
                    [
                        "name" => "Nay Palad Hideaway",
                        "image" => "destinations/naypalad.png",
                        "description" => "Barefoot Luxury in Siargao",
                        "link" => "https://naypaladhideaway.com/"
                    ],
                    [
                        "name" => "Alphaland Baguio",
                        "image" => "destinations/alphaland.png",
                        "description" => "Mountain Serenity in Baguio",
                        "link" => "https://alphaland.com.ph/baguiomountainlodges/"
                    ],
                    [
                        "name" => "Shangri-La Boracay",
                        "image" => "destinations/shangrila.png",
                        "description" => "Boracay's Premier Resort",
                        "link" => "https://www.shangri-la.com/boracay/boracayresort/about/local-guide/"
                    ],
                    [
                        "name" => "The Farm San Benito",
                        "image" => "destinations/thefarmbenito.png",
                        "description" => "Holistic Wellness Retreat",
                        "link" => "https://www.thefarmatsanbenito.com/villas/"
                    ],
                    [
                        "name" => "Aureo La Union",
                        "image" => "destinations/aureolu.png",
                        "description" => "Coastal Charm in La Union",
                        "link" => "https://www.aureohotels.com/"
                    ],
                    [
                        "name" => "Eagle Point Beach",
                        "image" => "destinations/eaglepoint.png",
                        "description" => "Seaside Dive & Leisure Resort",
                        "link" => "https://eaglepointresort.com.ph/"
                    ]
                ];

                foreach ($destinations as $destination) {
                ?>
                    <div class="col-lg-4 col-md-6">
                        <a href="<?= htmlspecialchars($destination['link']) ?>" target="_blank" rel="noopener noreferrer" class="destination-card text-decoration-none">
                            <div class="card-image">
                                <img src="assets/img/<?= htmlspecialchars($destination['image']) ?>" alt="<?= htmlspecialchars($destination['name']) ?>" loading="lazy" onerror="this.src='assets/img/placeholder.png'">
                                <div class="overlay">
                                    <span>Discover More <i class="bx bx-right-arrow-alt"></i></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h3><?= htmlspecialchars($destination['name']) ?></h3>
                                <p class="text-muted"><?= htmlspecialchars($destination['description']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Fleet Section -->
    <section class="section-padding bg-gradient-fleet" id="fleet">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-label">Premium Aircraft</span>
                <h2 class="section-title">Our Luxury Fleet</h2>
                <p class="section-subtitle">Travel in unparalleled style, comfort and privacy with our modern fleet</p>
            </div>

            <div class="row g-4 g-lg-5 justify-content-center fleet-row">
                <!-- Cessna 206 -->
                <div class="col-xl-6 col-lg-6 col-md-10">
                    <div class="fleet-card luxury-hover" onclick="openLightbox('cessna')">
                        <div class="fleet-image position-relative overflow-hidden">
                            <img src="assets/img/aircraft/cessna206/CESSNA-206-1.png"
                                alt="Cessna 206"
                                loading="lazy"
                                class="img-fluid transition-scale"
                                onerror="this.src='assets/img/placeholder.png'">
                            <div class="fleet-overlay">
                                <span class="view-gallery">View Gallery <i class="bx bx-images"></i></span>
                            </div>
                        </div>
                        <div class="fleet-info text-center">
                            <h3>Cessna 206</h3>
                            <p class="capacity"><i class="bx bx-user"></i> Up to 5 Passengers</p>
                            <p class="small text-muted mt-2">Versatile single-engine aircraft perfect for short island routes</p>
                        </div>
                    </div>
                </div>

                <!-- Cessna Grand Caravan EX -->
                <div class="col-xl-6 col-lg-6 col-md-10">
                    <div class="fleet-card luxury-hover" onclick="openLightbox('caravan')">
                        <div class="fleet-image position-relative overflow-hidden">
                            <img src="assets/img/aircraft/caravan/CESSNA-CARAVAN-1.png"
                                alt="Cessna Grand Caravan EX"
                                loading="lazy"
                                class="img-fluid transition-scale"
                                onerror="this.src='assets/img/placeholder.png'">
                            <div class="fleet-overlay">
                                <span class="view-gallery">View Gallery <i class="bx bx-images"></i></span>
                            </div>
                        </div>
                        <div class="fleet-info text-center">
                            <h3>Cessna Grand Caravan EX</h3>
                            <p class="capacity"><i class="bx bx-user"></i> Up to 10 Passengers</p>
                            <p class="small text-muted mt-2">Spacious & reliable turboprop for groups & remote access</p>
                        </div>
                    </div>
                </div>

                <!-- Airbus H160 -->
                <div class="col-xl-6 col-lg-6 col-md-10">
                    <div class="fleet-card luxury-hover" onclick="openLightbox('airbus')">
                        <div class="fleet-image position-relative overflow-hidden">
                            <img src="assets/img/aircraft/airbus/AIRBUS-H160-1.png"
                                alt="Airbus H160"
                                loading="lazy"
                                class="img-fluid transition-scale"
                                onerror="this.src='assets/img/placeholder.png'">
                            <div class="fleet-overlay">
                                <span class="view-gallery">View Gallery <i class="bx bx-images"></i></span>
                            </div>
                        </div>
                        <div class="fleet-info text-center">
                            <h3>Airbus H160</h3>
                            <p class="capacity"><i class="bx bx-user"></i> Up to 8 Passengers</p>
                            <p class="small text-muted mt-2">Modern, quiet & luxurious twin-engine helicopter</p>
                        </div>
                    </div>
                </div>

                <!-- Sikorsky S-76D -->
                <div class="col-xl-6 col-lg-6 col-md-10">
                    <div class="fleet-card luxury-hover" onclick="openLightbox('sikorsky')">
                        <div class="fleet-image position-relative overflow-hidden">
                            <img src="assets/img/aircraft/sikorsky/SIKORSKY-S76D-1.png"
                                alt="Sikorsky S-76D"
                                loading="lazy"
                                class="img-fluid transition-scale"
                                onerror="this.src='assets/img/placeholder.png'">
                            <div class="fleet-overlay">
                                <span class="view-gallery">View Gallery <i class="bx bx-images"></i></span>
                            </div>
                        </div>
                        <div class="fleet-info text-center">
                            <h3>Sikorsky S-76D</h3>
                            <p class="capacity"><i class="bx bx-user"></i> Up to 6 Passengers</p>
                            <p class="small text-muted mt-2">Proven executive helicopter – speed, comfort & reliability</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5 pt-4">
                <?php if ($isLoggedIn): ?>
                    <a href="booking/destinations.php" class="btn-green">Book Your Private Flight</a>
                <?php else: ?>
                    <a href="auth/signup.php" class="btn-green">Create Account to Book</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About -->
    <section class="section-padding about-section" id="about">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 text-center">
                    <span class="section-label text-white">Our Story</span>
                    <h2 class="section-title text-white">About Airlyft</h2>
                    <p class="lead text-white-75 mb-4">
                        At Airlyft, we believe travel is more than reaching a destination — it's about creating timeless memories in the most extraordinary places.
                        We specialize in connecting discerning travelers with the Philippines' most exclusive island escapes, mountain sanctuaries, and wellness retreats through premium private air travel.
                    </p>
                    <p class="lead text-white-75 mb-5">
                        With our modern fleet, personalized service, and deep knowledge of the country's hidden paradises, we make every journey extraordinary.
                    </p>
                    <p class="tagline">Let us be your wings to paradise.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row gy-5 align-items-start">
                <!-- BRAND -->
                <div class="col-md-4 text-center text-md-start">
                    <h3 class="footer-brand">AirLyft</h3>
                    <p class="footer-tagline mt-3">
                        Luxury Private Air Travel<br>
                        Seamless access to exclusive destinations across the Philippines.
                    </p>
                </div>

                <!-- CONTACT -->
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

                <!-- TRUST / SERVICE INFO -->
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

            <!-- DIVIDER -->
            <div class="footer-divider"></div>

            <!-- COPYRIGHT -->
            <div class="text-center">
                <small class="footer-copy">
                    © <?= date("Y") ?> Airlyft Travel Co. All rights reserved.
                </small>
            </div>
        </div>
    </footer>

    <!-- Lightbox Galleries -->
    <div id="lightbox-cessna" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox('cessna')"><i class="bx bx-x"></i></span>
        <img id="lightbox-img-cessna" src="" alt="Cessna 206 Gallery">
        <div class="lightbox-controls">
            <button class="lightbox-arrow" onclick="prevImage('cessna')"><i class="bx bx-chevron-left"></i></button>
            <button class="lightbox-arrow" onclick="nextImage('cessna')"><i class="bx bx-chevron-right"></i></button>
        </div>
    </div>

    <div id="lightbox-caravan" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox('caravan')"><i class="bx bx-x"></i></span>
        <img id="lightbox-img-caravan" src="" alt="Cessna Grand Caravan EX Gallery">
        <div class="lightbox-controls">
            <button class="lightbox-arrow" onclick="prevImage('caravan')"><i class="bx bx-chevron-left"></i></button>
            <button class="lightbox-arrow" onclick="nextImage('caravan')"><i class="bx bx-chevron-right"></i></button>
        </div>
    </div>

    <div id="lightbox-airbus" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox('airbus')"><i class="bx bx-x"></i></span>
        <img id="lightbox-img-airbus" src="" alt="Airbus H160 Gallery">
        <div class="lightbox-controls">
            <button class="lightbox-arrow" onclick="prevImage('airbus')"><i class="bx bx-chevron-left"></i></button>
            <button class="lightbox-arrow" onclick="nextImage('airbus')"><i class="bx bx-chevron-right"></i></button>
        </div>
    </div>

    <div id="lightbox-sikorsky" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox('sikorsky')"><i class="bx bx-x"></i></span>
        <img id="lightbox-img-sikorsky" src="" alt="Sikorsky S-76D Gallery">
        <div class="lightbox-controls">
            <button class="lightbox-arrow" onclick="prevImage('sikorsky')"><i class="bx bx-chevron-left"></i></button>
            <button class="lightbox-arrow" onclick="nextImage('sikorsky')"><i class="bx bx-chevron-right"></i></button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initialize Scrollspy
        const scrollSpy = new bootstrap.ScrollSpy(document.body, {
            target: '#navbar-example3',
            offset: 80
        });

        // Update progress indicator
        const sections = document.querySelectorAll('section[id]');
        const progressDots = document.querySelectorAll('.progress-dot');

        function updateProgressIndicator() {
            let currentSection = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop - 100;
                const sectionHeight = section.clientHeight;
                if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                    currentSection = section.getAttribute('id');
                }
            });

            progressDots.forEach(dot => {
                dot.classList.remove('active');
                if (dot.getAttribute('href') === `#${currentSection}`) {
                    dot.classList.add('active');
                }
            });
        }

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('#navbar-example3');
            navbar.classList.toggle('scrolled', window.scrollY > 100);
            updateProgressIndicator();
        });

        // Smooth scroll for progress dots
        progressDots.forEach(dot => {
            dot.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                document.querySelector(targetId)?.scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Smooth scroll for anchor links
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

        // Tooltip initialization
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // ── Lightbox Gallery Functionality ──
        const aircraftImages = {
            cessna: [
                'assets/img/aircraft/cessna206/CESSNA-206-1.png',
                'assets/img/aircraft/cessna206/CESSNA-206-2.png',
                'assets/img/aircraft/cessna206/CESSNA-206-3.png'
            ],
            caravan: [
                'assets/img/aircraft/caravan/CESSNA-CARAVAN-1.png',
                'assets/img/aircraft/caravan/CESSNA-CARAVAN-2.png',
                'assets/img/aircraft/caravan/CESSNA-CARAVAN-3.png'
            ],
            airbus: [
                'assets/img/aircraft/airbus/AIRBUS-H160-1.png',
                'assets/img/aircraft/airbus/AIRBUS-H160-2.png',
                'assets/img/aircraft/airbus/AIRBUS-H160-3.png',
                'assets/img/aircraft/airbus/AIRBUS-H160-4.png'
            ],
            sikorsky: [
                'assets/img/aircraft/sikorsky/SIKORSKY-S76D-1.png',
                'assets/img/aircraft/sikorsky/SIKORSKY-S76D-2.png',
                'assets/img/aircraft/sikorsky/SIKORSKY-S76D-3.png',
                'assets/img/aircraft/sikorsky/SIKORSKY-S76D-4.png'
            ]
        };

        let currentIndex = {};

        function openLightbox(aircraft) {
            const lightbox = document.getElementById(`lightbox-${aircraft}`);
            if (!lightbox) return;
            lightbox.style.display = 'flex';
            currentIndex[aircraft] = 0;
            updateLightboxImage(aircraft);
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(aircraft) {
            const lightbox = document.getElementById(`lightbox-${aircraft}`);
            if (lightbox) lightbox.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function nextImage(aircraft) {
            if (!aircraftImages[aircraft]) return;
            currentIndex[aircraft] = (currentIndex[aircraft] + 1) % aircraftImages[aircraft].length;
            updateLightboxImage(aircraft);
        }

        function prevImage(aircraft) {
            if (!aircraftImages[aircraft]) return;
            currentIndex[aircraft] = (currentIndex[aircraft] - 1 + aircraftImages[aircraft].length) % aircraftImages[aircraft].length;
            updateLightboxImage(aircraft);
        }

        function updateLightboxImage(aircraft) {
            const img = document.getElementById(`lightbox-img-${aircraft}`);
            if (img && aircraftImages[aircraft]) {
                img.src = aircraftImages[aircraft][currentIndex[aircraft]];
            }
        }

        // Close lightbox when clicking outside image
        document.querySelectorAll('.lightbox').forEach(lightbox => {
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) {
                    lightbox.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', (e) => {
            const activeLightbox = document.querySelector('.lightbox[style*="display: flex"]');
            if (!activeLightbox) return;

            const aircraft = activeLightbox.id.replace('lightbox-', '');

            if (e.key === 'Escape') {
                closeLightbox(aircraft);
            } else if (e.key === 'ArrowRight') {
                nextImage(aircraft);
            } else if (e.key === 'ArrowLeft') {
                prevImage(aircraft);
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateProgressIndicator();
        });
    </script>

    <?php include_once 'integrations/aiChat/chatbotWidget.php'; ?>
</body>

</html>