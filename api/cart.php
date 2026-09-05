<?php
/**
 * YoursSpeciallyJewellery - Cart & Shopping Bag API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $db = getDBConnection();
    $cartId = getActiveCartId();

    switch ($action) {
        case 'add':
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));

            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product selection.']);
                exit;
            }

            // Verify product existence and stock
            $pStmt = $db->prepare("SELECT id, name, price, sale_price, stock, status FROM products WHERE id = ? LIMIT 1");
            $pStmt->execute([$productId]);
            $product = $pStmt->fetch();

            if (!$product || $product['status'] != 1) {
                echo json_encode(['success' => false, 'message' => 'Product is currently unavailable.']);
                exit;
            }

            if ($product['stock'] < 1) {
                echo json_encode(['success' => false, 'message' => 'This jewellery creation is currently sold out.']);
                exit;
            }

            $unitPrice = (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) ? $product['sale_price'] : $product['price'];

            // Check if product already in cart
            $itemStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1");
            $itemStmt->execute([$cartId, $productId]);
            $existingItem = $itemStmt->fetch();

            $newTotalQty = $quantity + ($existingItem ? (int)$existingItem['quantity'] : 0);

            if ($newTotalQty > $product['stock']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Only ' . $product['stock'] . ' pieces available in stock.'
                ]);
                exit;
            }

            if ($existingItem) {
                $upd = $db->prepare("UPDATE cart_items SET quantity = ?, price = ? WHERE id = ?");
                $upd->execute([$newTotalQty, $unitPrice, $existingItem['id']]);
            } else {
                $ins = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $ins->execute([$cartId, $productId, $quantity, $unitPrice]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Added ' . $product['name'] . ' to your shopping bag.',
                'cart_count' => getCartItemCount()
            ]);
            exit;

        case 'update':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);

            if ($itemId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid item.']);
                exit;
            }

            if ($quantity <= 0) {
                $del = $db->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
                $del->execute([$itemId, $cartId]);
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from your shopping bag.',
                    'cart_count' => getCartItemCount()
                ]);
                exit;
            }

            // Verify stock limit
            $chk = $db->prepare("SELECT ci.id, ci.product_id, p.stock, p.name FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.id = ? AND ci.cart_id = ?");
            $chk->execute([$itemId, $cartId]);
            $item = $chk->fetch();

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found in bag.']);
                exit;
            }

            if ($quantity > $item['stock']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Only ' . $item['stock'] . ' pieces available in stock for ' . $item['name'] . '.'
                ]);
                exit;
            }

            $upd = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND cart_id = ?");
            $upd->execute([$quantity, $itemId, $cartId]);

            echo json_encode([
                'success' => true,
                'message' => 'Shopping bag updated.',
                'cart_count' => getCartItemCount()
            ]);
            exit;

        case 'remove':
            $itemId = (int)($_POST['item_id'] ?? 0);
            $del = $db->prepare("DELETE FROM cart_items WHERE id = ? AND cart_id = ?");
            $del->execute([$itemId, $cartId]);

            echo json_encode([
                'success' => true,
                'message' => 'Item removed.',
                'cart_count' => getCartItemCount()
            ]);
            exit;

        case 'apply_coupon':
            $code = strtoupper(trim($_POST['coupon_code'] ?? ''));

            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
                exit;
            }

            $today = date('Y-m-d');
            $cStmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) LIMIT 1");
            $cStmt->execute([$code, $today, $today]);
            $coupon = $cStmt->fetch();

            if (!$coupon) {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired promo code.']);
                exit;
            }

            // Calculate current subtotal
            $subStmt = $db->prepare("SELECT SUM(quantity * price) AS subtotal FROM cart_items WHERE cart_id = ?");
            $subStmt->execute([$cartId]);
            $subtotal = (float)$subStmt->fetchColumn();

            if ($subtotal < (float)$coupon['minimum_order']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Minimum order amount of ' . formatPrice($coupon['minimum_order']) . ' required for coupon ' . $code . '.'
                ]);
                exit;
            }

            // Store applied coupon in session
            $_SESSION['applied_coupon'] = [
                'id' => $coupon['id'],
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'discount_value' => (float)$coupon['discount_value'],
                'maximum_discount' => (float)($coupon['maximum_discount'] ?? 0)
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Coupon ' . $code . ' applied successfully!'
            ]);
            exit;

        case 'remove_coupon':
            unset($_SESSION['applied_coupon']);
            echo json_encode(['success' => true, 'message' => 'Coupon removed.']);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
            exit;
    }
} catch (Exception $e) {
    error_log("Cart API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred while updating the bag.']);
    exit;
}
