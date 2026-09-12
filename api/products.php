<?php
/**
 * YoursSpeciallyJewellery - Products API
 * Secure parameterized query interface
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$action = $_GET['action'] ?? 'list';

try {
    $db = getDBConnection();

    if ($action === 'detail') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.status = 1 LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if ($product) {
            $product['in_stock'] = (int)$product['stock'] > 0;
            unset($product['stock']);
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
        }
        exit;
    }

    if ($action === 'search') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'results' => []]);
            exit;
        }

        $term = '%' . $q . '%';
        $stmt = $db->prepare("SELECT id, name, slug, price, sale_price, main_image, sku, (stock > 0) AS in_stock FROM products WHERE (name LIKE ? OR sku LIKE ?) AND status = 1 ORDER BY id DESC LIMIT 8");
        $stmt->execute([$term, $term]);
        $results = $stmt->fetchAll();

        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    // Default: List active products
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
    $stmt = $db->prepare("SELECT id, name, slug, price, sale_price, main_image, sku, (stock > 0) AS in_stock FROM products WHERE status = 1 ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    echo json_encode(['success' => true, 'products' => $products]);

} catch (Exception $e) {
    error_log("Products API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error retrieving products.']);
}
