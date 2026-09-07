<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once dirname(__FILE__) . '/api/config.php';
startSecureSession();

$user = getLoggedInUser();
if ($user) { header('Location: /bazical/calculator.php'); exit; }

$error = '';
$msg = $_GET['msg'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? COLLATE NOCASE");
        $stmt->execute([$username]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u && password_verify($password, $u['password_hash'])) {
            if (!$u['is_active']) { $error = 'account_inactive'; }
            else {
                $today = date('Y-m-d');
                if ($u['access_start'] && $today < $u['access_start']) { $error = 'access_not_started'; }
                elseif ($u['access_end'] && $today > $u['access_end']) { $error = 'access_expired'; }
                else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']      = $u['id'];
                    $_SESSION['username']     = $u['username'];
                    $_SESSION['role']         = $u['role'];
                    $_SESSION['logged_in_at'] = time();
                    $db->prepare("UPDATE users SET last_login=CURRENT_TIMESTAMP, login_count=login_count+1 WHERE id=?")->execute([$u['id']]);
                    logActivity($u['id'], 'login', 'Login dari ' . getClientIP());
                    header('Location: /bazical/calculator.php'); exit;
                }
            }
        } else { $error = 'invalid_credentials'; }
    } else { $error = 'empty_fields'; }
}

$msgs = [
    'session_expired' => ['id'=>'Sesi Anda telah berakhir. Silakan login kembali.','en'=>'Your session has expired. Please login again.'],
    'logged_out'      => ['id'=>'Anda telah berhasil logout.','en'=>'You have been logged out successfully.'],
];
$errors = [
    'invalid_credentials' => ['id'=>'Username atau password salah.','en'=>'Invalid username or password.'],
    'account_inactive'    => ['id'=>'Akun Anda tidak aktif. Hubungi administrator.','en'=>'Account inactive. Contact administrator.'],
    'access_expired'      => ['id'=>'Masa akses Anda telah berakhir. Hubungi administrator.','en'=>'Access expired. Contact administrator.'],
    'access_not_started'  => ['id'=>'Akses Anda belum dimulai.','en'=>'Access not started yet.'],
    'empty_fields'        => ['id'=>'Username dan password harus diisi.','en'=>'Username and password are required.'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login — BaZi Calculator | Destiny Reading</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
html{min-height:100%}
body{font-family:'Segoe UI',Arial,sans-serif;background-color:#f0e6d2;background-image:url('/bazical/assets/bg_full.jpg');background-size:cover;background-position:top center;background-repeat:no-repeat;background-attachment:fixed;min-height:100vh;display:flex;align-items:flex-end;justify-content:center;padding:20px 20px 50px}
@supports (background-image: url('image.webp')){
  body{background-image:url('/bazical/assets/bg_full.webp')}
}
/* Landscape orientation: use wider-cropped background so logo+title stay fully visible */
@media (orientation: landscape){
  body{background-image:url('/bazical/assets/bg_landscape.jpg');background-position:top center;align-items:center}
  @supports (background-image: url('image.webp')){
    body{background-image:url('/bazical/assets/bg_landscape.webp')}
  }
}
@media (max-width:600px) and (orientation: portrait){
  body{background-attachment:scroll;align-items:center;padding-top:64vw}
}
@media (orientation: landscape) and (max-height:500px){
  body{align-items:center;padding:20px}
}
.wrap{width:100%;max-width:420px}
.box{background:#fffdf7;border:3px solid #5a2a0a;border-radius:12px;padding:32px;box-shadow:0 12px 40px rgba(0,0,0,.35)}
.title{font-size:1.3em;font-weight:700;color:#8b2500;text-align:center;margin-bottom:6px}
.subtitle{font-size:.8em;color:#888;text-align:center;margin-bottom:20px}
.lang-wrap{display:flex;justify-content:center;gap:8px;margin-bottom:18px}
.lang-btn{padding:4px 14px;border:1px solid #c8a050;border-radius:20px;background:#fff;color:#8b4513;font-size:.78em;cursor:pointer;transition:.2s}
.lang-btn.active{background:#8b2500;color:#fff;border-color:#8b2500}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:.8em;font-weight:700;color:#8b4513;margin-bottom:5px}
.fg input{width:100%;padding:10px 14px;border:1.5px solid #c8a050;border-radius:7px;font-size:.95em;background:#fffef5;outline:none;transition:.2s}
.fg input:focus{border-color:#8b2500;box-shadow:0 0 0 3px rgba(139,37,0,.1)}
.btn{width:100%;padding:12px;background:linear-gradient(135deg,#8b2500,#c0392b);color:#fff;border:none;border-radius:7px;font-size:1em;font-weight:700;cursor:pointer;margin-top:4px;transition:.2s}
.btn:hover{background:linear-gradient(135deg,#a83200,#e74c3c);transform:translateY(-1px)}
.msg{padding:10px 14px;border-radius:6px;font-size:.82em;margin-bottom:16px;text-align:center}
.msg-info{background:#d6eaf8;border:1px solid #aed6f1;color:#1a5276}
.msg-err{background:#fadbd8;border:1px solid #f1948a;color:#922b21}
.msg-ok{background:#d5f5e3;border:1px solid #a9dfbf;color:#1e8449}
.foot{text-align:center;font-size:.72em;color:#c8a96e;margin-top:16px}
/* Hide English by default — JS will show correct language */
.en{display:none}
/* ── ANNOUNCEMENT TICKER ─────────────────────────────────────────────── */
.ticker-wrap{width:100%;background:linear-gradient(90deg,#5c0f0f,#8b1a1a,#5c0f0f);border-radius:8px 8px 0 0;overflow:hidden;display:none;margin-bottom:0}
.ticker-wrap.has-msg{display:block}
.ticker-inner{display:flex;align-items:center;height:32px}
.ticker-label{flex-shrink:0;background:#c8a050;color:#1a0800;font-size:.68em;font-weight:700;letter-spacing:2px;padding:0 12px;height:100%;display:flex;align-items:center;text-transform:uppercase;white-space:nowrap}
.ticker-track{overflow:hidden;flex:1;height:100%;position:relative}
.ticker-content{display:inline-flex;align-items:center;height:100%;white-space:nowrap;animation:tickerScroll 30s linear infinite}
.ticker-content:hover{animation-play-state:paused}
.ticker-text{color:#f5dfa0;font-size:.78em;padding:0 48px}
.ticker-sep{color:#c8a050;opacity:.7;font-size:.9em}
@keyframes tickerScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
</style>
</head>
<body>
<div class="wrap">
  <!-- Announcement ticker — muncul di atas box jika ada pengumuman aktif -->
  <div class="ticker-wrap" id="tickerWrap">
    <div class="ticker-inner">
      <div class="ticker-label">📢 Info</div>
      <div class="ticker-track">
        <div class="ticker-content" id="tickerContent"></div>
      </div>
    </div>
  </div>
  <div class="box" id="loginBox">
    <div class="title">Login / Masuk</div>
    <div class="subtitle">
      <span class="id">Masukkan kredensial Anda untuk mengakses kalkulator</span>
      <span class="en" style="display:none">Enter your credentials to access the calculator</span>
    </div>
    <div class="lang-wrap">
      <button class="lang-btn active" onclick="setLang('id')">🇮🇩 Indonesia</button>
      <button class="lang-btn" onclick="setLang('en')">🇬🇧 English</button>
    </div>

    <?php if ($msg && isset($msgs[$msg])): ?>
    <div class="msg <?= $msg==='logged_out'?'msg-ok':'msg-info' ?>">
      <span class="id"><?= htmlspecialchars($msgs[$msg]['id']) ?></span>
      <span class="en" style="display:none"><?= htmlspecialchars($msgs[$msg]['en']) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error && isset($errors[$error])): ?>
    <div class="msg msg-err">
      <span class="id"><?= htmlspecialchars($errors[$error]['id']) ?></span>
      <span class="en" style="display:none"><?= htmlspecialchars($errors[$error]['en']) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="fg">
        <label>👤 Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Username" required autofocus>
      </div>
      <div class="fg">
        <label>🔑 <span class="id">Kata Sandi</span><span class="en" style="display:none">Password</span></label>
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <button type="submit" class="btn">
        <span class="id">Masuk →</span>
        <span class="en" style="display:none">Login →</span>
      </button>
    </form>
    <div class="foot">
      <span class="id">Butuh akses? Hubungi admin@destinyreading.id.</span>
      <span class="en" style="display:none">Need access? Contact admin@destinyreading.id.</span>
    </div>
  </div>
</div>
<script>
function setLang(l){
  document.querySelectorAll('.id').forEach(e=>e.style.display=l==='id'?'':'none');
  document.querySelectorAll('.en').forEach(e=>e.style.display=l==='en'?'':'none');
  document.querySelectorAll('.lang-btn').forEach((b,i)=>b.classList.toggle('active',[true,false][l==='id'?i:1-i]));
  localStorage.setItem('bl',l);
}

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

async function loadTicker(){
  try{
    const res=await fetch('/bazical/api/public_announcements.php');
    const data=await res.json();
    if(data.ok && data.announcements && data.announcements.length>0){
      const html=data.announcements.map(a=>
        `<span class="ticker-text">${escHtml(a.message)}</span><span class="ticker-sep"> ✦ </span>`
      ).join('');
      document.getElementById('tickerContent').innerHTML=html+html;
      document.getElementById('tickerWrap').classList.add('has-msg');
      // Round top corners of box since ticker is now attached above it
      document.getElementById('loginBox').style.borderRadius='0 0 12px 12px';
      document.getElementById('loginBox').style.borderTop='none';
    }
  }catch(e){}
}

window.onload=()=>{
  setLang(localStorage.getItem('bl')||'id');
  loadTicker();
};
</script>
</body>
</html>