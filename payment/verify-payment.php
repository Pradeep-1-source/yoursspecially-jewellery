<?php
/**
 * YoursSpeciallyJewellery - Server-Side Payment Verification
 * NEVER trusts the browser alone - validates cryptographic HMAC signatures
 */

require_once __DIR__ . '/gateway.php';
require_once __DIR__ . '/../includes/auth.php';

$orderId = (int)($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
$razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
$razorpaySignature = $_POST['razorpay_signature'] ?? '';

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
        $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', updated_at = NOW() WHERE id = ?");
        $upd->execute([$orderId]);

        // Deduct inventory stock for ordered items
        $itemsStmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
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
