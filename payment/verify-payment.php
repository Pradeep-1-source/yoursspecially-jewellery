<?php
/**
 * YoursSpeciallyJewellery - Server-Side Payment Verification
 * Supports both JSON API calls and HTML Form POST submissions.
 * NEVER trusts the browser alone - validates cryptographic HMAC signatures.
 */

// If incoming request is JSON, delegate directly to the standard JSON API endpoint
$isJsonRequest = (
    (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

if ($isJsonRequest) {
    require_once dirname(__DIR__) . '/api/verify-payment.php';
    exit;
}

require_once __DIR__ . '/gateway.php';
require_once __DIR__ . '/../includes/auth.php';

$orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$razorpayOrderId = trim((string)($_POST['razorpay_order_id'] ?? ''));
$razorpayPaymentId = trim((string)($_POST['razorpay_payment_id'] ?? ''));
$razorpaySignature = trim((string)($_POST['razorpay_signature'] ?? ''));

if ($orderId <= 0) {
    setFlash('error', 'Invalid order verification attempt.');
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        setFlash('error', 'Order not found.');
        header("Location: " . BASE_URL . "cart.php");
        exit;
    }

    $gateway = getPaymentGateway();
    $isValid = $gateway->verifyPayment([
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_signature' => $razorpaySignature
    ]);

    if ($isValid) {
        // Payment verified! Update order status
        try {
            $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', transaction_id = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$razorpayPaymentId, $orderId]);
        } catch (Throwable $t) {
            $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', updated_at = NOW() WHERE id = ?");
            $upd->execute([$orderId]);
        }

        // Deduct inventory stock for ordered items
        $itemsStmt = $db->prepare("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
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

        // Clear user's shopping bag
        $cartId = getActiveCartId();
        $delCart = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $delCart->execute([$cartId]);
        unset($_SESSION['applied_coupon']);

        // Redirect to success page
        header("Location: " . BASE_URL . "order-success.php?order=" . urlencode($order['order_number']));
        exit;
    } else {
        // Payment failed cryptographic signature verification
        $fail = $db->prepare("UPDATE orders SET payment_status = 'Failed', updated_at = NOW() WHERE id = ?");
        $fail->execute([$orderId]);

        setFlash('error', 'Payment verification was unsuccessful. Please check your bank transaction or try another payment method.');
        header("Location: " . BASE_URL . "checkout.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    setFlash('error', 'An error occurred during payment verification. Please contact boutique support.');
    header("Location: " . BASE_URL . "cart.php");
    exit;
}
