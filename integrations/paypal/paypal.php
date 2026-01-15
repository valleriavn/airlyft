<?php
// booking/paypal.php
// Handles PayPal payment for a specific booking
// Call example: paypal.php?booking_id=123

session_start();
// require_once '../db/connect.php';
require_once '../integrations/paypal/config.php';  // your PayPal config file

// ================== BASIC SECURITY CHECKS ==================
if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];
$user_id    = (int)$_SESSION['user_id'];

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.total_amount, b.booking_status,
           p.id AS payment_id, p.payment_status
    FROM Booking b
    JOIN Payment p ON b.payment_id = p.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    die("Booking not found or access denied.");
}

if ($booking['booking_status'] !== 'Pending' || $booking['payment_status'] !== 'Pending') {
    die("This booking is no longer pending payment.");
}

$amount_php = number_format($booking['total_amount'], 2, '.', '');
$amount_display = number_format($booking['total_amount'], 2);

// ================== PAYPAL CONFIG ==================
$paypal_client_id = PAYPAL_CLIENT_ID; // from config.php
// $env = PAYPAL_ENV; // 'sandbox' or 'live'

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pay with PayPal - AirLyft Booking #<?= $booking_id ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($paypal_client_id) ?>&currency=PHP"></script>
    
    <style>
        body {
            background: #f8f9fa;
            padding-top: 70px;
        }
        .payment-container {
            max-width: 540px;
            margin: 0 auto;
        }
        .amount-box {
            font-size: 2.8rem;
            font-weight: 700;
            color: #0d6efd;
        }
        .status-box {
            min-height: 120px;
        }
    </style>
</head>
<body>

<div class="container payment-container">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center py-4">
            <h3 class="mb-0">Complete Your Payment</h3>
        </div>

        <div class="card-body text-center py-5">
            <h4 class="mb-4">Booking #<?= $booking_id ?></h4>
            
            <div class="amount-box mb-4">
                ₱<?= $amount_display ?>
            </div>

            <p class="lead text-muted mb-5">
                Secure payment via PayPal
            </p>

            <div id="paypal-button-container" class="mx-auto mb-4"></div>
            
            <div id="payment-result" class="status-box mt-4"></div>
        </div>

        <div class="card-footer text-center text-muted">
            <small>Transaction secured by PayPal • You will be redirected after payment</small>
        </div>
    </div>
</div>

<script>
const resultDiv = document.getElementById('payment-result');
const container = document.getElementById('paypal-button-container');

function showMessage(text, type = 'info') {
    resultDiv.innerHTML = `<div class="alert alert-${type}">${text}</div>`;
}

showMessage("Click the button below to pay securely with PayPal", "info");

paypal.Buttons({
    style: {
        shape: 'rect',
        color: 'gold',
        layout: 'vertical',
        label: 'paypal',
        height: 55
    },

    createOrder: function(data, actions) {
        showMessage("Creating secure payment order...", "info");

        return fetch('../../integrations/paypal/create_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                booking_id: <?= $booking_id ?>,
                amount: "<?= $amount_php ?>"
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.order_id) {
                throw new Error(data.error || 'Failed to create PayPal order');
            }
            return data.order_id;
        })
        .catch(err => {
            showMessage("Error creating payment order: " + err.message, "danger");
            throw err;
        });
    },

    onApprove: function(data, actions) {
        showMessage("Processing your payment... Please wait.", "info");

        return fetch('../../integrations/paypal/capture_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: data.orderID
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showMessage(`
                    <strong>Payment Successful!</strong><br>
                    Transaction ID: ${result.transaction_id || '—'}<br><br>
                    Redirecting...
                `, "success");

                // Optional delay before redirect
                setTimeout(() => {
                    window.location.href = `../success.php?booking_id=<?= $booking_id ?>`;
                }, 2500);
            } else {
                showMessage("Payment capture failed: " + (result.error || "Unknown error"), "danger");
            }
        })
        .catch(err => {
            showMessage("Error finalizing payment: " + err.message, "danger");
        });
    },

    onCancel: function() {
        showMessage("Payment was cancelled.", "warning");
    },

    onError: function(err) {
        console.error(err);
        showMessage("An error occurred with PayPal. Please try again or contact support.", "danger");
    }

}).render('#paypal-button-container');
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>