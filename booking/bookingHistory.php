<?php
// booking_history.php
session_start();
require_once '../db/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$user_stmt = $conn->prepare("SELECT first_name, last_name, created_at FROM Users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
    $member_since = date('Y', strtotime($user['created_at']));
} else {
    $user_name = 'User';
    $member_since = date('Y');
}
$user_stmt->close();

$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_status,
        b.total_amount,
        p.method,
        p.payment_status,
        p.paid_at,
        p.transaction_id,
        s.departure_time,
        s.arrival_time,
        s.airport,
        l.aircraft_name,
        pl.place_name,
        pl.location
    FROM Booking b
    JOIN Payment p ON b.payment_id = p.payment_id
    JOIN Schedule s ON b.sched_id = s.schedule_id
    JOIN Lift l ON s.lift_id = l.lift_id
    JOIN Place pl ON s.place_id = pl.place_id
    WHERE b.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate statistics
$total_bookings = count($bookings);
$total_spent = 0;
foreach ($bookings as $booking) {
    $total_spent += $booking['total_amount'];
}

$transactions = [];
foreach ($bookings as $booking) {
    $transactions[] = [
        'booking_id' => $booking['booking_id'],
        'booking_reference' => 'BOOK' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT), // Generate reference
        'booking_status' => $booking['booking_status'],
        'total_amount' => $booking['total_amount'],
        'payment_method' => $booking['method'],
        'payment_status' => $booking['payment_status'],
        'transaction_id' => $booking['transaction_id'],
        'departure_time' => $booking['departure_time'],
        'arrival_time' => $booking['arrival_time'],
        'airport' => $booking['airport'],
        'aircraft_type' => $booking['aircraft_name'],
        'place_name' => $booking['place_name'],
        'location' => $booking['location']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AirLyft | Transaction History</title>
    <link rel="icon" href="../assets/img/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
            background: #f8f9fa;
            color: var(--dark);
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

        .navbar {
            background: rgba(0, 71, 171, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.5rem 0;
            min-height: 85px;
        }

        .nav-logo {
            height: 80%;
            max-height: 48px;
            object-fit: contain;
        }

        .nav-link {
            color: white !important;
            font-weight: 500;
        }

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
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .transaction-hero {
            background: linear-gradient(rgba(0, 71, 171, 0.85), rgba(0, 45, 114, 0.85));
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .transaction-hero h1 {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .transaction-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            margin-bottom: 2rem;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .stats-card p {
            color: #666;
            margin-bottom: 0;
        }

        .transaction-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .transaction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .transaction-card.confirmed {
            border-left-color: var(--emerald);
        }

        .transaction-card.pending {
            border-left-color: var(--luxury-gold);
        }

        .transaction-card.cancelled {
            border-left-color: var(--accent);
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .transaction-details {
            color: #666;
            font-size: 0.9rem;
        }

        .footer {
            background: var(--primary-dark);
            color: #ffffff;
            padding: 3rem 0;
            margin-top: 3rem;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }

            .transaction-hero h1 {
                font-size: 2.5rem;
            }

            .stats-card h3 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <img src="../assets/img/logo.png" alt="AirLyft Logo" class="nav-logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 gap-lg-4">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../booking/destinations.php">Destinations</a>
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
                    <li class="nav-item dropdown user-dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user_name, 0, 1)) ?>
                            </div>
                            <span class="d-none d-md-inline"><?= htmlspecialchars($user_name) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="bookingHistory.php"><i class='bx bx-history'></i> Transaction History</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class='bx bx-log-out'></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="transaction-hero">
        <div class="container">
            <h1>Transaction History</h1>
            <p>View all your bookings and flight history with AirLyft</p>
        </div>
    </header>

    <main class="container py-5">
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3><?= $total_bookings ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3>₱<?= number_format($total_spent, 2) ?></h3>
                    <p>Total Amount Spent</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h3><?= date('Y') ?></h3>
                    <p>Member Since <?= $member_since ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Your Bookings</h2>

                <?php if (empty($transactions)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class='bx bx-info-circle' style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No transactions found</h4>
                        <p class="mb-0">You haven't made any bookings yet. Start your journey with AirLyft!</p>
                        <a href="../booking/destinations.php" class="btn btn-primary mt-3">Book Now</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <?php
                        $status_class = '';
                        if ($transaction['booking_status'] === 'Confirmed') {
                            $status_class = 'confirmed';
                        } elseif ($transaction['booking_status'] === 'Pending') {
                            $status_class = 'pending';
                        } else {
                            $status_class = 'cancelled';
                        }
                        
                        $payment_badge_class = ($transaction['payment_status'] === 'Paid') ? 'badge-paid' : 'badge-unpaid';
                        ?>
                        <div class="transaction-card <?= $status_class ?>">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="amount mb-2">₱<?= number_format($transaction['total_amount'], 2) ?></div>
                                    <div class="transaction-details">
                                        <small>Ref: <?= $transaction['booking_reference'] ?></small><br>
                                        <small>Booking ID: #<?= $transaction['booking_id'] ?></small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <strong><?= htmlspecialchars($transaction['place_name']) ?></strong><br>
                                    <small class="transaction-details">
                                        <?= htmlspecialchars($transaction['aircraft_type']) ?><br>
                                        <?= htmlspecialchars($transaction['location']) ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <div class="transaction-details">
                                        <strong>Departure:</strong><br>
                                        <?= date('M d, Y', strtotime($transaction['departure_time'])) ?><br>
                                        <?= date('h:i A', strtotime($transaction['departure_time'])) ?><br>
                                        <?= htmlspecialchars($transaction['airport']) ?>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="status-badge badge-<?= strtolower($transaction['booking_status']) ?>">
                                        <?= $transaction['booking_status'] ?>
                                    </span>
                                    <br>
                                    <?php if ($transaction['payment_status']): ?>
                                        <span class="status-badge <?= $payment_badge_class ?> mt-2">
                                            <?= $transaction['payment_status'] ?> (<?= $transaction['payment_method'] ?>)
                                        </span>
                                    <?php endif; ?>
                                    <br>
                                    <?php if ($transaction['transaction_id']): ?>
                                        <small class="transaction-details">Txn: <?= $transaction['transaction_id'] ?></small><br>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <h3 class="mb-3" style="font-family: 'Playfair Display', serif;">AirLyft</h3>
                    <p class="mb-0" style="opacity: 0.8;">Luxury Private Air Travel</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        <a href="mailto:AirLyft16@gmail.com" class="text-white text-decoration-none me-3">
                            <i class='bx bxs-envelope'></i> AirLyft16@gmail.com
                        </a>
                        <a href="tel:+639232912527" class="text-white text-decoration-none">
                            <i class='bx bxs-phone'></i> +63 923 291 2527
                        </a>
                    </p>
                </div>
            </div>
            <div class="text-center mt-4" style="opacity: 0.6;">
                <small>© <?= date('Y') ?> Airlyft Travel Co. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                navbar.classList.toggle('scrolled', window.scrollY > 100);
            });
        });
    </script>
</body>

</html>