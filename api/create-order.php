<?php
/**
 * YoursSpeciallyJewellery - Razorpay Order Creation API Endpoint
 * Endpoint: POST /api/create-order (or /api/create-order.php)
 *
 * Request Payload (JSON or Form POST):
 * {
 *   "amount": 50000,       // Required: Amount in paise (minimum 100 paise = ₹1.00)
 *   "currency": "INR",     // Optional: defaults to INR
 *   "receipt": "rcpt_123", // Optional receipt identifier
 *   "order_id": 15         // Optional: Store internal order ID
 * }
 *
 * Response (JSON):
 * {
 *   "success": true,
 *   "order_id": "order_xxx",
 *   "amount": 50000,
 *   "currency": "INR",
 *   "key_id": "rzp_test_xxx"
 * }
 */

header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/payment/gateway.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Support both JSON input and standard form POST
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$orderPayload = [];

// Case A: Internal store order ID provided
if (!empty($data['order_id']) && is_numeric($data['order_id'])) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$data['order_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Order not found in database.'
            ]);
            exit;
        }

        $orderPayload = [
            'amount' => (int)round((float)$order['total_amount'] * 100),
            'currency' => 'INR',
            'receipt' => $order['order_number'],
            'customer_name' => $order['customer_name'] ?? '',
            'customer_email' => $order['customer_email'] ?? ''
        ];
    } catch (Exception $e) {
        error_log("Order lookup error in create-order: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal database error looking up order.'
        ]);
        exit;
    }
} else {
    // Case B: Direct parameters (amount, currency, receipt)
    $amount = $data['amount'] ?? 0;
    
    // If passed as decimal string/float (e.g. 500.00), convert to paise if not already integer paise
    if (is_numeric($amount)) {
        // If amount contains decimal point (e.g. "500.50"), convert to paise
        if (strpos((string)$amount, '.') !== false) {
            $amountInPaise = (int)round((float)$amount * 100);
        } else {
            $amountInPaise = (int)$amount;
        }
    } else {
        $amountInPaise = 0;
    }

    $orderPayload = [
        'amount' => $amountInPaise,
        'currency' => strtoupper($data['currency'] ?? 'INR'),
        'receipt' => $data['receipt'] ?? ('YSJ-' . time()),
        'customer_name' => $data['customer_name'] ?? 'Boutique Customer',
        'customer_email' => $data['customer_email'] ?? ''
    ];
}

// Validate minimum amount: >= 100 paise (₹1.00)
if (($orderPayload['amount'] ?? 0) < 100) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed: Minimum order amount must be at least 100 paise (₹1.00).'
    ]);
    exit;
}

// Call Razorpay Orders API
try {
    $gateway = getPaymentGateway();
    $result = $gateway->createGatewayOrder($orderPayload);

    if (!empty($result['success'])) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'order_id' => $result['order_id'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'key_id' => $result['key_id']
        ]);
        exit;
    }

    // Handle failure
    $statusCode = $result['status_code'] ?? 500;
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $result['message'] ?? 'Failed to create order with Razorpay.'
    ]);
    exit;
} catch (Exception $e) {
    error_log("Create Razorpay Order Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error while initializing Razorpay order.'
    ]);
    exit;
}

