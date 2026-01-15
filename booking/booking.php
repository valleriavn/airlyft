<?php
// booking.php
session_start();
require_once '../db/connect.php';
require_once '../integrations/paypal/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$pageTitle = "Private Flight Booking";

// Get selected destination
$selected_place_id = isset($_GET['place_id']) ? (int)$_GET['place_id'] : 0;
$selected_place_name = '—';
$selected_place_location = '';

if ($selected_place_id > 0) {
    $stmt = $conn->prepare("SELECT place_name, location FROM Place WHERE place_id = ?");
    $stmt->bind_param("i", $selected_place_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $selected_place_name = $row['place_name'];
        $selected_place_location = $row['location'];
    }
}

// Load real aircraft from Lift table
$aircraft_result = $conn->query("
    SELECT lift_id, aircraft_name, aircraft_type, capacity, price, lift_status
    FROM Lift 
    WHERE lift_status = 'available'
    ORDER BY price ASC
");

// Prepare aircraft options
$aircraft_data = [];
$aircraft_options = "";

while ($row = $aircraft_result->fetch_assoc()) {
    // Determine correct image folder
    $folder = '';
    if (strtolower($row['aircraft_type']) === 'helicopter') {
        $folder = stripos($row['aircraft_name'], 'sikorsky') !== false ? 'helicopter02' : 'helicopter01';
    } else {
        $folder = stripos($row['aircraft_name'], 'grand') !== false ? 'cessna02' : 'cessna01';
    }

    // Build full image path
    $imagePath = "../assets/img/$folder/" . $row['aircraft_name'] . "-1.png";

    // Store data for JS
    $aircraft_data[$row['lift_id']] = [
        'lift_id'     => $row['lift_id'],
        'name'        => $row['aircraft_name'],
        'type'        => $row['aircraft_type'],
        'capacity'    => (int)$row['capacity'],
        'price'       => (float)$row['price'],
        'status'      => $row['lift_status'],
        'image_path'  => $imagePath
    ];

    // Create dropdown option
    $aircraft_options .= '<option value="'.$row['lift_id'].'">'
        . htmlspecialchars($row['aircraft_name']) . ' (' . $row['capacity'] . ' seats)'
        . '</option>';
}

// Fetch already booked dates for each aircraft
$booked_dates = [];
$schedule_query = "
    SELECT 
        s.lift_id,
        DATE(s.departure_time) as booked_date,
        b.booking_status
    FROM Schedule s
    LEFT JOIN Booking b ON s.schedule_id = b.sched_id
    WHERE b.booking_status IN ('Confirmed', 'Pending')
    GROUP BY s.lift_id, DATE(s.departure_time), b.booking_status
    ORDER BY s.lift_id, booked_date
";

$schedule_result = $conn->query($schedule_query);
while ($row = $schedule_result->fetch_assoc()) {
    $lift_id = $row['lift_id'];
    if (!isset($booked_dates[$lift_id])) {
        $booked_dates[$lift_id] = [];
    }
    $booked_dates[$lift_id][] = $row['booked_date'];
}

// Fetch distinct airports from Schedule
$airports = [
    'MNL' => 'Manila (MNL) - NAIA',
    'CEB' => 'Cebu (CEB) - Mactan-Cebu',
    'DVO' => 'Davao (DVO) - Francisco Bangoy'
];

$airport_options = '';
foreach ($airports as $code => $fullName) {
    $airport_options .= '<option value="'.htmlspecialchars($code).'">'.htmlspecialchars($fullName).'</option>';
}

// Get user info
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM Users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Pass data to JavaScript
$aircraft_json = json_encode($aircraft_data);
$booked_dates_json = json_encode($booked_dates);
$user_json = json_encode($user);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Boxicons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700;900&display=swap"
        rel="stylesheet">

    <!-- Custom styles -->
    <link rel="stylesheet" href="../assets/css/booking.css">
    <style>
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
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--emerald) !important;
        }

        .btn-outline-light {
            border-color: white;
            color: white;
            transition: all 0.3s;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
        }

        /* Calendar styling */
        .flatpickr-day.past {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            text-decoration: line-through;
            cursor: not-allowed !important;
        }

        .flatpickr-day.booked {
            background-color: #ffcccc !important;
            color: #ff0000 !important;
            font-weight: bold !important;
            border: 2px solid #ff0000 !important;
            text-decoration: line-through;
        }

        .flatpickr-day.available {
            background-color: #d4edda !important;
            color: #155724 !important;
            font-weight: bold !important;
            border: 2px solid #28a745 !important;
        }

        .flatpickr-day.today {
            border-color: #007bff !important;
            background-color: #e7f1ff !important;
        }

        .flatpickr-day.selected {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        .flatpickr-day.disabled {
            color: #ccc !important;
            cursor: not-allowed !important;
        }

        /* Time picker improvements */
        .flatpickr-time {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .flatpickr-hour, .flatpickr-minute {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .flatpickr-am-pm {
            padding: 8px 12px;
            border-radius: 4px;
            background: #f8f9fa;
        }

        .calendar-legend {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 15px 0;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid transparent;
        }

        .legend-past {
            background-color: #f8d7da;
            border-color: #721c24 !important;
        }

        .legend-booked {
            background-color: #ffcccc;
            border-color: #ff0000 !important;
        }

        .legend-available {
            background-color: #d4edda;
            border-color: #28a745 !important;
        }

        /* Time picker container */
        .time-picker-container {
            position: relative;
        }

        .time-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            display: none;
            max-height: 200px;
            overflow-y: auto;
        }

        .time-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .time-suggestion:hover {
            background: #f8f9fa;
        }

        .time-suggestion.active {
            background: #007bff;
            color: white;
        }

        /* Better time display */
        .selected-time {
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
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
                    <li class="nav-item"><a class="nav-link" href="../index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../destinations/destinations.php">Destinations</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#fleet">Our Fleet</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bx bx-user me-1"></i> <?= htmlspecialchars($user['first_name'] ?? 'Users') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bx bx-log-out me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="padding-top: 80px;">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                    <div class="card-header text-white text-center py-4">
                        <h3 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h3>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <!-- Selected Destination Display -->
                        <?php if ($selected_place_id > 0): ?>
                        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                            <i class="bx bx-map me-3 fs-4"></i>
                            <div>
                                <h5 class="mb-1">Selected Destination: <?= htmlspecialchars($selected_place_name) ?></h5>
                                <p class="mb-0"><?= htmlspecialchars($selected_place_location) ?></p>
                                <input type="hidden" id="selectedPlaceId" value="<?= $selected_place_id ?>">
                                <input type="hidden" id="selectedPlaceName" value="<?= htmlspecialchars($selected_place_name) ?>">
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Calendar Legend -->
                        <div class="calendar-legend mb-4">
                            <div class="legend-item">
                                <div class="legend-color legend-past"></div>
                                <span>Past Date</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-booked"></div>
                                <span>Already Booked</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color legend-available"></div>
                                <span>Available</span>
                            </div>
                        </div>

                        <div id="formAlert" class="mb-4"></div>

                        <form id="bookingForm">
                            <!-- Trip Type -->
                            <div class="mb-5">
                                <span class="form-label fw-bold fs-5 d-block mb-3">Trip Type</span>
                                <div class="btn-group w-100" role="group" aria-label="Trip type selection">
                                    <input type="radio" class="btn-check" name="trip_type" id="oneway" value="oneway"
                                        checked required>
                                    <label class="btn btn-outline-primary py-3 fs-5" for="oneway">One Way</label>
                                    <input type="radio" class="btn-check" name="trip_type" id="roundtrip"
                                        value="roundtrip">
                                    <label class="btn btn-outline-primary py-3 fs-5" for="roundtrip">Round Trip</label>
                                </div>
                            </div>

                            <!-- Aircraft -->
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5">Select Aircraft</label>
                                <select class="form-select form-select-lg" id="aircraftSelect" name="aircraft" required>
                                    <option value="" disabled selected>Select an aircraft...</option>
                                    <?= $aircraft_options ?>
                                </select>
                            </div>

                            <!-- Aircraft Preview -->
                            <div class="card mb-5 bg-light border-0 shadow-sm" id="aircraftPreview"
                                style="display:none;">
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
                                                <strong>Status:</strong> <span class="text-success">Available</span>
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
                                    <?= $airport_options ?>
                                </select>
                            </div>

                            <!-- Dates & Times -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold fs-5">Departure Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="datePicker" name="date"
                                        required placeholder="Select date" readonly>
                                    <div class="form-text">Red dates are unavailable (past or already booked)</div>
                                </div>
                                <div class="col-md-6 time-picker-container">
                                    <label class="form-label fw-bold fs-5">Departure Time <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="timePicker"
                                        name="departure_time" required placeholder="Select time (9:00 AM - 6:00 PM)" readonly>
                                    <div class="time-suggestions" id="timeSuggestions">
                                        <!-- Time suggestions will be populated by JavaScript -->
                                    </div>
                                    <div class="form-text">Recommended: 9:00 AM, 11:00 AM, 2:00 PM, 4:00 PM</div>
                                </div>
                                <div class="col-md-6" id="returnDateContainer" style="display:none;">
                                    <label class="form-label fw-bold fs-5">Return Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="returnDatePicker"
                                        name="return_date" placeholder="Select return date" readonly>
                                </div>
                                <div class="col-md-6" id="returnTimeContainer" style="display:none;">
                                    <label class="form-label fw-bold fs-5">Return Time <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="returnTimePicker"
                                        name="return_time" placeholder="Select return time (9:00 AM - 6:00 PM)" readonly>
                                </div>
                            </div>

                            <!-- Passengers -->
                            <div class="row g-3 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold fs-5">Number of Passengers</label>
                                    <input type="number" class="form-control form-control-lg" id="passengers"
                                        name="passengers_count" min="1" required value="1">
                                    <div class="form-text text-muted">
                                        Maximum allowed: <strong id="maxPassengers">—</strong>
                                    </div>
                                </div>
                            </div>

                            <div id="dynamicPassengers"></div>

                            <div class="alert alert-info mb-4 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Estimated Total Price:</strong><br>
                                        <span class="fs-4" id="totalPrice">₱0</span>
                                    </div>
                                    <div class="text-end">
                                        <small class="d-block">
                                            <i class="bx bx-info-circle"></i> Round-trip × 1.8
                                        </small>
                                        <small class="d-block">
                                            <i class="bx bx-shield-alt"></i> Insurance: +₱2,000/person
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-5">
                                <button type="button" id="reviewBookingBtn" class="btn btn-primary btn-lg py-3 fs-5">
                                    <i class="bx bx-search-alt me-2"></i> View Booking Summary
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="summaryContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg px-5" data-bs-dismiss="modal">
                        <i class="bx bx-edit me-2"></i> Edit Booking
                    </button>
                    <button type="button" id="confirmFinalBtn" class="btn btn-success btn-lg px-5">
                        <i class="bx bx-credit-card me-2"></i> Confirm & Pay
                    </button>
                    <div id="paypal-button-container" class="w-100 mt-3 text-center" style="min-height: 120px;"></div>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- PayPal JavaScript SDK -->
    <script
        src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(PAYPAL_CLIENT_ID) ?>&currency=PHP&components=buttons"></script>

    <script>
        // Pass PHP data to JavaScript
        const aircraftData = <?= $aircraft_json ?>;
        const bookedDates = <?= $booked_dates_json ?>;
        const userData = <?= $user_json ?>;
        const selectedPlaceId = <?= $selected_place_id ?>;
        const selectedPlaceName = "<?= addslashes($selected_place_name) ?>";
        const userId = <?= $_SESSION['user_id'] ?>;
        
        // Global variable to store booking data
        let currentBookingData = null;
    </script>
    <script src="../assets/js/booking.js" defer></script>
</body>

</html>