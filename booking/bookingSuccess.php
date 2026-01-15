<?php
session_start();
require_once '../db/connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = $_GET['booking_id'] ?? null;
$transaction_id = $_GET['tx'] ?? 'TXN-' . time();
$payment_amount = $_GET['amount'] ?? 0;

if (!$booking_id) {
    die("No booking specified.");
}

$user_id = $_SESSION['user_id'];

try {
    $conn->begin_transaction();

    // Get booking with payment_id
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.payment_id, b.total_amount,
               l.aircraft_name, l.aircraft_type,
               p.place_name, p.location,
               s.airport, s.departure_time,
               u.first_name, u.last_name, u.email, u.phone as user_phone
        FROM Booking b
        JOIN Schedule s ON b.sched_id = s.schedule_id
        JOIN Lift l ON s.lift_id = l.lift_id
        JOIN Place p ON s.place_id = p.place_id
        JOIN Users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found.");
    }
    
    $booking = $result->fetch_assoc();
    
    // Update Payment record with transaction ID
    $update_payment = $conn->prepare("
        UPDATE Payment 
        SET payment_status = 'Paid', 
            paid_at = NOW(),
            transaction_id = ?
        WHERE payment_id = ?
    ");
    $update_payment->bind_param("si", $transaction_id, $booking['payment_id']);
    $update_payment->execute();
    
    // Update Booking status
    $update_booking = $conn->prepare("
        UPDATE Booking 
        SET booking_status = 'Confirmed'
        WHERE booking_id = ?
    ");
    $update_booking->bind_param("i", $booking_id);
    $update_booking->execute();
    
    // Get passenger details - get first passenger with valid phone number
    $passenger_stmt = $conn->prepare("
        SELECT passenger_f_name, passenger_l_name, passenger_phone_number
        FROM Passenger 
        WHERE booking_id = ? 
        AND passenger_phone_number IS NOT NULL 
        AND passenger_phone_number != ''
        AND passenger_phone_number != 0
        ORDER BY passenger_id ASC
        LIMIT 1
    ");
    $passenger_stmt->bind_param("i", $booking_id);
    $passenger_stmt->execute();
    $passenger_result = $passenger_stmt->get_result();
    $passenger = $passenger_result->fetch_assoc();
    
    // If no passenger with phone found, try to get any passenger
    if (!$passenger || empty($passenger['passenger_phone_number'])) {
        $passenger_stmt2 = $conn->prepare("
            SELECT passenger_f_name, passenger_l_name, passenger_phone_number
            FROM Passenger 
            WHERE booking_id = ? 
            ORDER BY passenger_id ASC
            LIMIT 1
        ");
        $passenger_stmt2->bind_param("i", $booking_id);
        $passenger_stmt2->execute();
        $passenger_result2 = $passenger_stmt2->get_result();
        $passenger = $passenger_result2->fetch_assoc();
        $passenger_stmt2->close();
    }
    
    // Convert phone to string and ensure it's valid
    if ($passenger && !empty($passenger['passenger_phone_number'])) {
        $passenger['passenger_phone_number'] = (string)$passenger['passenger_phone_number'];
        if ($passenger['passenger_phone_number'] === '0') {
            $passenger['passenger_phone_number'] = '';
        }
    }
    
    // Fallback to user phone if passenger phone is not available
    if (empty($passenger['passenger_phone_number']) && !empty($booking['user_phone'])) {
        $passenger['passenger_phone_number'] = (string)$booking['user_phone'];
    }
    
    $conn->commit();

    // Format dates
    $departure_datetime = new DateTime($booking['departure_time']);
    $dep_display = $departure_datetime->format('F d, Y h:i A');
    
    // Get return date if round trip
    $round_trip_stmt = $conn->prepare("
        SELECT return_departure FROM Booking 
        WHERE booking_id = ? AND is_round_trip = 1
    ");
    $round_trip_stmt->bind_param("i", $booking_id);
    $round_trip_stmt->execute();
    $round_trip_result = $round_trip_stmt->get_result();
    $round_trip = $round_trip_result->fetch_assoc();
    
    $return_display = null;
    if ($round_trip && !empty($round_trip['return_departure'])) {
        $return_datetime = new DateTime($round_trip['return_departure']);
        $return_display = $return_datetime->format('F d, Y h:i A');
    }
    
    $confirmation_date = date('F d, Y h:i A');
    
    // Store in session for success display
    $_SESSION['last_booking'] = [
        'booking_id' => $booking_id,
        'transaction_id' => $transaction_id,
        'amount' => $payment_amount,
        'aircraft' => $booking['aircraft_name'],
        'destination' => $booking['place_name'],
        'departure' => $booking['airport'],
        'departure_date_time' => $dep_display,
        'return_date_time' => $return_display,
        'passenger_name' => ($passenger['passenger_f_name'] ?? '') . ' ' . ($passenger['passenger_l_name'] ?? ''),
        'passenger_phone' => $passenger['passenger_phone_number'] ?? '',
        'user_email' => $booking['email']
    ];

} catch (Exception $e) {
    $conn->rollback();
    die("Error processing booking: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - AirLyft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }

        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 71, 171, 0.15);
        }

        .confirmation-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .confirmation-header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--emerald), var(--luxury-gold), var(--accent));
        }

        .confirmation-header h1 {
            font-family: "Playfair Display", serif;
            font-size: 2.8rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .confirmation-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--emerald);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(0, 168, 107, 0.3);
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .confirmation-body {
            padding: 40px;
        }

        .section-title {
            font-family: "Playfair Display", serif;
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            position: relative;
        }

        .section-title::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--primary);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .detail-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .detail-value.highlight {
            color: var(--emerald);
            font-weight: 700;
            font-size: 1.3rem;
        }

        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .badge-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .badge-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .alert-notification {
            background: linear-gradient(135deg, #e7f3ff, #d4e7ff);
            border: 1px solid #b3d7ff;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: center;
            border-left: 4px solid var(--primary);
        }

        .alert-notification h5 {
            color: var(--primary-dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn-action {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            min-width: 180px;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-print:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 71, 171, 0.3);
            color: white;
        }

        .btn-history {
            background: var(--emerald);
            color: white;
        }

        .btn-history:hover {
            background: #258f6b;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 168, 107, 0.3);
            color: white;
        }

        .btn-home {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-home:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white;
                padding: 0;
            }
            
            .confirmation-container {
                box-shadow: none;
                margin: 0;
                border: 1px solid #ddd;
            }
            
            .action-buttons {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .confirmation-header h1 {
                font-size: 2.2rem;
            }
            
            .confirmation-body {
                padding: 25px;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-action {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>

<body>
    <div class="confirmation-container">
        <div class="confirmation-header">
            <div class="success-icon">
                <i class='bx bx-check'></i>
            </div>
            <h1>Booking Confirmed!</h1>
            <p>Thank you for choosing AirLyft. Your journey to luxury awaits.</p>
        </div>
        
        <div class="confirmation-body">
            <!-- Transaction Details -->
            <h3 class="section-title">Transaction Details</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-label">Booking Reference</div>
                    <div class="detail-value">#<?= htmlspecialchars($booking_id) ?></div>
                    
                    <div class="detail-label">Transaction ID</div>
                    <div class="detail-value"><?= htmlspecialchars($transaction_id) ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Confirmation Date & Time</div>
                    <div class="detail-value"><?= htmlspecialchars($confirmation_date) ?></div>
                    
                    <div class="detail-label">Amount Paid</div>
                    <div class="detail-value highlight">₱<?= number_format($payment_amount, 2) ?></div>
                </div>
            </div>
            
            <!-- Flight Information -->
            <h3 class="section-title">Flight Information</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-label">Aircraft</div>
                    <div class="detail-value"><?= htmlspecialchars($booking['aircraft_name']) ?> (<?= htmlspecialchars($booking['aircraft_type']) ?>)</div>
                    
                    <div class="detail-label">Departure Airport</div>
                    <div class="detail-value"><?= htmlspecialchars($booking['airport']) ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Destination</div>
                    <div class="detail-value"><?= htmlspecialchars($booking['place_name']) ?></div>
                    <div class="detail-value"><?= htmlspecialchars($booking['location']) ?></div>
                    
                    <div class="detail-label">Departure Date & Time</div>
                    <div class="detail-value"><?= $dep_display ?></div>
                    
                    <?php if ($return_display): ?>
                        <div class="detail-label">Return Date & Time</div>
                        <div class="detail-value"><?= $return_display ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">Primary Passenger</div>
                    <div class="detail-value"><?= htmlspecialchars($passenger['passenger_f_name'] ?? '') . ' ' . htmlspecialchars($passenger['passenger_l_name'] ?? '') ?></div>
                    
                    <div class="detail-label">Booking Status</div>
                    <div><span class="badge-status badge-confirmed">Confirmed</span></div>
                    
                    <div class="detail-label">Payment Status</div>
                    <div><span class="badge-status badge-paid">Paid (PayPal)</span></div>
                </div>
            </div>
            
            <!-- Notification -->
            <div class="alert-notification">
                <h5>Booking Secured!</h5>
                <p>
                    A confirmation SMS has been sent to <strong><?= htmlspecialchars($passenger['passenger_phone_number'] ?? 'N/A') ?></strong>.<br>
                    A detailed confirmation email has been sent to <strong><?= htmlspecialchars($booking['email']) ?></strong>.
                </p>
                <small class="text-muted">Please check your inbox (and spam/junk folder) for complete details.</small>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons no-print">
                <button onclick="window.print()" class="btn-action btn-print">
                    <i class='bx bx-printer'></i> Print Ticket
                </button>
                <a href="../booking/bookingHistory.php" class="btn-action btn-history">
                    <i class='bx bx-history'></i> View Transaction History
                </a>
                <a href="../index.php" class="btn-action btn-home">
                    <i class='bx bx-home'></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-trigger print dialog if requested
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === 'true') {
                setTimeout(() => {
                    window.print();
                }, 1000);
            }
            
            // Add success animation
            const successIcon = document.querySelector('.success-icon');
            successIcon.style.animation = 'pulse 2s ease-in-out';
            
            // Create CSS for animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>