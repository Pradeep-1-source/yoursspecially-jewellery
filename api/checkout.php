<?php
/**
 * YoursSpeciallyJewellery - Checkout API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token.']);
    exit;
}

try {
    $db = getDBConnection();
    $cartId = getActiveCartId();

    // Verify cart items
    $stmt = $db->prepare("SELECT ci.product_id, ci.quantity, ci.price, p.name, p.stock 
                          FROM cart_items ci 
                          JOIN products p ON ci.product_id = p.id 
                          WHERE ci.cart_id = ?");
    $stmt->execute([$cartId]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Shopping bag is empty.']);
        exit;
    }

    // Verify stock
    foreach ($items as $item) {
        if ($item['quantity'] > $item['stock']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Insufficient stock for ' . $item['name'] . ' (Only ' . $item['stock'] . ' available).'
            ]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'message' => 'Checkout verified successfully.']);
} catch (Exception $e) {
    error_log("Checkout API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error verifying checkout.']);
}
