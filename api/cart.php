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
            $variantId = isset($_POST['variant_id']) && (int)$_POST['variant_id'] > 0 ? (int)$_POST['variant_id'] : null;

            if ($productId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product selection.']);
                exit;
            }

            // Verify product existence and status
            $pStmt = $db->prepare("SELECT id, name, price, sale_price, stock, status FROM products WHERE id = ? LIMIT 1");
            $pStmt->execute([$productId]);
            $product = $pStmt->fetch();

            if (!$product || $product['status'] != 1) {
                echo json_encode(['success' => false, 'message' => 'Product is currently unavailable.']);
                exit;
            }

            $unitPrice = (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) ? (float)$product['sale_price'] : (float)$product['price'];

            // Check if product has active variants configured
            $vStmt = $db->prepare("SELECT id, variant_type, variant_value, sku, price, stock FROM product_variants WHERE product_id = ? AND status = 1");
            $vStmt->execute([$productId]);
            $availableVariants = $vStmt->fetchAll();

            $variantInfo = null;
            $availableStock = (int)$product['stock'];

            if (!empty($availableVariants)) {
                // Product requires a variant selection
                if (!$variantId) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Please select an option (e.g. Size, Colour, or Material) before adding to bag.',
                        'require_variant' => true
                    ]);
                    exit;
                }

                $selectedVariant = null;
                foreach ($availableVariants as $av) {
                    if ((int)$av['id'] === $variantId) {
                        $selectedVariant = $av;
                        break;
                    }
                }

                if (!$selectedVariant) {
                    echo json_encode(['success' => false, 'message' => 'Invalid variant option selected.']);
                    exit;
                }

                if ($selectedVariant['stock'] < 1) {
                    echo json_encode(['success' => false, 'message' => 'Selected option (' . $selectedVariant['variant_value'] . ') is currently out of stock.']);
                    exit;
                }

                // Override unit price if variant has custom price
                if (!empty($selectedVariant['price']) && (float)$selectedVariant['price'] > 0) {
                    $unitPrice = (float)$selectedVariant['price'];
                }

                $variantInfo = $selectedVariant['variant_type'] . ': ' . $selectedVariant['variant_value'];
                $availableStock = (int)$selectedVariant['stock'];
            } else {
                // Product without variants
                $variantId = null;
                $variantInfo = null;

                if ($availableStock < 1) {
                    echo json_encode(['success' => false, 'message' => 'This jewellery creation is currently sold out.']);
                    exit;
                }
            }

            // Check if product/variant already in cart
            if ($variantId) {
                $itemStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND variant_id = ? LIMIT 1");
                $itemStmt->execute([$cartId, $productId, $variantId]);
            } else {
                $itemStmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id IS NULL OR variant_id = 0) LIMIT 1");
                $itemStmt->execute([$cartId, $productId]);
            }
            $existingItem = $itemStmt->fetch();

            $newTotalQty = $quantity + ($existingItem ? (int)$existingItem['quantity'] : 0);

            if ($newTotalQty > $availableStock) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Maximum available quantity (' . $availableStock . ') reached for this piece.',
                    'max_reached' => true
                ]);
                exit;
            }

            if ($existingItem) {
                $upd = $db->prepare("UPDATE cart_items SET quantity = ?, price = ?, variant_info = ? WHERE id = ?");
                $upd->execute([$newTotalQty, $unitPrice, $variantInfo, $existingItem['id']]);
            } else {
                $ins = $db->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, variant_info, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$cartId, $productId, $variantId, $variantInfo, $quantity, $unitPrice]);
            }

            $successMsg = 'Added ' . $product['name'] . ($variantInfo ? " ({$variantInfo})" : '') . ' to your shopping bag.';
            echo json_encode([
                'success' => true,
                'message' => $successMsg,
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

            // Verify stock limit against variant or base product
            $chk = $db->prepare("SELECT ci.id, ci.product_id, ci.variant_id, p.stock AS prod_stock, p.name, pv.stock AS variant_stock 
                                 FROM cart_items ci 
                                 JOIN products p ON ci.product_id = p.id 
                                 LEFT JOIN product_variants pv ON ci.variant_id = pv.id 
                                 WHERE ci.id = ? AND ci.cart_id = ?");
            $chk->execute([$itemId, $cartId]);
            $item = $chk->fetch();

            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found in bag.']);
                exit;
            }

            $effectiveStock = ($item['variant_id'] && $item['variant_stock'] !== null) ? (int)$item['variant_stock'] : (int)$item['prod_stock'];

            if ($quantity > $effectiveStock) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Maximum available quantity (' . $effectiveStock . ') reached for this piece.',
                    'max_reached' => true
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
