<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
$base = dirname(__FILE__);
require_once $base . '/config.php';

$user = getLoggedInUser();
if (!$user) jsonResponse(['error' => 'Unauthorized'], 401);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');
$db = getDB();

// ── Also ensure charts table exists (for existing installs) ──────────────────
initChartsTable($db);

$uid = (int)$user['id'];
$MAX_CHARTS = 1000;

switch ($action) {

    // ── LIST ──────────────────────────────────────────────────────────────────
    case 'list':
        $stmt = $db->prepare("SELECT * FROM charts WHERE user_id=? ORDER BY updated_at DESC LIMIT ?");
        $stmt->execute([$uid, $MAX_CHARTS]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['ok'=>true, 'charts'=>$rows, 'total'=>count($rows)]);

    // ── SAVE ──────────────────────────────────────────────────────────────────
    case 'save':
        $label          = trim($input['label'] ?? '');
        $personName     = trim($input['person_name'] ?? '');
        $birthDate      = trim($input['birth_date'] ?? '');
        $unknownHour    = !empty($input['unknown_hour']);
        $birthHour      = $unknownHour ? null : (int)($input['birth_hour'] ?? 0);
        $birthMin       = $unknownHour ? null : (int)($input['birth_min'] ?? 0);
        $sex            = ($input['sex'] ?? 'M') === 'F' ? 'F' : 'M';
        $ziMode         = ($input['zi_mode'] ?? 'late') === 'early' ? 'early' : 'late';
        $forceOverwrite = (bool)($input['force_overwrite'] ?? false);

        if (!$label || !$birthDate) jsonResponse(['error' => 'Label dan tanggal lahir wajib diisi'], 400);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) jsonResponse(['error' => 'Format tanggal tidak valid'], 400);

        // Check duplicate label (uses idx_charts_user_label index)
        $dupCheck = $db->prepare("SELECT id FROM charts WHERE user_id=? AND label=? COLLATE NOCASE LIMIT 1");
        $dupCheck->execute([$uid, $label]);
        $existing = $dupCheck->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            if (!$forceOverwrite) {
                jsonResponse(['ok'=>false, 'duplicate'=>true, 'existing_id'=>$existing['id'],
                    'message'=>"Label \"$label\" sudah ada. Apakah ingin menimpa (overwrite)?"], 409);
            } else {
                $stmt = $db->prepare("UPDATE charts SET person_name=?,birth_date=?,birth_hour=?,birth_min=?,sex=?,zi_mode=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
                $stmt->execute([$personName,$birthDate,$birthHour,$birthMin,$sex,$ziMode,$existing['id'],$uid]);
                logActivity($uid, 'overwrite_chart', "Overwrite chart: $label");
                jsonResponse(['ok'=>true, 'id'=>$existing['id'], 'label'=>$label, 'message'=>"Chart \"$label\" berhasil ditimpa"]);
            }
        }

        // Check count limit
        $count = $db->prepare("SELECT COUNT(*) FROM charts WHERE user_id=?");
        $count->execute([$uid]);
        if ((int)$count->fetchColumn() >= $MAX_CHARTS)
            jsonResponse(['error' => "Maksimal $MAX_CHARTS chart per user"], 400);

        $stmt = $db->prepare("INSERT INTO charts (user_id,label,person_name,birth_date,birth_hour,birth_min,sex,zi_mode) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$uid,$label,$personName,$birthDate,$birthHour,$birthMin,$sex,$ziMode]);
        $newId = $db->lastInsertId();
        logActivity($uid, 'save_chart', "Simpan chart: $label");
        jsonResponse(['ok'=>true, 'id'=>$newId, 'label'=>$label, 'message'=>"Chart \"$label\" berhasil disimpan"]);

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update':
        $id          = (int)($input['id'] ?? 0);
        $label       = trim($input['label'] ?? '');
        $personName  = trim($input['person_name'] ?? '');
        $birthDate   = trim($input['birth_date'] ?? '');
        $unknownHour = !empty($input['unknown_hour']);
        $birthHour   = $unknownHour ? null : (int)($input['birth_hour'] ?? 0);
        $birthMin    = $unknownHour ? null : (int)($input['birth_min'] ?? 0);
        $sex         = ($input['sex'] ?? 'M') === 'F' ? 'F' : 'M';
        $ziMode      = ($input['zi_mode'] ?? 'late') === 'early' ? 'early' : 'late';

        if (!$id || !$label || !$birthDate) jsonResponse(['error' => 'Data tidak lengkap'], 400);

        // Verify ownership
        $own = $db->prepare("SELECT id FROM charts WHERE id=? AND user_id=?");
        $own->execute([$id, $uid]);
        if (!$own->fetch()) jsonResponse(['error' => 'Chart tidak ditemukan'], 404);

        // Check duplicate label (exclude current record)
        $dupCheck = $db->prepare("SELECT id FROM charts WHERE user_id=? AND label=? COLLATE NOCASE AND id!=?");
        $dupCheck->execute([$uid, $label, $id]);
        if ($dupCheck->fetch()) jsonResponse(['error' => "Label \"$label\" sudah digunakan chart lain. Gunakan label berbeda."], 409);

        $stmt = $db->prepare("UPDATE charts SET label=?,person_name=?,birth_date=?,birth_hour=?,birth_min=?,sex=?,zi_mode=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?");
        $stmt->execute([$label,$personName,$birthDate,$birthHour,$birthMin,$sex,$ziMode,$id,$uid]);
        logActivity($uid, 'update_chart', "Update chart ID $id: $label");
        jsonResponse(['ok'=>true, 'message'=>"Chart \"$label\" berhasil diupdate"]);

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID tidak valid'], 400);

        $own = $db->prepare("SELECT label FROM charts WHERE id=? AND user_id=?");
        $own->execute([$id, $uid]);
        $row = $own->fetch(PDO::FETCH_ASSOC);
        if (!$row) jsonResponse(['error' => 'Chart tidak ditemukan'], 404);

        $db->prepare("DELETE FROM charts WHERE id=? AND user_id=?")->execute([$id, $uid]);
        logActivity($uid, 'delete_chart', "Hapus chart ID $id: {$row['label']}");
        jsonResponse(['ok'=>true, 'message'=>"Chart \"{$row['label']}\" berhasil dihapus"]);

    // ── CHECK DUPLICATE ───────────────────────────────────────────────────────
    case 'check':
        $birthDate   = trim($input['birth_date'] ?? '');
        $unknownHour = !empty($input['unknown_hour']);
        $birthHour   = $unknownHour ? null : (int)($input['birth_hour'] ?? 0);
        $birthMin    = $unknownHour ? null : (int)($input['birth_min'] ?? 0);
        $sex         = ($input['sex'] ?? 'M') === 'F' ? 'F' : 'M';

        if ($unknownHour) {
            $stmt = $db->prepare("SELECT id,label,person_name FROM charts WHERE user_id=? AND birth_date=? AND birth_hour IS NULL AND sex=? LIMIT 1");
            $stmt->execute([$uid,$birthDate,$sex]);
        } else {
            $stmt = $db->prepare("SELECT id,label,person_name FROM charts WHERE user_id=? AND birth_date=? AND birth_hour=? AND birth_min=? AND sex=? LIMIT 1");
            $stmt->execute([$uid,$birthDate,$birthHour,$birthMin,$sex]);
        }
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        jsonResponse(['ok'=>true, 'existing'=> $existing ?: null]);

    default:
        jsonResponse(['error' => 'Action tidak dikenal'], 400);
}