<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
$base = dirname(__FILE__);
require_once $base . '/api/config.php';
$admin = requireAdmin();
$db = getDB();

// ── HANDLE ACTIONS ────────────────────────────────────────────────────────────
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD USER
    if ($action === 'add_user') {
        $un   = trim($_POST['username'] ?? '');
        $pw   = $_POST['password'] ?? '';
        $fn   = trim($_POST['full_name'] ?? '');
        $em   = trim($_POST['email'] ?? '');
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $as   = $_POST['access_start'] ?: null;
        $ae   = $_POST['access_end'] ?: null;
        $note = trim($_POST['notes'] ?? '');

        if (!$un || !$pw) {
            $error = 'Username dan password wajib diisi.';
        } elseif (strlen($pw) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            try {
                $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare("INSERT INTO users (username,password_hash,full_name,email,role,is_active,access_start,access_end,notes) VALUES (?,?,?,?,?,1,?,?,?)");
                $stmt->execute([$un,$hash,$fn,$em,$role,$as,$ae,$note]);
                logActivity($admin['id'], 'add_user', "Tambah user: $un");
                $success = "User <strong>$un</strong> berhasil dibuat.";
            } catch (Exception $e) {
                $error = 'Username sudah ada atau terjadi kesalahan.';
            }
        }
    }

    // TOGGLE ACTIVE
    if ($action === 'toggle_active') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== (int)$admin['id']) {
            $stmt = $db->prepare("UPDATE users SET is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END WHERE id=?");
            $stmt->execute([$uid]);
            logActivity($admin['id'], 'toggle_active', "Toggle aktif user ID: $uid");
            $success = 'Status user berhasil diubah.';
        }
    }

    // DELETE USER
    if ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid && $uid !== (int)$admin['id']) {
            $stmt = $db->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$uid]);
            logActivity($admin['id'], 'delete_user', "Hapus user ID: $uid");
            $success = 'User berhasil dihapus.';
        } else {
            $error = 'Tidak dapat menghapus akun admin sendiri.';
        }
    }

    // EDIT USER
    if ($action === 'edit_user') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        $fn   = trim($_POST['full_name'] ?? '');
        $em   = trim($_POST['email'] ?? '');
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $as   = $_POST['access_start'] ?: null;
        $ae   = $_POST['access_end'] ?: null;
        $note = trim($_POST['notes'] ?? '');
        $pw   = $_POST['new_password'] ?? '';
        $act  = (int)($_POST['is_active'] ?? 1);

        if ($uid) {
            if ($pw && strlen($pw) < 6) {
                $error = 'Password baru minimal 6 karakter.';
            } else {
                $params = [$fn,$em,$role,$act,$as,$ae,$note,$uid];
                $sql = "UPDATE users SET full_name=?,email=?,role=?,is_active=?,access_start=?,access_end=?,notes=?";
                if ($pw) { $sql .= ",password_hash=?"; array_splice($params, 7, 0, [password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12])]); }
                $sql .= " WHERE id=?";
                $db->prepare($sql)->execute($params);
                logActivity($admin['id'], 'edit_user', "Edit user ID: $uid");
                $success = 'Data user berhasil diperbarui.';
            }
        }
    }

    // RESET PASSWORD
    if ($action === 'reset_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $pw  = $_POST['new_password'] ?? '';
        if ($uid && strlen($pw) >= 6) {
            $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash,$uid]);
            logActivity($admin['id'], 'reset_password', "Reset password user ID: $uid");
            $success = 'Password berhasil direset.';
        } else {
            $error = 'Password minimal 6 karakter.';
        }
    }
}

// ── FETCH USERS ──────────────────────────────────────────────────────────────
$search   = trim($_GET['q'] ?? '');
$filterSt = $_GET['status'] ?? 'all';
$sortBy   = $_GET['sort'] ?? 'created_at';
$sortDir  = $_GET['dir'] ?? 'DESC';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;

