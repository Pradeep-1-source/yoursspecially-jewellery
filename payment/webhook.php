<?php
/**
 * YoursSpeciallyJewellery - Payment Gateway Webhook Receiver
 * Handles asynchronous payment confirmations from Razorpay / Cashfree
 */

require_once __DIR__ . '/gateway.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (empty($payload)) {
    http_response_code(400);
    exit('No payload received.');
}

$gateway = getPaymentGateway();
if (!empty($signature) && !$gateway->verifyWebhook($payload, $signature)) {
    error_log("Payment Webhook: Invalid signature verification.");
    http_response_code(400);
    exit('Invalid signature.');
}

$data = json_decode($payload, true);
$event = $data['event'] ?? '';

try {
    $db = getDBConnection();

    if ($event === 'order.paid' || $event === 'payment.captured') {
        $paymentEntity = $data['payload']['payment']['entity'] ?? [];
        $orderReceipt = $paymentEntity['notes']['receipt'] ?? ($paymentEntity['description'] ?? '');

        if (!empty($orderReceipt)) {
            $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', updated_at = NOW() WHERE order_number = ? AND payment_status != 'Paid'");
            $upd->execute([$orderReceipt]);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
