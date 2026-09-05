<?php
/**
 * YoursSpeciallyJewellery - Admin Logout
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logoutAdmin();
header("Location: " . BASE_URL . "admin/login.php");
exit;