$where = ['1=1'];
$params = [];
if ($search) { $where[] = "(username LIKE ? OR full_name LIKE ? OR email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($filterSt === 'active')   { $where[] = "is_active=1"; }
if ($filterSt === 'inactive') { $where[] = "is_active=0"; }
if ($filterSt === 'expired')  { $where[] = "access_end IS NOT NULL AND access_end < date('now')"; }

$allowed = ['id','username','full_name','role','is_active','access_end','created_at','last_login','login_count'];
if (!in_array($sortBy, $allowed)) $sortBy = 'created_at';
$sortDir = $sortDir === 'ASC' ? 'ASC' : 'DESC';

$whereSql = implode(' AND ', $where);
$total = $db->prepare("SELECT COUNT(*) FROM users WHERE $whereSql"); $total->execute($params); $total = $total->fetchColumn();
$offset = ($page-1)*$perPage;
$stmt = $db->prepare("SELECT * FROM users WHERE $whereSql ORDER BY $sortBy $sortDir LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = ceil($total / $perPage);

// Stats
$stats = $db->query("SELECT
    COUNT(*) as total,
    SUM(is_active) as active,
    SUM(CASE WHEN access_end < date('now') AND access_end IS NOT NULL THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN last_login >= date('now','-7 days') THEN 1 ELSE 0 END) as recent
    FROM users WHERE role='user'")->fetch(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Panel — BaZi Calculator</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:#f0ece0;color:#222;font-size:13px}
/* NAVBAR */
.navbar{background:linear-gradient(135deg,#8b2500,#c0392b);color:#fff;padding:0 20px;display:flex;align-items:center;justify-content:space-between;height:52px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:1.1em;font-weight:700;letter-spacing:.5px}
.nav-brand span{color:#f5c842}
.nav-right{display:flex;align-items:center;gap:16px;font-size:.82em}
.nav-right a{color:#ffd;text-decoration:none;padding:6px 12px;border-radius:4px;transition:.2s}
.nav-right a:hover{background:rgba(255,255,255,.15)}
.nav-user{color:#f5c842;font-weight:700}
/* LAYOUT */
.container{max-width:1300px;margin:0 auto;padding:16px}
/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.stat-card{background:#fff;border-radius:8px;padding:14px 16px;border-left:4px solid #c8a050;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.stat-card.blue{border-color:#2980b9}.stat-card.green{border-color:#27ae60}.stat-card.red{border-color:#c0392b}.stat-card.orange{border-color:#e67e22}
.stat-num{font-size:2em;font-weight:700;line-height:1}
.stat-lbl{font-size:.75em;color:#888;margin-top:3px}
/* CARDS */
.card{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);margin-bottom:16px;overflow:hidden}
.card-hd{background:#e8d890;padding:10px 16px;font-weight:700;font-size:.9em;color:#5d4037;border-bottom:1px solid #d4b896;display:flex;align-items:center;justify-content:space-between}
.card-body{padding:16px}
/* FORM */
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.fg{display:flex;flex-direction:column;gap:4px}
.fg label{font-size:.75em;font-weight:700;color:#8b4513}
.fg input,.fg select,.fg textarea{padding:7px 10px;border:1px solid #c8a050;border-radius:5px;font-size:.88em;background:#fffef5;width:100%}
.fg input:focus,.fg select:focus{outline:none;border-color:#8b2500}
.fg small{font-size:.7em;color:#999}
.btn{padding:7px 16px;border:none;border-radius:5px;font-size:.85em;font-weight:700;cursor:pointer;transition:.15s}
.btn-primary{background:#8b2500;color:#fff}.btn-primary:hover{background:#c0392b}
.btn-sm{padding:4px 10px;font-size:.78em}
.btn-success{background:#27ae60;color:#fff}.btn-success:hover{background:#2ecc71}
.btn-danger{background:#c0392b;color:#fff}.btn-danger:hover{background:#e74c3c}
.btn-warning{background:#e67e22;color:#fff}.btn-warning:hover{background:#f39c12}
.btn-secondary{background:#7f8c8d;color:#fff}.btn-secondary:hover{background:#95a5a6}
/* ALERT */
.alert{padding:10px 14px;border-radius:6px;font-size:.85em;margin-bottom:14px}
.alert-success{background:#d5f5e3;border:1px solid #a9dfbf;color:#1e8449}
.alert-error{background:#fadbd8;border:1px solid #f1948a;color:#922b21}
/* TABLE */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 10px;text-align:left;border-bottom:1px solid #eee}
th{background:#f5e6c8;font-size:.75em;color:#8b4513;font-weight:700;white-space:nowrap}
tr:hover td{background:#fffde0}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.72em;font-weight:700}
.badge-active{background:#d5f5e3;color:#1e8449}
.badge-inactive{background:#fadbd8;color:#922b21}
.badge-expired{background:#fdebd0;color:#d35400}
.badge-admin{background:#d6eaf8;color:#1a5276}
.badge-user{background:#f0f3f4;color:#555}
/* SEARCH BAR */
.search-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
.search-bar input{padding:7px 12px;border:1px solid #c8a050;border-radius:5px;font-size:.88em;min-width:200px}
.search-bar select{padding:7px 10px;border:1px solid #c8a050;border-radius:5px;font-size:.85em;background:#fff}
/* PAGINATION */
.pagination{display:flex;gap:4px;justify-content:center;margin-top:12px}
.pagination a,.pagination span{padding:5px 10px;border:1px solid #c8a050;border-radius:4px;font-size:.8em;color:#8b4513;text-decoration:none}
.pagination a:hover{background:#f5e6c8}
.pagination .current{background:#8b2500;color:#fff;border-color:#8b2500}
/* MODAL */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:10px;padding:24px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.3)}
.modal-hd{font-size:1em;font-weight:700;color:#8b2500;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #eee}
.modal-footer{display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:12px;border-top:1px solid #eee}
@media(max-width:600px){.stats-row{grid-template-columns:repeat(2,1fr)}.form-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="navbar">
  <div class="nav-brand">🔮 <span>Destiny Reading</span> — Admin Panel</div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($admin['username']) ?></span>
    <a href="/bazical/calculator.php">🔮 Kalkulator</a>
    <a href="/bazical/logout.php">🚪 Logout</a>
  </div>
</div>

<div class="container">

<?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>

<!-- STATS -->
<div class="stats-row">
  <div class="stat-card blue"><div class="stat-num"><?= $stats['total'] ?></div><div class="stat-lbl">Total User</div></div>
  <div class="stat-card green"><div class="stat-num"><?= $stats['active'] ?></div><div class="stat-lbl">User Aktif</div></div>
  <div class="stat-card red"><div class="stat-num"><?= $stats['expired'] ?></div><div class="stat-lbl">Akses Expired</div></div>
  <div class="stat-card orange"><div class="stat-num"><?= $stats['recent'] ?></div><div class="stat-lbl">Login 7 Hari Terakhir</div></div>
</div>

<!-- ADD USER FORM -->
<div class="card">
  <div class="card-hd">
    ➕ Tambah User Baru
    <button class="btn btn-sm btn-secondary" onclick="toggleSection('add_form')">Tampilkan/Sembunyikan</button>
  </div>
  <div class="card-body" id="add_form">
    <form method="POST">
      <input type="hidden" name="action" value="add_user">
      <div class="form-grid">
        <div class="fg"><label>Username *</label><input type="text" name="username" required placeholder="contoh: john_doe"></div>
        <div class="fg"><label>Password *</label><input type="password" name="password" required placeholder="Min. 6 karakter"><small>Min. 6 karakter</small></div>
        <div class="fg"><label>Nama Lengkap</label><input type="text" name="full_name" placeholder="Nama lengkap user"></div>
        <div class="fg"><label>Email</label><input type="email" name="email" placeholder="email@example.com"></div>
        <div class="fg"><label>Role</label>
          <select name="role"><option value="user">User</option><option value="admin">Admin</option></select></div>
        <div class="fg"><label>Akses Mulai</label><input type="date" name="access_start" value="<?= $today ?>"></div>
        <div class="fg"><label>Akses Berakhir</label><input type="date" name="access_end" value="<?= date('Y-m-d', strtotime('+1 year')) ?>"><small>Kosongkan = tidak ada batas</small></div>
        <div class="fg"><label>Catatan</label><input type="text" name="notes" placeholder="Catatan opsional"></div>
      </div>
      <div style="margin-top:12px"><button type="submit" class="btn btn-primary">➕ Tambah User</button></div>
    </form>
  </div>
</div>

<!-- USER LIST -->
<div class="card">
  <div class="card-hd">👥 Daftar User (<?= $total ?> total)</div>
  <div class="card-body">
    <div class="search-bar">
      <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Cari username, nama, email...">
        <select name="status">
          <option value="all" <?= $filterSt==='all'?'selected':'' ?>>Semua Status</option>
          <option value="active" <?= $filterSt==='active'?'selected':'' ?>>Aktif</option>
          <option value="inactive" <?= $filterSt==='inactive'?'selected':'' ?>>Nonaktif</option>
          <option value="expired" <?= $filterSt==='expired'?'selected':'' ?>>Expired</option>
        </select>
        <select name="sort">
          <option value="created_at" <?= $sortBy==='created_at'?'selected':'' ?>>Tanggal Buat</option>
          <option value="username" <?= $sortBy==='username'?'selected':'' ?>>Username</option>
          <option value="last_login" <?= $sortBy==='last_login'?'selected':'' ?>>Last Login</option>
          <option value="access_end" <?= $sortBy==='access_end'?'selected':'' ?>>Akses Berakhir</option>
          <option value="login_count" <?= $sortBy==='login_count'?'selected':'' ?>>Login Count</option>
        </select>
        <select name="dir">
          <option value="DESC" <?= $sortDir==='DESC'?'selected':'' ?>>↓ Terbaru</option>
          <option value="ASC" <?= $sortDir==='ASC'?'selected':'' ?>>↑ Terlama</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        <a href="/bazical/admin.php" class="btn btn-secondary btn-sm">Reset</a>
      </form>
    </div>

    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Username</th><th>Nama</th><th>Role</th><th>Status</th>
            <th>Akses Mulai</th><th>Akses Berakhir</th><th>Last Login</th><th>Login</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <?php
            $isExpired = $u['access_end'] && $today > $u['access_end'];
            $notStarted = $u['access_start'] && $today < $u['access_start'];
            $statusBadge = !$u['is_active'] ? '<span class="badge badge-inactive">Nonaktif</span>'
                : ($isExpired ? '<span class="badge badge-expired">Expired</span>'
                : ($notStarted ? '<span class="badge badge-expired">Belum Mulai</span>'
                : '<span class="badge badge-active">Aktif</span>'));
          ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
            <td><?= $statusBadge ?></td>
            <td><?= $u['access_start'] ?: '—' ?></td>
            <td style="<?= $isExpired?'color:#c0392b;font-weight:700':'' ?>"><?= $u['access_end'] ?: '∞' ?></td>
            <td><?= $u['last_login'] ? date('d/m/y H:i', strtotime($u['last_login'])) : '—' ?></td>
            <td><?= $u['login_count'] ?></td>
            <td>
              <button class="btn btn-sm btn-warning" onclick="openEdit(<?= htmlspecialchars(json_encode($u)) ?>)">✏️</button>
              <?php if ($u['id'] !== (int)$admin['id']): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Toggle status user <?= htmlspecialchars($u['username']) ?>?')">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm <?= $u['is_active']?'btn-secondary':'btn-success' ?>" type="submit">
                  <?= $u['is_active']?'⏸':'▶' ?>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('HAPUS user <?= htmlspecialchars($u['username']) ?>? Tidak dapat dibatalkan!')">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">🗑️</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
          <tr><td colspan="10" style="text-align:center;color:#888;padding:20px">Tidak ada user ditemukan.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php
        $base = "/bazical/admin.php?q=".urlencode($search)."&status=$filterSt&sort=$sortBy&dir=$sortDir";
        if ($page > 1) echo "<a href='$base&page=".($page-1)."'>‹ Prev</a>";
        for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++) {
            if ($p === $page) echo "<span class='current'>$p</span>";
            else echo "<a href='$base&page=$p'>$p</a>";
        }
        if ($page < $totalPages) echo "<a href='$base&page=".($page+1)."'>Next ›</a>";
      ?>
    </div>
    <?php endif; ?>
  </div>
</div>

</div><!-- /container -->

<!-- EDIT MODAL -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-hd">✏️ Edit User — <span id="edit_username_lbl"></span></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_user">
      <input type="hidden" name="user_id" id="edit_user_id">
      <div class="form-grid">
        <div class="fg"><label>Nama Lengkap</label><input type="text" name="full_name" id="edit_full_name"></div>
        <div class="fg"><label>Email</label><input type="email" name="email" id="edit_email"></div>
        <div class="fg"><label>Role</label>
          <select name="role" id="edit_role">
            <option value="user">User</option><option value="admin">Admin</option>
          </select></div>
        <div class="fg"><label>Status</label>
          <select name="is_active" id="edit_is_active">
            <option value="1">Aktif</option><option value="0">Nonaktif</option>
          </select></div>
        <div class="fg"><label>Akses Mulai</label><input type="date" name="access_start" id="edit_access_start"></div>
        <div class="fg"><label>Akses Berakhir</label><input type="date" name="access_end" id="edit_access_end"><small>Kosongkan = tidak ada batas</small></div>
        <div class="fg" style="grid-column:1/-1"><label>Password Baru (kosongkan jika tidak diubah)</label>
          <input type="password" name="new_password" placeholder="Min. 6 karakter, kosongkan jika tidak diubah"></div>
        <div class="fg" style="grid-column:1/-1"><label>Catatan</label>
          <input type="text" name="notes" id="edit_notes"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeEdit()">Batal</button>
        <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSection(id){ const el=document.getElementById(id); el.style.display=el.style.display==='none'?'':'none'; }
// Hide add form by default
document.getElementById('add_form').style.display='none';

function openEdit(u){
  document.getElementById('edit_user_id').value=u.id;
  document.getElementById('edit_username_lbl').textContent=u.username;
  document.getElementById('edit_full_name').value=u.full_name||'';
  document.getElementById('edit_email').value=u.email||'';
  document.getElementById('edit_role').value=u.role;
  document.getElementById('edit_is_active').value=u.is_active;
  document.getElementById('edit_access_start').value=u.access_start||'';
  document.getElementById('edit_access_end').value=u.access_end||'';
  document.getElementById('edit_notes').value=u.notes||'';
  document.getElementById('editModal').classList.add('show');
}
function closeEdit(){ document.getElementById('editModal').classList.remove('show'); }
document.getElementById('editModal').addEventListener('click',function(e){ if(e.target===this)closeEdit(); });
</script>
</body>
</html>
