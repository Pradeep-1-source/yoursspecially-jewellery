<?php
/**
 * YoursSpeciallyJewellery - Razorpay Signature Verification API Endpoint
 * Endpoint: POST /api/verify-payment (or /api/verify-payment.php)
 *
 * Algorithm: HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
 * Compare generated signature with razorpay_signature
 *
 * Request Payload (JSON or Form POST):
 * {
 *   "razorpay_order_id": "order_xxx",      // Required
 *   "razorpay_payment_id": "pay_xxx",      // Required
 *   "razorpay_signature": "sig_xxx",       // Required
 *   "order_id": 15                         // Optional: Internal store order ID
 * }
 *
 * Response (JSON):
 * {
 *   "success": true,
 *   "message": "Payment verified successfully.",
 *   "redirect_url": "order-success.php?order=YSJ-xxx"
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

$razorpayOrderId = trim((string)($data['razorpay_order_id'] ?? ''));
$razorpayPaymentId = trim((string)($data['razorpay_payment_id'] ?? ''));
$razorpaySignature = trim((string)($data['razorpay_signature'] ?? ''));
$storeOrderId = (int)($data['order_id'] ?? 0);

// Validate missing fields
if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required payment signature fields: razorpay_order_id, razorpay_payment_id, and razorpay_signature are all required.'
    ]);
    exit;
}

// Cryptographic HMAC-SHA256 verification
$gateway = getPaymentGateway();
$isValid = $gateway->verifyPayment([
    'razorpay_order_id' => $razorpayOrderId,
    'razorpay_payment_id' => $razorpayPaymentId,
    'razorpay_signature' => $razorpaySignature
]);

// Handle signature mismatch: Return HTTP 400 and NEVER mark as paid
if (!$isValid) {
    if ($storeOrderId > 0) {
        try {
            $db = getDBConnection();
            $fail = $db->prepare("UPDATE orders SET payment_status = 'Failed', updated_at = NOW() WHERE id = ?");
            $fail->execute([$storeOrderId]);
        } catch (Exception $e) {
            error_log("Failed to update failed order status: " . $e->getMessage());
        }
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Payment signature verification failed. HMAC SHA-256 signature mismatch.'
    ]);
    exit;
}

// Signature is valid!
$redirectUrl = BASE_URL . 'order-success.php';

// Update store database if storeOrderId is provided
if ($storeOrderId > 0) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
        $stmt->execute([$storeOrderId]);
        $order = $stmt->fetch();

        if ($order) {
            // Update order as Paid and Confirmed
            try {
                $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', transaction_id = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$razorpayPaymentId, $storeOrderId]);
            } catch (Throwable $t) {
                $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                $upd->execute([$storeOrderId]);
            }

            // Deduct stock for ordered items
            $itemsStmt = $db->prepare("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$storeOrderId]);
            $items = $itemsStmt->fetchAll();

            foreach ($items as $item) {
                if (!empty($item['variant_id'])) {
                    $varStockStmt = $db->prepare("UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $varStockStmt->execute([$item['quantity'], $item['variant_id']]);
                }
                if (!empty($item['product_id'])) {
                    $stockStmt = $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                    $stockStmt->execute([$item['quantity'], $item['product_id']]);
                }
            }

            // Clear user's active cart
            $cartId = getActiveCartId();
            $delCart = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $delCart->execute([$cartId]);
            unset($_SESSION['applied_coupon']);

            $redirectUrl = BASE_URL . 'order-success.php?order=' . urlencode($order['order_number']);
        }
    } catch (Exception $e) {
        error_log("Database update on payment success error: " . $e->getMessage());
    }
}

// Return HTTP 200 Success
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Payment verified successfully.',
    'payment_id' => $razorpayPaymentId,
    'order_id' => $razorpayOrderId,
    'redirect_url' => $redirectUrl
]);
exit;
