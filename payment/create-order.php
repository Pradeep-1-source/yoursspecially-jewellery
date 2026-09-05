<?php
/**
 * YoursSpeciallyJewellery - Payment Order Creation Endpoint
 * Initializes server-side order with payment gateway
 */

header('Content-Type: application/json');
require_once __DIR__ . '/gateway.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    $gateway = getPaymentGateway();
    $res = $gateway->createGatewayOrder($order);

    echo json_encode($res);
} catch (Exception $e) {
    error_log("Create payment order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment initialization failed.']);
}
