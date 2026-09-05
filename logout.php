<?php
/**
 * YoursSpeciallyJewellery - Customer Logout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

logoutCustomer();
setFlash('info', 'You have been safely signed out. Thank you for visiting.');
header("Location: " . BASE_URL . "login.php");
exit;
