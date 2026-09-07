<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
$base = dirname(__FILE__);
require_once $base . '/config.php';

$user = getLoggedInUser();
if (!$user) jsonResponse(['error' => 'Unauthorized'], 401);

$db     = getDB();
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? 'get');

switch ($action) {

    // ── GET active announcements (all users) ─────────────────────────────────
    case 'get':
        $stmt = $db->prepare("SELECT id, message FROM announcements WHERE is_active=1 ORDER BY updated_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['ok'=>true, 'announcements'=>$rows]);

    // ── LIST all (admin only) ─────────────────────────────────────────────────
    case 'list':
        if ($user['role'] !== 'admin') jsonResponse(['error'=>'Forbidden'], 403);
        $stmt = $db->prepare("SELECT * FROM announcements ORDER BY updated_at DESC");
        $stmt->execute();
        jsonResponse(['ok'=>true, 'announcements'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);

    // ── SAVE (admin only) ─────────────────────────────────────────────────────
    case 'save':
        if ($user['role'] !== 'admin') jsonResponse(['error'=>'Forbidden'], 403);
        $id      = (int)($input['id'] ?? 0);
        $message = trim($input['message'] ?? '');
        $active  = (int)($input['is_active'] ?? 1);
        if (!$message) jsonResponse(['error'=>'Pesan tidak boleh kosong'], 400);

        if ($id) {
            $db->prepare("UPDATE announcements SET message=?,is_active=?,updated_at=CURRENT_TIMESTAMP WHERE id=?")
               ->execute([$message, $active, $id]);
            jsonResponse(['ok'=>true, 'message'=>'Pengumuman diperbarui']);
        } else {
            $db->prepare("INSERT INTO announcements (message,is_active) VALUES (?,?)")
               ->execute([$message, $active]);
            $newId = $db->lastInsertId();
            jsonResponse(['ok'=>true, 'id'=>$newId, 'message'=>'Pengumuman disimpan']);
        }

    // ── DELETE (admin only) ───────────────────────────────────────────────────
    case 'delete':
        if ($user['role'] !== 'admin') jsonResponse(['error'=>'Forbidden'], 403);
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error'=>'ID tidak valid'], 400);
        $db->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
        jsonResponse(['ok'=>true, 'message'=>'Pengumuman dihapus']);

    // ── TOGGLE active (admin only) ────────────────────────────────────────────
    case 'toggle':
        if ($user['role'] !== 'admin') jsonResponse(['error'=>'Forbidden'], 403);
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE announcements SET is_active=CASE WHEN is_active=1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?")
           ->execute([$id]);
        jsonResponse(['ok'=>true]);

    default:
        jsonResponse(['error'=>'Action tidak dikenal'], 400);
}