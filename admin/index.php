<?php
/**
 * YoursSpeciallyJewellery - Admin Entry Point
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
} else {
    header("Location: " . BASE_URL . "admin/login.php");
}
exit;
