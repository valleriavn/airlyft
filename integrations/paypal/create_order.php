<?php
// integrations/paypal/create_order.php
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$amount = filter_var($input['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$booking_id = trim($input['booking_id'] ?? '');

if ($amount <= 0 || $amount > 1000000) {
    ob_end_clean();
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid amount']));
}

function get_paypal_token() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => PAYPAL_API_BASE . "/v1/oauth2/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => "grant_type=client_credentials",
        CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET,
        CURLOPT_HTTPHEADER     => ["Accept: application/json"]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("PayPal token error: HTTP $http_code - $response");
        return false;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

$token = get_paypal_token();
if (!$token) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['error' => 'PayPal authentication failed']));
}

$payload = [
    "intent"         => "CAPTURE",
    "purchase_units" => [[
        "reference_id"   => $booking_id ?: 'AIR_' . time(),
        "description"    => "AirLyft Private Flight Booking - Booking ID: " . ($booking_id ?: 'NEW'),
        "amount"         => [
            "currency_code" => "PHP",
            "value"         => number_format($amount, 2, '.', '')
        ]
    ]],
    "payment_source" => [
        "paypal" => [
            "experience_context" => [
                "brand_name"             => "AirLyft",
                "user_action"            => "PAY_NOW",
                "shipping_preference"    => "NO_SHIPPING",
                "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED"
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => PAYPAL_API_BASE . "/v2/checkout/orders",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
        "Prefer: return=representation",
        "PayPal-Request-Id: " . uniqid('airlyft-', true)
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    error_log("PayPal cURL error: $curl_error");
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['error' => 'Network error: ' . $curl_error]));
}

if ($http_code >= 200 && $http_code < 300) {
    $order = json_decode($response, true);
    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'order_id' => $order['id']
    ]);
} else {
    error_log("PayPal order creation failed [$http_code]: $response");
    ob_end_clean();
    http_response_code($http_code ?: 500);
    echo json_encode([
        'error' => 'Failed to create PayPal order',
        'paypal_response' => json_decode($response, true)
    ]);
}