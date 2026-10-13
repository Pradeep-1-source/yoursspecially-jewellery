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
            $orderCheck = $db->prepare("SELECT id, payment_status FROM orders WHERE order_number = ?");
            $orderCheck->execute([$orderReceipt]);
            $ord = $orderCheck->fetch();
            if ($ord && $ord['payment_status'] !== 'Paid') {
                $upd = $db->prepare("UPDATE orders SET payment_status = 'Paid', order_status = 'Confirmed', updated_at = NOW() WHERE id = ?");
                $upd->execute([$ord['id']]);

                $itemsStmt = $db->prepare("SELECT product_id, variant_id, quantity FROM order_items WHERE order_id = ?");
                $itemsStmt->execute([$ord['id']]);
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
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    error_log("Webhook processing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
