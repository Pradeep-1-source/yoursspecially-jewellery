<?php
/**
 * YoursSpeciallyJewellery - Auth API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = loginCustomer($email, $password);
        echo json_encode($res);
        exit;

    case 'logout':
        logoutCustomer();
        echo json_encode(['success' => true]);
        exit;

    case 'status':
        echo json_encode([
            'is_logged_in' => isCustomerLoggedIn(),
            'customer' => currentCustomer()
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid auth action.']);
        exit;
}
