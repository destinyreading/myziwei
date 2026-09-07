<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Public endpoint - no login required
// Only returns active announcements (read-only, no write access)
$base = dirname(__FILE__);
require_once $base . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60'); // cache 60 seconds

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT id, message FROM announcements WHERE is_active=1 ORDER BY updated_at DESC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok'=>true, 'announcements'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false, 'announcements'=>[]]);
}