<?php
session_start();
require_once '../db/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    header("Location: ../booking/transactionHistory.php");
    exit();
}

$query = "
    SELECT 
        b.*,
        p.place_name,
        p.location,
        p.description as dest_description,
        l.aircraft_name,
        l.aircraft_type,
        l.capacity,
        u.first_name,
        u.last_name
        u.email,
        u.phone
    FROM bookings b
    JOIN place p ON b.place_id = p.place_id
    JOIN lift l ON b.lift_id = l.lift_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = ? AND b.user_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();

$passenger_query = "SELECT * FROM passenger WHERE booking_id = ?";
$passenger_stmt = $conn->prepare($passenger_query);
$passenger_stmt->bind_param("i", $booking_id);
$passenger_stmt->execute();
$passengers_result = $passenger_stmt->get_result();
$passengers = [];
while ($row = $passengers_result->fetch_assoc()) {
    $passengers[] = $row;
}

$pageTitle = "Booking Details #" . $booking_id;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AirLyft | <?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .booking-details-container {
            max-width: 800px;
            margin: 100px auto 50px;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .detail-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }

        .passenger-list {
            list-style: none;
            padding: 0;
        }

        .passenger-list li {
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 5px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="booking-details-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Booking Details</h1>
            <div class="d-flex gap-2">
                <span class="status-badge bg-success text-white">
                    <?= htmlspecialchars($booking['booking_status']) ?>
                </span>
                <span class="status-badge bg-info text-white">
                    <?= htmlspecialchars($booking['payment_status']) ?>
                </span>
            </div>
        </div>

        <div class="detail-section">
            <h3 class="h5 mb-3 text-primary">
                <i class='bx bx-plane'></i> Flight Information
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Aircraft:</strong> <?= htmlspecialchars($booking['aircraft_name']) ?></p>
                    <p><strong>Trip Type:</strong> <?= htmlspecialchars(ucfirst($booking['trip_type'])) ?></p>
                    <p><strong>Departure:</strong> <?= htmlspecialchars($booking['departure_airport']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Destination:</strong> <?= htmlspecialchars($booking['destination_name']) ?></p>
                    <p><strong>Departure Date:</strong> <?= date('F d, Y', strtotime($booking['departure_date'])) ?></p>
                    <p><strong>Departure Time:</strong> <?= $booking['departure_time'] ?></p>
                </div>
            </div>
        </div>

        <div class="detail-section">
            <h3 class="h5 mb-3 text-primary">
                <i class='bx bx-user'></i> Passenger Information
            </h3>
            <p><strong>Total Passengers:</strong> <?= $booking['passengers_count'] ?></p>
            <?php if (!empty($passengers)): ?>
                <ul class="passenger-list">
                    <?php foreach ($passengers as $passenger): ?>
                        <li>
                            <?= htmlspecialchars($passenger['full_name']) ?>
                            <?php if ($passenger['has_insurance']): ?>
                                <span class="badge bg-success ms-2">Insured</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3 class="h5 mb-3 text-primary">
                <i class='bx bx-credit-card'></i> Payment Information
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Total Amount:</strong> ₱<?= number_format($booking['total_amount'], 2) ?></p>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($booking['payment_method'] ?? 'PayPal') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($booking['transaction_id'] ?? 'N/A') ?></p>
                    <p><strong>Booking Date:</strong> <?= date('F d, Y', strtotime($booking['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($booking['special_requests'])): ?>
            <div class="detail-section">
                <h3 class="h5 mb-3 text-primary">
                    <i class='bx bx-note'></i> Special Requests
                </h3>
                <p><?= nl2br(htmlspecialchars($booking['special_requests'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="transactionHistory.php" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Back to History
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class='bx bx-printer'></i> Print Details
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>