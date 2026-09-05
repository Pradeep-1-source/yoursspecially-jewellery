<?php
/**
 * YoursSpeciallyJewellery - Orders API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$orderNumber = trim($_GET['order_number'] ?? '');
if (empty($orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Order number is required.']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT order_number, order_status, payment_status, total_amount, created_at FROM orders WHERE order_number = ? LIMIT 1");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        echo json_encode(['success' => true, 'order' => $order]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order reference not found.']);
    }
} catch (Exception $e) {
    error_log("Orders API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error retrieving order status.']);
}
