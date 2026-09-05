<?php
/**
 * YoursSpeciallyJewellery - Banners API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDBConnection();
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT id, title, subtitle, image, button_text, button_link 
                          FROM banners 
                          WHERE status = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) 
                          ORDER BY sort_order ASC, id DESC");
    $stmt->execute([$today, $today]);
    $banners = $stmt->fetchAll();

    echo json_encode(['success' => true, 'banners' => $banners]);
} catch (Exception $e) {
    error_log("Banners API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error retrieving banners.']);
}
