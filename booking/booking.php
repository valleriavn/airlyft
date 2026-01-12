<?php
// booking.php (updated with DB connection and form processing)
session_start();
require_once '../db/connect.php';

// Security: User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle form submission to create booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $user_id = $_SESSION['user_id'];
    $sched_id = (int)$_POST['sched_id']; // Assume sched_id from selection
    $total_amount = (float)$_POST['total_amount'];
    $passengers = $_POST['passengers']; // Array of passenger names
    $insurance = isset($_POST['insurance']) ? 1 : 0;
    $notes = trim($_POST['notes']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Create Payment (pending)
        $stmt = $conn->prepare("INSERT INTO Payment (booking_id, amount, method, payment_status) VALUES (?, ?, ?, 'Pending')");
        $stmt->bind_param("ids", $booking_id, $total_amount, $payment_method); // payment_method from form if added
        $stmt->execute();
        $payment_id = $conn->insert_id;

        // Create Booking
        $stmt = $conn->prepare("
            INSERT INTO Booking (user_id, sched_id, payment_id, booking_status, total_amount) 
            VALUES (?, ?, ?, 'Pending', ?)
        ");
        $stmt->bind_param("iiid", $user_id, $sched_id, $payment_id, $total_amount);
        $stmt->execute();
        $booking_id = $conn->insert_id;

        // Add Passengers
        foreach ($passengers as $passenger_name) {
            $stmt = $conn->prepare("INSERT INTO Passenger (user_id, booking_id, name, insurance) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $user_id, $booking_id, $passenger_name, $insurance);
            $stmt->execute();
        }

        // Commit
        $conn->commit();
        $success_msg = "Booking created successfully! Booking ID: $booking_id";

        // Optional: Create notifications
        // Insert into EmailNotification and SMSNotification here

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = "Error creating booking: " . $e->getMessage();
    }
}

$pageTitle = "Private Flight Booking";
$activePage = $activePage ?? 'booking';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap" rel="stylesheet">

    <!-- Your custom styles -->
    <link rel="stylesheet" href="../css/booking.css">
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




<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header text-white text-center py-4">
                    <h3 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h3>
                </div>

                <div class="card-body p-4 p-md-5">
                    <div id="alertBox" class="alert d-none mb-4" role="alert"></div>

                    <form id="bookingForm" novalidate>
                        <!-- Trip Type -->
                        <div class="mb-5">
                            <span class="form-label fw-bold fs-5 d-block mb-3">Trip Type</span>
                            <div class="btn-group w-100" role="group" aria-label="Trip type selection">
                                <input type="radio" class="btn-check" name="trip_type" id="oneway" value="oneway" checked required>
                                <label class="btn btn-outline-primary py-3 fs-5" for="oneway">One Way</label>
                                <input type="radio" class="btn-check" name="trip_type" id="roundtrip" value="roundtrip">
                                <label class="btn btn-outline-primary py-3 fs-5" for="roundtrip">Round Trip</label>
                            </div>
                        </div>

                        <!-- Aircraft -->
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5">Select Aircraft</label>
                            <select class="form-select form-select-lg" id="aircraftSelect" name="aircraft" required>
                                <option value="" disabled selected>Choose aircraft type...</option>
                                <option value="Cessna 206">Cessna 206</option>
                                <option value="Cessna G-Caravan EX">Cessna G-Caravan EX</option>
                                <option value="Airbus H160">Airbus H160</option>
                                <option value="Sikorsky S-76D">Sikorsky S-76D</option>
                            </select>
                        </div>

                        <!-- Aircraft Preview -->
                        <div class="card mb-5 bg-light border-0 shadow-sm" id="aircraftPreview" style="display:none;">
                            <div class="row g-0">
                                <div class="col-md-5">
                                    <img id="previewImage" class="img-fluid rounded-start" alt="Selected aircraft">
                                </div>
                                <div class="col-md-7">
                                    <div class="card-body">
                                        <h4 class="card-title" id="previewName"></h4>
                                        <p class="card-text fs-5">
                                            <strong>Capacity:</strong> <span id="previewCapacity"></span><br>
                                            <strong>Base Price:</strong> <span id="basePriceDisplay">₱0</span><br>
                                            <strong>Per Passenger:</strong> <span id="perPassengerDisplay">₱0</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Departure Airport -->
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5">Departure Airport</label>
                            <select class="form-select form-select-lg" id="departureLocation" name="departure_location" required>
                                <option value="" disabled selected>Select departure airport...</option>
                                <option value="MNL">Manila (MNL) - NAIA</option>
                                <option value="CEB">Cebu (CEB) - Mactan-Cebu</option>
                                <option value="DVO">Davao (DVO) - Francisco Bangoy</option>
                            </select>
                        </div>

                        <!-- Dates & Times -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-5">Departure Date</label>
                                <input type="text" class="form-control form-control-lg" id="datePicker" name="date" required placeholder="Select date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-5">Departure Time</label>
                                <input type="text" class="form-control form-control-lg" id="timePicker" name="departure_time" required placeholder="Select time">
                            </div>
                            <div class="col-md-6" id="returnDateContainer" style="display:none;">
                                <label class="form-label fw-bold fs-5">Return Date</label>
                                <input type="text" class="form-control form-control-lg" id="returnDatePicker" name="return_date" placeholder="Select return date">
                            </div>
                            <div class="col-md-6" id="returnTimeContainer" style="display:none;">
                                <label class="form-label fw-bold fs-5">Return Time</label>
                                <input type="text" class="form-control form-control-lg" id="returnTimePicker" name="return_time" placeholder="Select return time">
                            </div>
                        </div>

                        <!-- Passengers -->
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-5">Number of Passengers</label>
                                <input type="number" class="form-control form-control-lg" id="passengers" name="passengers_count" min="1" required value="1">
                                <div class="form-text text-muted">
                                    Maximum allowed: <strong id="maxPassengers">—</strong>
                                </div>
                            </div>
                        </div>

                        <div id="dynamicPassengers"></div>

                        <div class="mb-4">
                            <label class="form-label fw-bold fs-5">Special Requests / Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Any special requests, preferences or notes..."></textarea>
                        </div>

                        <div class="alert alert-info mb-4 py-3">
                            <strong>Estimated Total Price:</strong><br>
                            <span class="fs-4" id="totalPrice">₱0</span>
                            <small class="d-block mt-1">
                                Round-trip × 1.8 • Travel Insurance: +₱2,000 per passenger if selected
                            </small>
                        </div>

                        <div class="d-grid mt-5">
                            <button type="button" id="reviewBookingBtn" class="btn btn-primary btn-lg py-3 fs-5">
                                View Booking Summary
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Modal -->
<div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="summaryModalLabel">Booking Summary</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="summaryContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">Edit Booking</button>
                <button type="button" id="confirmFinalBtn" class="btn btn-success btn-lg px-5">Confirm & Proceed to Payment</button>
            </div>
        </div>
    </div>
</div>

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
                            <i class="bx bxs-envelope"></i> AirLyft16@gmail.com
                        </a>
                    </li>
                    <li>
                        <a href="tel:+639232912527" class="footer-link">
                            <i class="bx bxs-phone"></i> +63 923 291 2527
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
                © <?= date("Y") ?> AirLyft Travel Co. All rights reserved.
            </small>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="booking.js" defer></script>

</body>
</html>
