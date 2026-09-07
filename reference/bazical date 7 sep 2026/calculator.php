<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
$base = dirname(__FILE__);
require_once $base . '/api/config.php';
$user = requireLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>BaZi Calculator — Destiny Reading</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Microsoft YaHei','Segoe UI',Arial,sans-serif;background:#f5f0e0;color:#222;font-size:13px}
.navbar{background:linear-gradient(135deg,#8b2500,#c0392b);color:#fff;padding:0 16px;display:flex;align-items:center;justify-content:space-between;height:48px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:1em;font-weight:700;color:#f5c842}
.nav-right{display:flex;align-items:center;gap:12px;font-size:.8em}
.nav-right a{color:#ffd;text-decoration:none;padding:5px 10px;border-radius:4px;transition:.2s}
.nav-right a:hover{background:rgba(255,255,255,.15)}
.nav-user{color:#ffd;font-size:.82em}
.nav-access{font-size:.72em;color:#ffd;opacity:.8}
.wrap{max-width:1500px;margin:0 auto;padding:8px}
/* FORM */
.form-card{background:#fffde8;border:2px solid #c8a050;border-radius:6px;padding:10px 14px;margin-bottom:8px}
.form-row{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
.fg{display:flex;flex-direction:column;gap:3px}
.fg label{font-size:.72em;color:#8b4513;font-weight:700}
.fg input,.fg select{padding:5px 8px;border:1px solid #c8a050;border-radius:4px;font-size:.88em;background:#fffef5;min-width:110px}
.fg input:focus,.fg select:focus{outline:none;border-color:#8b2500}
.calc-btn{padding:8px 18px;background:#8b2500;color:#fff;border:none;border-radius:4px;font-size:.9em;font-weight:700;cursor:pointer;align-self:flex-end}
.calc-btn:hover{background:#c0392b}
.calc-btn:disabled{background:#aaa;cursor:not-allowed}
.save-btn{padding:8px 14px;background:#1a5276;color:#fff;border:none;border-radius:4px;font-size:.85em;font-weight:700;cursor:pointer;align-self:flex-end;display:none}
.save-btn:hover{background:#2980b9}
.charts-btn{padding:8px 14px;background:#1e8449;color:#fff;border:none;border-radius:4px;font-size:.85em;font-weight:700;cursor:pointer;align-self:flex-end}
.charts-btn:hover{background:#27ae60}
/* BOARD */
.board{background:#fffef0;border:2px solid #c8a050;border-radius:4px;margin-bottom:8px;overflow:hidden}
.board-hd{background:#e8d890;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #c8a050;flex-wrap:wrap;gap:8px}
.bd-left{display:flex;align-items:center;gap:10px}
.bd-qian{font-size:1.6em;font-weight:900;color:#c0392b}
.bd-info{display:flex;flex-direction:column;gap:1px}
.bd-name{font-size:1em;font-weight:700}
.bd-date{font-size:.78em;color:#666}
.bd-kw-wrap{text-align:right}
.bd-kw-lbl{font-size:.68em;color:#888;display:block;margin-bottom:1px}
.bd-kw-val{font-size:.9em;font-weight:700;letter-spacing:1px}
/* PILLAR GRID — improved sizing */
.pillars-outer{border-bottom:2px solid #c8a050}
.pillars-grid{display:grid;grid-template-columns:1fr 24px 1fr 24px 1fr 24px 1fr}
.p-col{border-right:1px solid #d4b896;text-align:center;padding:5px 4px 8px}
.p-col:last-child{border-right:none}
.p-label{background:#e8d890;font-size:.75em;color:#5d4037;font-weight:700;padding:3px 0;margin:-5px -4px 4px;border-bottom:1px solid #c8a050}
.p-ss{font-size:.9em;font-weight:700;color:#2980b9;padding:2px 0;min-height:20px}
.p-ss.main{color:#c0392b}
.p-ss-py{font-size:.7em;color:#aaa;font-style:italic;display:block;line-height:1.2}
/* BIGGER TG/DZ */
.p-tg{font-size:3.4em;font-weight:900;display:block;line-height:1;padding:3px 0 2px}
.p-tg-el{font-size:.72em;color:#888;display:block;padding:0 0 3px}
.v-badge{display:flex;flex-direction:column;align-items:center;margin:1px auto;cursor:help}
.v-badge .bs{font-size:1.1em;line-height:1;font-weight:700}
.v-badge .bl{font-size:.6em;color:#666}
.p-dz{font-size:3.4em;font-weight:900;display:block;line-height:1;padding:2px 0 2px}
.p-dz-el{font-size:.72em;color:#888;display:block;padding:0 0 2px}
/* HIDDEN STEMS — bigger */
.p-hs-block{border-top:1px dashed #e0d0a0;border-bottom:1px dashed #e0d0a0;padding:3px 0 2px;margin:2px 0;min-height:20px}
.p-hs-line{font-size:.82em;line-height:1.6;white-space:nowrap}
.p-hs-char{font-weight:700;font-size:1.05em}
.p-hs-ss{color:#e67e22;font-size:.88em;margin-left:2px}
.p-hs-py{color:#aaa;font-size:.72em;margin-left:2px;font-style:italic}
/* 12 STAGE — bigger */
.p-qi-block{margin-top:3px;border-top:1px dashed #e0d0a0;padding-top:2px}
.p-qi-row{font-size:.75em;padding:1px 0}
.p-qi-lbl{color:#888;font-size:.88em}
.p-qi-val{font-weight:700;color:#16a085}
.p-qi-py{color:#aaa;font-style:italic;font-size:.88em;margin-left:2px}
.p-qi-val.self{color:#8e44ad}
/* NAYIN — bigger */
.p-ny{font-size:.75em;color:#7f8c8d;padding:1px 0 0;display:block}
.p-ny-py{font-size:.68em;color:#aaa;font-style:italic;display:block}
.p-ny-id{font-size:.68em;color:#c8900a;display:block;padding:0 0 2px}
.kw-badge{display:inline-block;background:#fde8e8;border:1px solid #e74c3c;border-radius:3px;font-size:.65em;color:#c0392b;font-weight:700;padding:1px 4px;margin-top:2px}
/* ELEMENT COLORS */
.el-wood{color:#27ae60}.el-fire{color:#c0392b}.el-earth{color:#c8900a}.el-metal{color:#7f8c8d}.el-water{color:#2980b9}
/* GAP */
.p-gap{display:flex;flex-direction:column;justify-content:space-around;align-items:center;padding:4px 0;background:#fffef0}
.h-badge{display:flex;flex-direction:column;align-items:center;font-size:.75em;padding:2px;cursor:help;border-radius:4px;min-width:20px;text-align:center}
.h-badge .sym{font-size:1.15em;line-height:1;font-weight:700}
.h-badge .lbl{font-size:.6em;color:#666;margin-top:1px}
.int-sheng{color:#27ae60}.int-ke{color:#c0392b}.int-bi{color:#2980b9}
.int-bg-sheng{background:rgba(39,174,96,.1);border:1px solid rgba(39,174,96,.3)}
.int-bg-ke{background:rgba(192,57,43,.1);border:1px solid rgba(192,57,43,.3)}
.int-bg-bi{background:rgba(41,128,185,.1);border:1px solid rgba(41,128,185,.3)}
/* SAN YUAN */
.san-yuan{display:flex;flex-wrap:wrap;gap:16px;padding:6px 12px;background:#fffde0;border-bottom:1px solid #d4b896;font-size:.84em;align-items:center}
.sy-item{display:flex;align-items:baseline;gap:3px}
.sy-lbl{color:#8b4513;font-weight:700;margin-right:3px;white-space:nowrap;font-size:.9em}
.sy-ny{color:#7f8c8d;font-size:.85em;margin-left:3px}
.sy-ny-py{color:#aaa;font-size:.72em;font-style:italic;margin-left:2px}
/* QIYUN */
.qiyun-bar{background:#d6eaf8;padding:5px 12px;font-size:.8em;color:#1a5276;border-bottom:1px solid #aed6f1}
/* DA YUN TABLE — DY1-DY9 + 命造, bigger Liu Nian font */
.dy-outer{overflow-x:auto}
.dy-tbl{border-collapse:collapse;min-width:900px;width:100%}
.dy-tbl th{border:1px solid #c8a050;padding:3px 4px;background:#e8d890;font-size:.72em;color:#5d4037;text-align:center;white-space:nowrap;font-weight:700}
.dy-tbl td{border:1px solid #d4b896;padding:0;text-align:center;vertical-align:middle}
.row-hd{background:#e8d890!important;font-size:.72em;font-weight:700;color:#5d4037;padding:3px 5px;white-space:nowrap;min-width:40px}
.dc{padding:2px 2px;line-height:1.2}
.dc-ss{font-size:.72em;font-weight:700;color:#2980b9;display:block}
.dc-tg{font-size:1.5em;font-weight:900;display:block;line-height:1.1}
.dc-dz{font-size:1.5em;font-weight:900;display:block;line-height:1.1}
.dc-cs{font-size:.62em;color:#16a085;display:block}
.dc-cs-py{font-size:.56em;color:#aaa;font-style:italic;display:block}
.dc-ny{font-size:.6em;color:#7f8c8d;display:block}
.dc-ny-py{font-size:.56em;color:#aaa;font-style:italic;display:block}
.dc-age{font-size:.75em;color:#c0392b;font-weight:700;display:block}
.dc-yr{font-size:.7em;color:#2980b9;display:block}
.dc-date{font-size:.62em;color:#888;display:block}
.dc-stop{font-size:.62em;color:#888;display:block}
.col-active{background:#fff3cd!important}
.col-active-hd{background:#ffe082!important;color:#8b4513!important}
.col-birth{background:#fffde7}
/* BIGGER Liu Nian font */
.ln{padding:2px 3px;border-bottom:1px solid #eee8d0;font-size:.78em;display:flex;align-items:center;justify-content:center;gap:3px}
.ln:last-child{border-bottom:none}
.ln-yr{color:#888;font-size:.88em}
.ln-now{background:#fff3cd}
.ln-now .ln-tg,.ln-now .ln-dz{font-size:1.1em;font-weight:900}
/* LOADING */
.loading{text-align:center;padding:30px;color:#8b4513;font-size:1em}
.spinner{display:inline-block;width:24px;height:24px;border:3px solid #c8a050;border-top-color:#8b2500;border-radius:50%;animation:spin .8s linear infinite;margin-right:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
/* ── MODAL (Save/Charts) ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:10px;padding:24px;width:100%;max-width:500px;max-height:85vh;overflow-y:auto;box-shadow:0 8px 32px rgba(0,0,0,.3)}
.modal-hd{font-size:1em;font-weight:700;color:#8b2500;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}
.modal-close{background:none;border:none;font-size:1.3em;cursor:pointer;color:#888;padding:0}
.modal-close:hover{color:#c0392b}
.mfg{margin-bottom:12px}
.mfg label{display:block;font-size:.78em;font-weight:700;color:#8b4513;margin-bottom:4px}
.mfg input,.mfg select{width:100%;padding:8px 10px;border:1px solid #c8a050;border-radius:5px;font-size:.9em;background:#fffef5}
.mfg input:focus,.mfg select:focus{outline:none;border-color:#8b2500}
.modal-btns{display:flex;gap:8px;justify-content:flex-end;margin-top:14px;padding-top:12px;border-top:1px solid #eee}
.mbtn{padding:7px 16px;border:none;border-radius:5px;font-size:.85em;font-weight:700;cursor:pointer}
.mbtn-primary{background:#8b2500;color:#fff}.mbtn-primary:hover{background:#c0392b}
.mbtn-secondary{background:#7f8c8d;color:#fff}.mbtn-secondary:hover{background:#95a5a6}
.mbtn-danger{background:#c0392b;color:#fff}.mbtn-danger:hover{background:#e74c3c}
/* CHARTS LIST */
.chart-item{display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e0d0a0;border-radius:6px;margin-bottom:6px;background:#fffef5;cursor:pointer;transition:.15s}
.chart-item:hover{background:#fff3cd;border-color:#c8a050}
.chart-item-info{flex:1}
.chart-item-label{font-weight:700;font-size:.9em;color:#333}
.chart-item-sub{font-size:.75em;color:#888;margin-top:2px}
.chart-item-acts{display:flex;gap:4px;flex-shrink:0}
.ci-btn{padding:3px 8px;border:none;border-radius:4px;font-size:.72em;font-weight:700;cursor:pointer}
.ci-load{background:#1a5276;color:#fff}.ci-load:hover{background:#2980b9}
.ci-edit{background:#e67e22;color:#fff}.ci-edit:hover{background:#f39c12}
.ci-del{background:#c0392b;color:#fff}.ci-del:hover{background:#e74c3c}
.chart-search{width:100%;padding:7px 10px;border:1px solid #c8a050;border-radius:5px;font-size:.85em;margin-bottom:10px;background:#fffef5}
.chart-search:focus{outline:none;border-color:#8b2500}
.charts-empty{text-align:center;color:#888;padding:20px;font-size:.85em}
.charts-count{font-size:.72em;color:#888;margin-bottom:8px}
[title]{cursor:help}
@media(max-width:700px){.pillars-grid{grid-template-columns:1fr 18px 1fr 18px 1fr 18px 1fr}.p-tg,.p-dz{font-size:2.4em}}
/* ── ANNOUNCEMENT TICKER ─────────────────────────────────────────────── */
.ticker-wrap{width:100%;background:linear-gradient(90deg,#5c0f0f,#8b1a1a,#5c0f0f);border-bottom:1px solid #c8a050;overflow:hidden;display:none}
.ticker-wrap.has-msg{display:block}
.ticker-inner{display:flex;align-items:center;height:34px}
.ticker-label{flex-shrink:0;background:#c8a050;color:#1a0800;font-size:.72em;font-weight:700;letter-spacing:2px;padding:0 14px;height:100%;display:flex;align-items:center;text-transform:uppercase;white-space:nowrap}
.ticker-track{overflow:hidden;flex:1;position:relative;height:100%}
.ticker-content{display:inline-flex;align-items:center;height:100%;white-space:nowrap;animation:tickerScroll 35s linear infinite}
.ticker-content:hover{animation-play-state:paused}
.ticker-text{color:#f5dfa0;font-size:.8em;padding:0 60px}
.ticker-sep{color:#c8a050;opacity:.6}
@keyframes tickerScroll{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
/* ── ADMIN ANNOUNCEMENT PANEL ────────────────────────────────────────── */
.ann-panel{background:#fff8f0;border:1px solid #c8a050;border-radius:8px;padding:16px;margin-bottom:16px}
.ann-panel h4{font-size:.9em;color:#8b2500;margin-bottom:12px;font-weight:700}
.ann-list{display:flex;flex-direction:column;gap:6px;margin-bottom:12px;max-height:180px;overflow-y:auto}
.ann-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid #e0d0a0;border-radius:5px;background:#fffef5;font-size:.82em}
.ann-item-msg{flex:1;color:#333;word-break:break-word}
.ann-item-status{font-size:.72em;padding:2px 8px;border-radius:10px;font-weight:700;white-space:nowrap}
.ann-on{background:#d5f5e3;color:#1e8449}.ann-off{background:#f0f0f0;color:#888}
.ann-actions{display:flex;gap:4px;flex-shrink:0}
.ann-btn{padding:3px 8px;border:none;border-radius:3px;font-size:.72em;cursor:pointer;font-weight:600}
.ann-btn-toggle{background:#d4a017;color:#fff}.ann-btn-del{background:#c0392b;color:#fff}
.ann-form{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.ann-form textarea{flex:1;min-width:200px;padding:7px 10px;border:1px solid #c8a050;border-radius:5px;font-size:.82em;background:#fffef5;resize:vertical;min-height:52px;font-family:inherit}
.ann-form button{padding:7px 16px;background:#8b2500;color:#fff;border:none;border-radius:5px;font-size:.82em;font-weight:700;cursor:pointer;align-self:flex-end}
</style>
</head>
<body>
<div class="navbar">
  <div class="nav-brand">🔮 Destiny Reading — BaZi Calculator 四柱八字</div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span>
    <?php if ($user['access_end']): ?>
    <span class="nav-access">Akses s/d: <?= $user['access_end'] ?></span>
    <?php endif; ?>
    <?php if ($user['role']==='admin'): ?>
    <a href="#" onclick="toggleAnnAdmin();return false">📢 Pengumuman</a>
    <a href="/bazical/admin.php">⚙️ Admin</a>
    <?php endif; ?>
    <a href="/bazical/logout.php">🚪 Logout</a>
  </div>
</div>

<!-- ── ANNOUNCEMENT TICKER ───────────────────────────────────────────────── -->
<div class="ticker-wrap" id="tickerWrap">
  <div class="ticker-inner">
    <div class="ticker-label">📢 Info</div>
    <div class="ticker-track">
      <div class="ticker-content" id="tickerContent"></div>
    </div>
  </div>
</div>

<!-- ── ADMIN ANNOUNCEMENT PANEL ──────────────────────────────────────────── -->
<?php if ($user['role']==='admin'): ?>
<div id="annAdminWrap" style="display:none;padding:12px 16px;background:#fff0e0;border-bottom:2px solid #c8a050">
  <div class="ann-panel">
    <h4>📢 Kelola Pengumuman (Running Text)</h4>
    <div class="ann-list" id="annList"><em style="color:#aaa;font-size:.82em">Memuat...</em></div>
    <div class="ann-form">
      <textarea id="annNewMsg" placeholder="Contoh: Maintenance 30 Juni 2026 pukul 21:00–23:00 WIB. Website akan down sementara." rows="2"></textarea>
      <button onclick="annSave()">➕ Tambah</button>
    </div>
  </div>
  <div style="text-align:right;margin-top:-4px">
    <button onclick="document.getElementById('annAdminWrap').style.display='none'" style="background:none;border:none;color:#888;font-size:.8em;cursor:pointer">✕ Tutup</button>
  </div>
</div>
<?php endif; ?>

<div class="wrap">
<div class="form-card">
  <div class="form-row">
    <div class="fg"><label>姓名 Nama</label><input type="text" id="i_name" placeholder="Nama" style="min-width:150px"></div>
    <div class="fg"><label>出生日期 Tanggal</label><input type="date" id="i_date" value="<?= date('Y-m-d') ?>"></div>
    <div class="fg"><label>时 Jam</label><input type="number" id="i_h" min="0" max="23" value="<?= date('G') ?>" style="min-width:65px"></div>
    <div class="fg"><label>分 Menit</label><input type="number" id="i_m" min="0" max="59" value="<?= (int)date('i') ?>" style="min-width:65px"></div>
    <div class="fg" style="align-self:flex-end;padding-bottom:6px;min-width:0;margin-left:-4px;flex-shrink:0">
      <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:.82em;color:#5d4037;white-space:nowrap">
        <input type="checkbox" id="i_unk" onchange="toggleUnknownHour(this.checked)" style="width:14px;height:14px;cursor:pointer;accent-color:#8b2500;min-width:14px">
        Jam tidak diketahui
      </label>
    </div>
    <div class="fg"><label>子时 Zi Mode</label>
      <select id="i_zi" style="font-size:.82em;min-width:120px">
        <option value="late">夜子时 Late Zi</option>
        <option value="early">早子时 Early Zi</option>
      </select></div>
    <div class="fg"><label>性别 JK</label>
      <select id="i_sex"><option value="M">乾 ♂ Pria</option><option value="F">坤 ♀ Wanita</option></select></div>
    <div class="fg"><label>流年 Tahun Analisis</label><input type="number" id="i_ln" value="<?= date('Y') ?>" style="min-width:85px"></div>
    <button class="calc-btn" id="calc_btn" onclick="calc()">排命盘 ▶</button>
    <button class="save-btn" id="save_btn" onclick="openSaveModal()">💾 Simpan</button>
    <button class="charts-btn" onclick="openChartsModal()">📂 Chart Saya</button>
  </div>
</div>

<div id="output"></div>
</div>

<!-- SAVE MODAL -->
<div class="modal-bg" id="saveModal">
  <div class="modal">
    <div class="modal-hd">
      <span id="saveModalTitle">💾 Simpan Chart</span>
      <button class="modal-close" onclick="closeSaveModal()">✕</button>
    </div>
    <div class="mfg">
      <label>Label Chart *</label>
      <input type="text" id="save_label" placeholder="cth: Bambang - Klien A" maxlength="100">
    </div>
    <div class="mfg">
      <label>Nama Orang</label>
      <input type="text" id="save_name" placeholder="Nama lengkap" maxlength="100">
    </div>
    <div class="mfg">
      <label>Tanggal Lahir</label>
      <input type="date" id="save_date" readonly style="background:#f5f5f5">
    </div>
    <div class="mfg" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
      <div><label>Jam</label><input type="number" id="save_h" min="0" max="23" readonly style="background:#f5f5f5"></div>
      <div><label>Menit</label><input type="number" id="save_m" min="0" max="59" readonly style="background:#f5f5f5"></div>
      <div><label>JK</label><select id="save_sex" disabled style="background:#f5f5f5">
        <option value="M">♂ Pria</option><option value="F">♀ Wanita</option>
      </select></div>
    </div>
    <div id="save_msg" style="font-size:.8em;margin-top:6px;display:none"></div>
    <div class="modal-btns">
      <button class="mbtn mbtn-secondary" onclick="closeSaveModal()">Batal</button>
      <button class="mbtn mbtn-primary" id="save_confirm_btn" onclick="confirmSave()">💾 Simpan</button>
    </div>
  </div>
</div>

<!-- CHARTS LIST MODAL -->
<div class="modal-bg" id="chartsModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-hd">
      <span>📂 Chart Saya</span>
      <button class="modal-close" onclick="closeChartsModal()">✕</button>
    </div>
    <input type="text" class="chart-search" id="chart_search" placeholder="🔍 Cari label atau nama..." oninput="filterCharts()">
    <div class="charts-count" id="charts_count"></div>
    <div id="charts_list"></div>
  </div>
</div>

<!-- EDIT CHART MODAL -->
<div class="modal-bg" id="editChartModal">
  <div class="modal">
    <div class="modal-hd">
      <span>✏️ Edit Chart</span>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <input type="hidden" id="edit_id">
    <div class="mfg"><label>Label Chart *</label><input type="text" id="edit_label" maxlength="100"></div>
    <div class="mfg"><label>Nama Orang</label><input type="text" id="edit_name" maxlength="100"></div>
    <div class="mfg"><label>Tanggal Lahir</label><input type="date" id="edit_date"></div>
    <div class="mfg" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px">
      <div><label>Jam</label><input type="number" id="edit_h" min="0" max="23"></div>
      <div><label>Menit</label><input type="number" id="edit_m" min="0" max="59"></div>
      <div><label>JK</label><select id="edit_sex">
        <option value="M">♂ Pria</option><option value="F">♀ Wanita</option>
      </select></div>
    </div>
    <div class="mfg" style="margin-top:4px;display:flex;gap:16px;align-items:center">
      <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.82em;color:#8b4513">
        <input type="checkbox" id="edit_unk" onchange="toggleEditUnknownHour(this.checked)" style="width:14px;height:14px">
        Jam tidak diketahui
      </label>
      <label style="font-size:.82em;color:#8b4513;display:flex;align-items:center;gap:4px">
        子时:
        <select id="edit_zi" style="font-size:.95em">
          <option value="late">夜子时 Late Zi</option>
          <option value="early">早子时 Early Zi</option>
        </select>
      </label>
    </div>
    <div id="edit_msg" style="font-size:.8em;margin-top:6px;display:none"></div>
    <div class="modal-btns">
      <button class="mbtn mbtn-secondary" onclick="closeEditModal()">Batal</button>
      <button class="mbtn mbtn-primary" onclick="confirmEdit()">💾 Simpan Perubahan</button>
    </div>
  </div>
</div>

<script>
const EL_CLS=['el-wood','el-fire','el-earth','el-metal','el-water'];
const TG_EL=[0,0,1,1,2,2,3,3,4,4];
const DZ_EL=[4,2,0,0,2,1,1,2,3,3,2,4];
const DZ=['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
const TG=['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
function tgCls(i){return EL_CLS[TG_EL[i]]||'';}
function dzCls(i){return EL_CLS[DZ_EL[i]]||'';}

function vBadge(ix){
  if(!ix)return'';
  let sym,color,lbl;
  if(ix.type==='bi'){sym='≈';color='#2980b9';lbl='比和';}
  else if(ix.type==='sheng'){sym=ix.dir==='ab'?'↓':'↑';color='#27ae60';lbl='生';}
  else{sym='✕';color='#c0392b';lbl='克';}
  return`<div class="v-badge" title="${ix.tooltip||''}"><span class="bs" style="color:${color}">${sym}</span><span class="bl" style="color:${color}">${lbl}</span></div>`;
}
function hBadge(ix){
  if(!ix)return`<div class="h-badge" style="opacity:.15">·</div>`;
  let sym,lbl,cls,bgcls;
  if(ix.type==='bi'){sym='≈';lbl='比';cls='int-bi';bgcls='int-bg-bi';}
  else if(ix.type==='sheng'){sym=ix.dir==='ab'?'→':'←';lbl='生';cls='int-sheng';bgcls='int-bg-sheng';}
  else{sym='✕';lbl='克';cls='int-ke';bgcls='int-bg-ke';}
  return`<div class="h-badge ${cls} ${bgcls}" title="${ix.tooltip||''}"><span class="sym">${sym}</span><span class="lbl">${lbl}</span></div>`;
}

function toggleUnknownHour(unk){
  const ih=document.getElementById('i_h');
  const im=document.getElementById('i_m');
  ih.disabled=unk;
  im.disabled=unk;
  ih.style.background=unk?'#f0f0f0':'';
  im.style.background=unk?'#f0f0f0':'';
}

function renderPillar(p,kw,kwY){
  // Unknown hour pillar
  if(p.unknown){
    return`<div class="p-col">
    <div class="p-label">${p.label}</div>
    <div class="p-ss">?</div>
    <span class="p-tg" style="color:#aaa;font-size:2em">?</span>
    <span class="p-tg-el" style="color:#aaa">—</span>
    <div style="margin:4px 0"></div>
    <span class="p-dz" style="color:#aaa;font-size:2em">?</span>
    <span class="p-dz-el" style="color:#aaa">—</span>
    <div class="p-hs-block" style="color:#bbb;font-size:.8em;text-align:center;padding:4px">—</div>
    <div class="p-qi-block" style="color:#bbb;font-size:.8em;text-align:center">Jam tidak diketahui</div>
    <span class="p-ny" style="color:#bbb">—</span>
  </div>`;}
  const isKwDay=kw.includes(p.dz);
  const isKwYear=kwY.includes(p.dz);
  let kwBadge='';
  if(isKwDay) kwBadge+='<div><span class="kw-badge">空亡 Kōng Wáng</span></div>';
  if(isKwYear) kwBadge+='<div><span class="kw-badge" style="background:#e8f0fe;border-color:#2980b9;color:#1a5276">年空亡 Nián Kōng Wáng</span></div>';
  const hsHTML=p.hidden.map(h=>`<div class="p-hs-line">
    <span class="p-hs-char ${EL_CLS[h.el]}">${h.tg}</span>
    <span class="p-hs-ss">${h.ss}</span>
    <span class="p-hs-py">(${h.ss_py})</span>
  </div>`).join('');
  return`<div class="p-col">
    <div class="p-label">${p.label}</div>
    <div class="p-ss${p.ss.name==='主'?' main':''}">${p.ss.name}<span class="p-ss-py">${p.ss.py}</span></div>
    <span class="p-tg ${tgCls(p.tg_idx)}">${p.tg}</span>
    <span class="p-tg-el ${tgCls(p.tg_idx)}">${p.el_tg_name.split(' ')[0]} ${p.tg_yang?'+':'-'}</span>
    ${vBadge(p.v_interaction)}
    <span class="p-dz ${dzCls(p.dz_idx)}">${p.dz}</span>
    <span class="p-dz-el ${dzCls(p.dz_idx)}">${p.el_dz_name.split(' ')[0]} ${p.dz_yang?'+':'-'}</span>
    <div class="p-hs-block">${hsHTML}</div>
    <div class="p-qi-block">
      <div class="p-qi-row"><span class="p-qi-lbl">DM:</span> <span class="p-qi-val">${p.cs_dm.name}</span> <span class="p-qi-py">${p.cs_dm.py} · ${p.cs_dm.en}</span></div>
      <div class="p-qi-row"><span class="p-qi-lbl">自:</span> <span class="p-qi-val self">${p.cs_self.name}</span> <span class="p-qi-py">${p.cs_self.py} · ${p.cs_self.en}</span></div>
    </div>
    <span class="p-ny">${p.nayin.name}</span>
    <span class="p-ny-py">${p.nayin.py}</span>
    <span class="p-ny-id">${p.nayin.id}</span>
    ${kwBadge}
  </div>`;
}

function kwHTML(kw){return kw.split('').map(c=>{const i=DZ.indexOf(c);return i>=0?`<span class="${dzCls(i)}">${c}</span>`:c;}).join('');}

function syItem(lbl,t){
  if(t.tg==='?'){
    return`<div class="sy-item"><span class="sy-lbl">${lbl}</span>
    <span style="color:#aaa">? ?</span>
    <span class="sy-ny" style="color:#aaa">—</span></div>`;}
  return`<div class="sy-item"><span class="sy-lbl">${lbl}</span>
    <span class="${tgCls(t.tg_idx)}" style="font-weight:700">${t.tg}</span>
    <span class="${dzCls(t.dz_idx)}" style="font-weight:700">${t.dz}</span>
    <span class="sy-ny">${t.nayin.name}</span>
    <span class="sy-ny-py">(${t.nayin.py})</span></div>`;}

let _lastParams={};
let _allCharts=[];
let _chartsDirty=true; // flag: need to reload from server

async function calc(){
  const dv=document.getElementById('i_date').value;
  if(!dv)return;
  const[y,m,d]=dv.split('-').map(Number);
  const unk=document.getElementById('i_unk').checked;
  const h=unk?0:+document.getElementById('i_h').value||0;
  const mi=unk?0:+document.getElementById('i_m').value||0;
  const sex=document.getElementById('i_sex').value;
  const name=document.getElementById('i_name').value||'';
  const lnY=+document.getElementById('i_ln').value||new Date().getFullYear();
  const ziMode=document.getElementById('i_zi').value;
  const earlyZi=ziMode==='early';

  document.getElementById('output').innerHTML='<div class="loading"><span class="spinner"></span>Menghitung BaZi...</div>';
  document.getElementById('calc_btn').disabled=true;
  document.getElementById('save_btn').style.display='none';

  try{
    const res=await fetch('/bazical/api/calculate.php',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({year:y,month:m,day:d,hour:h,min:mi,sex,lnYear:lnY,unknown_hour:unk,early_zi:earlyZi})
    });
    if(res.status===401){window.location='/bazical/index.php?msg=session_expired';return;}
    const data=await res.json();
    if(!data.ok)throw new Error(data.error||'Server error');
    _lastParams={year:y,month:m,day:d,hour:h,min:mi,sex,lnYear:lnY,name,unknownHour:unk,ziMode};
    render(data,name,sex,lnY);
    // Check if already saved
    checkExistingSave(dv,h,mi,sex,unk);
  }catch(e){
    document.getElementById('output').innerHTML=`<div class="loading" style="color:#c0392b">❌ Error: ${e.message}</div>`;
  }
  document.getElementById('calc_btn').disabled=false;
}

async function checkExistingSave(date,h,m,sex,unk=false){
  try{
    const res=await fetch('/bazical/api/charts.php',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'check',birth_date:date,birth_hour:h,birth_min:m,sex,unknown_hour:unk})
    });
    const data=await res.json();
    const btn=document.getElementById('save_btn');
    btn.style.display='inline-block';
    if(data.existing){
      btn.textContent=`✏️ Update "${data.existing.label}"`;
      btn.style.background='#d4a017';
      btn._existingId=data.existing.id;
      btn._existingLabel=data.existing.label;
    } else {
      btn.textContent='💾 Simpan';
      btn.style.background='#1a5276';
      btn._existingId=null;
    }
  }catch(e){}
}

function render(d,name,sex,lnY){
  const kw=d.kongwang,kwY=d.kongwangYear;
  const pillars=d.pillars,ixs=d.interactions,dy=d.dayun;
  let gridHTML='';
  pillars.forEach((p,i)=>{
    gridHTML+=renderPillar(p,kw,kwY);
    if(i<3) gridHTML+=`<div class="p-gap">${hBadge(ixs[i].tg)}${hBadge(ixs[i].dz)}</div>`;
  });
  const slIdx=TG.indexOf(d.siling);
  const slHTML=d.siling?`<div class="sy-item"><span class="sy-lbl">司令 (Sī Lìng):</span>
    <span class="${slIdx>=0?tgCls(slIdx):''}" style="font-weight:700">${d.siling}</span></div>`:'';

  // Da Yun: birth + DY1..DY9 = 10 cols
  const allCols=dy.list.slice(0,10);
  const activeDyIdx=allCols.findIndex((_,i)=>{
    if(i===0)return false;const nx=allCols[i+1];
    return _.calYear<=lnY&&(!nx||nx.calYear>lnY);});
  function cc(i){if(i===activeDyIdx)return' class="col-active"';if(allCols[i].isBirth)return' class="col-birth"';return'';}
  function tc(i){if(i===activeDyIdx)return' class="col-active-hd"';if(allCols[i].isBirth)return' style="background:#fffde0"';return'';}

  let th=`<thead><tr><th class="row-hd">栏位</th>`;
  allCols.forEach((_,i)=>{th+=`<th${tc(i)} style="min-width:72px">${_.isBirth?'命造':'大运'+i}</th>`;});
  th+='</tr></thead>';
  function mkRow(lbl,fn){let r=`<tr><td class="row-hd">${lbl}</td>`;allCols.forEach((col,i)=>{r+=`<td${cc(i)}>${fn(col,i)}</td>`;});return r+'</tr>';}
  let tb='<tbody>';
  tb+=mkRow('十神',(c)=>c.isBirth?`<div class="dc"><span class="dc-ss" style="color:#888;font-size:.65em">生月</span></div>`:`<div class="dc"><span class="dc-ss">${c.ss||''}</span></div>`);
  tb+=mkRow('天干',(c)=>`<div class="dc"><span class="dc-tg ${tgCls(c.tg_idx)}">${c.tg}</span></div>`);
  tb+=mkRow('地支',(c)=>`<div class="dc"><span class="dc-dz ${dzCls(c.dz_idx)}">${c.dz}</span></div>`);
  tb+=mkRow('长生(DM)',(c)=>`<div class="dc"><span class="dc-cs">${c.cs_dm?.name||''}</span><span class="dc-cs-py">${c.cs_dm?.py||''}</span></div>`);
  tb+=mkRow('纳音',(c)=>`<div class="dc"><span class="dc-ny">${c.nayin?.name||''}</span><span class="dc-ny-py">${c.nayin?.py||''}</span></div>`);
  tb+=mkRow('虚岁',(c)=>`<div class="dc"><span class="dc-age">${c.isBirth?'1岁':(Math.ceil(c.startAge)+1)+'岁'}</span></div>`);
  tb+=mkRow('交运',(c)=>`<div class="dc"><span class="dc-yr">${c.calYear}</span></div>`);
  tb+=mkRow('日期',(c)=>`<div class="dc"><span class="dc-date">${c.calMo}/${c.calDay}</span></div>`);
  for(let li=0;li<10;li++){
    tb+=`<tr><td class="row-hd">第${li+1}年</td>`;
    allCols.forEach((col,i)=>{
      const c2=cc(i);
      if(li<col.ln.length){const ln=col.ln[li];const isNow=ln.year===lnY;
        tb+=`<td${c2}><div class="ln${isNow?' ln-now':''}">
          <span class="ln-yr">${ln.year} </span>
          <span class="${tgCls(ln.tg_idx)}" style="font-weight:700">${ln.tg}</span>
          <span class="${dzCls(ln.dz_idx)}" style="font-weight:700">${ln.dz}</span>
        </div></td>`;}else tb+=`<td${c2}></td>`;});
    tb+='</tr>';}
  tb+=mkRow('止于',(c,i)=>{const nx=allCols[i+1];return`<div class="dc"><span class="dc-stop">${nx?nx.calYear-1:'—'}</span></div>`;});
  tb+='</tbody>';

  const dv=document.getElementById('i_date').value;
  const[y,m,dd]=dv.split('-').map(Number);
  const h=+document.getElementById('i_h').value||0;
  const mi=+document.getElementById('i_m').value||0;
  const sexLbl=sex==='M'?'乾 ♂':'坤 ♀';
  document.getElementById('output').innerHTML=`
  <div class="board">
    <div class="board-hd">
      <div class="bd-left">
        <span class="bd-qian">${sex==='M'?'乾':'坤'}</span>
        <div class="bd-info">
          <span class="bd-name">${name||''} &nbsp;<span style="font-size:.8em;color:#888">${sexLbl}</span></span>
          <span class="bd-date">阳历: ${y}-${String(m).padStart(2,'0')}-${String(dd).padStart(2,'0')} ${d.unknownHour?'??:??':String(h).padStart(2,'0')+':'+String(mi).padStart(2,'0')} (${d.ziMode==='early'?'早子时':'夜子时'}) &nbsp;·&nbsp; ${d.lcNote}</span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <button onclick="openCompare()" style="padding:6px 12px;background:#6a1b9a;color:#fff;border:none;border-radius:5px;font-size:.8em;font-weight:700;cursor:pointer;">⚖️ Compare</button>
        <button onclick="openPDF()" style="padding:6px 12px;background:#1a5276;color:#fff;border:none;border-radius:5px;font-size:.8em;font-weight:700;cursor:pointer;">🖨️ PDF</button>
      </div>
    </div>
    <div class="pillars-outer"><div class="pillars-grid">${gridHTML}</div></div>
    <div class="san-yuan">${syItem('胎元 (Tāi Yuán):',d.taiyuan)}${syItem('命宫 (Mìng Gōng):',d.minggong)}${syItem('身宫 (Shēn Gōng):',d.shengong)}${slHTML}<div class="sy-item"><span class="sy-lbl">日空亡 (Rì Kōng Wáng):</span><span class="bd-kw-val">${kwHTML(kw)}空</span></div><div class="sy-item"><span class="sy-lbl">年空亡 (Nián Kōng Wáng):</span><span class="bd-kw-val">${kwHTML(kwY)}空</span></div></div>
    <div class="qiyun-bar">出生后约 <b>${dy.ay}年${dy.am}月${dy.ad}天</b> 起运 (${dy.fwd?'顺行 →':'逆行 ←'}) &nbsp;·&nbsp; 实际起运约 <b>${dy.startYear}-${dy.startMo}-${dy.startDay}</b></div>
    <div class="dy-outer"><table class="dy-tbl">${th+tb}</table></div>
  </div>`;
}

function openPDF(){
  if(!_lastParams.year)return;
  const p=_lastParams;
  const n=encodeURIComponent(document.getElementById('i_name').value||'');
  const unk=p.unknownHour?1:0;
  // Server-side PDF via mPDF — consistent A4 landscape on all devices
  window.location.href=`/bazical/pdf_generate.php?y=${p.year}&mo=${p.month}&d=${p.day}&h=${p.hour}&mi=${p.min}&s=${p.sex}&ln=${p.lnYear}&n=${n}&unk=${unk}&ez=${p.ziMode==='early'?1:0}`;
}

function openCompare(){
  if(!_lastParams.year)return;
  const p=_lastParams;
  const n=encodeURIComponent(document.getElementById('i_name').value||'');
  const unk=p.unknownHour?1:0;
  window.location.href=`/bazical/compare.php?y=${p.year}&mo=${p.month}&d=${p.day}&h=${p.hour}&mi=${p.min}&s=${p.sex}&n=${n}&unk=${unk}&ez=${p.ziMode==='early'?1:0}`;
}

// ═══ SAVE / CHARTS ═══
function openSaveModal(){
  if(!_lastParams.year)return;
  const p=_lastParams;
  const btn=document.getElementById('save_btn');
  const isUpdate=!!btn._existingId;
  document.getElementById('saveModalTitle').textContent=isUpdate?'✏️ Update Chart':'💾 Simpan Chart';
  document.getElementById('save_confirm_btn').textContent=isUpdate?'✏️ Update':'💾 Simpan';
  document.getElementById('save_label').value=isUpdate?(btn._existingLabel||''):(p.name||'');
  document.getElementById('save_name').value=p.name||'';
  document.getElementById('save_date').value=`${p.year}-${String(p.month).padStart(2,'0')}-${String(p.day).padStart(2,'0')}`;
  document.getElementById('save_h').value=p.unknownHour?'':p.hour;
  document.getElementById('save_m').value=p.unknownHour?'':p.min;
  document.getElementById('save_h').disabled=p.unknownHour;
  document.getElementById('save_m').disabled=p.unknownHour;
  document.getElementById('save_h').style.background=p.unknownHour?'#f0f0f0':'';
  document.getElementById('save_m').style.background=p.unknownHour?'#f0f0f0':'';
  // Show unknown hour indicator in modal
  let unkNote=document.getElementById('save_unk_note');
  if(!unkNote){
    unkNote=document.createElement('div');
    unkNote.id='save_unk_note';
    unkNote.style='font-size:.78em;color:#888;margin:4px 0 0;display:none';
    unkNote.textContent='⚠️ Jam tidak diketahui';
    document.getElementById('save_h').parentNode.parentNode.appendChild(unkNote);
  }
  unkNote.style.display=p.unknownHour?'':'none';
  document.getElementById('save_sex').value=p.sex;
  document.getElementById('save_msg').style.display='none';
  document.getElementById('saveModal').classList.add('show');
  setTimeout(()=>document.getElementById('save_label').focus(),100);
}
function closeSaveModal(){
  document.getElementById('saveModal').classList.remove('show');
  // Reset confirm button in case it was changed to overwrite
  const confirmBtn=document.getElementById('save_confirm_btn');
  confirmBtn.textContent=document.getElementById('save_btn')._existingId?'✏️ Update':'💾 Simpan';
  confirmBtn.style.background='';
  confirmBtn.onclick=confirmSave;
}

async function confirmSave(){
  const btn=document.getElementById('save_btn');
  const isUpdate=!!btn._existingId;
  const label=document.getElementById('save_label').value.trim();
  if(!label){showSaveMsg('Label tidak boleh kosong','#c0392b');return;}
  const p=_lastParams;
  const payload={
    action: isUpdate?'update':'save',
    label,
    person_name: document.getElementById('save_name').value.trim(),
    birth_date: document.getElementById('save_date').value,
    birth_hour: p.hour, birth_min: p.min, sex: p.sex,
    unknown_hour: p.unknownHour||false,
    zi_mode: p.ziMode||'late'
  };
  if(isUpdate) payload.id=btn._existingId;
  try{
    const res=await fetch('/bazical/api/charts.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const data=await res.json();
    if(data.ok){
      showSaveMsg('✅ '+data.message,'#1e8449');
      _chartsDirty=true;
      const savBtn=document.getElementById('save_btn');
      savBtn._existingId=data.id;
      savBtn._existingLabel=data.label||label;
      savBtn.textContent=`✏️ Update "${data.label||label}"`;
      savBtn.style.background='#d4a017';
      setTimeout(closeSaveModal,1200);
    } else if(data.duplicate){
      // Label already exists — ask to overwrite
      showSaveMsg(`⚠️ Label "${label}" sudah ada. Klik Timpa untuk mengganti, atau ubah labelnya.`,'#d4a017');
      // Show overwrite button
      const confirmBtn=document.getElementById('save_confirm_btn');
      confirmBtn.textContent='⚠️ Timpa (Overwrite)';
      confirmBtn.style.background='#d4a017';
      confirmBtn.onclick=()=>confirmSaveForce(label);
    } else showSaveMsg('❌ '+(data.error||'Error'),'#c0392b');
  }catch(e){showSaveMsg('❌ Error: '+e.message,'#c0392b');}
}

async function confirmSaveForce(label){
  const p=_lastParams;
  const payload={
    action:'save', label,
    person_name: document.getElementById('save_name').value.trim(),
    birth_date: document.getElementById('save_date').value,
    birth_hour: p.hour, birth_min: p.min, sex: p.sex,
    unknown_hour: p.unknownHour||false,
    zi_mode: p.ziMode||'late',
    force_overwrite: true
  };
  try{
    const res=await fetch('/bazical/api/charts.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const data=await res.json();
    if(data.ok){
      showSaveMsg('✅ '+data.message,'#1e8449');
      _chartsDirty=true;
      const savBtn=document.getElementById('save_btn');
      savBtn._existingId=data.id;
      savBtn._existingLabel=data.label||label;
      savBtn.textContent=`✏️ Update "${data.label||label}"`;
      savBtn.style.background='#d4a017';
      setTimeout(closeSaveModal,1200);
      const confirmBtn=document.getElementById('save_confirm_btn');
      confirmBtn.textContent='💾 Simpan';
      confirmBtn.style.background='';
      confirmBtn.onclick=confirmSave;
    } else showSaveMsg('❌ '+(data.error||'Error'),'#c0392b');
  }catch(e){showSaveMsg('❌ Error: '+e.message,'#c0392b');}
}
function showSaveMsg(msg,color){
  const el=document.getElementById('save_msg');
  el.textContent=msg; el.style.color=color; el.style.display='block';
}

async function openChartsModal(){
  document.getElementById('chartsModal').classList.add('show');
  document.getElementById('chart_search').value='';
  if(_chartsDirty){
    document.getElementById('charts_list').innerHTML='<div class="charts-empty">⏳ Memuat...</div>';
    await loadCharts();
  } else {
    renderChartsList(_allCharts);
  }
}
function closeChartsModal(){document.getElementById('chartsModal').classList.remove('show');}

async function loadCharts(){
  try{
    const res=await fetch('/bazical/api/charts.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'list'})});
    const data=await res.json();
    if(data.ok){_allCharts=data.charts;_chartsDirty=false;renderChartsList(_allCharts);}
  }catch(e){document.getElementById('charts_list').innerHTML='<div class="charts-empty">❌ Gagal memuat</div>';}
}

function renderChartsList(charts){
  const cnt=document.getElementById('charts_count');
  cnt.textContent=`${charts.length} chart tersimpan`;
  if(!charts.length){document.getElementById('charts_list').innerHTML='<div class="charts-empty">Belum ada chart tersimpan.<br>Buat chart lalu klik 💾 Simpan.</div>';return;}
  const SEX={'M':'♂ Pria','F':'♀ Wanita'};
  document.getElementById('charts_list').innerHTML=charts.map(c=>`
    <div class="chart-item" onclick="loadChart(${JSON.stringify(c).replace(/"/g,'&quot;')})">
      <div class="chart-item-info">
        <div class="chart-item-label">${escHtml(c.label)}</div>
        <div class="chart-item-sub">${escHtml(c.person_name||'—')} &nbsp;|&nbsp; ${c.birth_date} ${c.birth_hour===null?'??:??':String(c.birth_hour).padStart(2,'0')+':'+String(c.birth_min).padStart(2,'0')} &nbsp;|&nbsp; ${SEX[c.sex]||c.sex}</div>
      </div>
      <div class="chart-item-acts" onclick="event.stopPropagation()">
        <button class="ci-btn ci-load" onclick="loadChart(${JSON.stringify(c).replace(/"/g,'&quot;')})">▶ Load</button>
        <button class="ci-btn ci-edit" onclick="openEditModal(${JSON.stringify(c).replace(/"/g,'&quot;')})">✏️</button>
        <button class="ci-btn ci-del" onclick="deleteChart(${c.id},'${escHtml(c.label)}')">🗑️</button>
      </div>
    </div>`).join('');
}

function filterCharts(){
  const q=document.getElementById('chart_search').value.toLowerCase();
  const filtered=q?_allCharts.filter(c=>(c.label+c.person_name+c.birth_date).toLowerCase().includes(q)):_allCharts;
  renderChartsList(filtered);
}

function loadChart(c){
  closeChartsModal();
  const unk=c.birth_hour===null;
  document.getElementById('i_name').value=c.person_name||c.label||'';
  document.getElementById('i_date').value=c.birth_date;
  document.getElementById('i_unk').checked=unk;
  toggleUnknownHour(unk);
  document.getElementById('i_h').value=unk?0:c.birth_hour;
  document.getElementById('i_m').value=unk?0:c.birth_min;
  document.getElementById('i_sex').value=c.sex;
  document.getElementById('i_zi').value=c.zi_mode||'late';
  calc();
}

function openEditModal(c){
  const unk=c.birth_hour===null;
  document.getElementById('edit_id').value=c.id;
  document.getElementById('edit_label').value=c.label;
  document.getElementById('edit_name').value=c.person_name||'';
  document.getElementById('edit_date').value=c.birth_date;
  document.getElementById('edit_unk').checked=unk;
  toggleEditUnknownHour(unk);
  document.getElementById('edit_h').value=unk?'':c.birth_hour;
  document.getElementById('edit_m').value=unk?'':c.birth_min;
  document.getElementById('edit_sex').value=c.sex;
  document.getElementById('edit_zi').value=c.zi_mode||'late';
  document.getElementById('edit_msg').style.display='none';
  document.getElementById('editChartModal').classList.add('show');
}
function closeEditModal(){document.getElementById('editChartModal').classList.remove('show');}
function toggleEditUnknownHour(unk){
  const eh=document.getElementById('edit_h');
  const em=document.getElementById('edit_m');
  eh.disabled=unk; em.disabled=unk;
  eh.style.background=unk?'#f0f0f0':'';
  em.style.background=unk?'#f0f0f0':'';
}

async function confirmEdit(){
  const id=+document.getElementById('edit_id').value;
  const label=document.getElementById('edit_label').value.trim();
  if(!label){document.getElementById('edit_msg').textContent='Label wajib diisi';document.getElementById('edit_msg').style.cssText='display:block;color:#c0392b';return;}
  const unk=document.getElementById('edit_unk').checked;
  const payload={action:'update',id,label,
    person_name:document.getElementById('edit_name').value.trim(),
    birth_date:document.getElementById('edit_date').value,
    birth_hour:unk?null:+document.getElementById('edit_h').value,
    birth_min:unk?null:+document.getElementById('edit_m').value,
    sex:document.getElementById('edit_sex').value,
    unknown_hour:unk,zi_mode:document.getElementById('edit_zi').value};
  try{
    const res=await fetch('/bazical/api/charts.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const data=await res.json();
    if(data.ok){
      const el=document.getElementById('edit_msg');
      el.textContent='✅ '+data.message; el.style.cssText='display:block;color:#1e8449';
      _chartsDirty=true;
      setTimeout(()=>{closeEditModal();loadCharts();},1000);
    } else {
      const el=document.getElementById('edit_msg');
      el.textContent='❌ '+(data.error||'Error'); el.style.cssText='display:block;color:#c0392b';
    }
  }catch(e){document.getElementById('edit_msg').textContent='❌ '+e.message;document.getElementById('edit_msg').style.cssText='display:block;color:#c0392b';}
}

async function deleteChart(id,label){
  if(!confirm(`Hapus chart "${label}"?`))return;
  try{
    const res=await fetch('/bazical/api/charts.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'delete',id})});
    const data=await res.json();
    if(data.ok){
      // Update local cache instantly — no need to reload from server
      _allCharts=_allCharts.filter(c=>c.id!==id);
      renderChartsList(_allCharts);
    }
    else alert('❌ '+(data.error||'Error'));
  }catch(e){alert('❌ '+e.message);}
}

function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

// Close modals on background click
['saveModal','chartsModal','editChartModal'].forEach(id=>{
  document.getElementById(id).addEventListener('click',function(e){if(e.target===this)this.classList.remove('show');});
});

// Enter key on save label
document.getElementById('save_label').addEventListener('keydown',e=>{if(e.key==='Enter')confirmSave();});

window.onload=()=>{calc();loadAnnouncements();}

// ── ANNOUNCEMENT TICKER & ADMIN ───────────────────────────────────────────
async function loadAnnouncements(){
  try{
    const res=await fetch('/bazical/api/announcements.php',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'get'})
    });
    const data=await res.json();
    if(data.ok && data.announcements.length>0){
      renderTicker(data.announcements);
    }
  }catch(e){}
}

function renderTicker(items){
  const wrap=document.getElementById('tickerWrap');
  const content=document.getElementById('tickerContent');
  if(!items||items.length===0){wrap.classList.remove('has-msg');return;}
  // Duplicate content for seamless loop
  const html=items.map(a=>`<span class="ticker-text">${escHtml(a.message)}</span><span class="ticker-sep">✦</span>`).join('');
  content.innerHTML=html+html; // duplicate for seamless scroll
  wrap.classList.add('has-msg');
}

function toggleAnnAdmin(){
  const w=document.getElementById('annAdminWrap');
  if(w.style.display==='none'){
    w.style.display='block';
    loadAnnList();
  } else {
    w.style.display='none';
  }
}

async function loadAnnList(){
  const list=document.getElementById('annList');
  list.innerHTML='<em style="color:#aaa;font-size:.82em">Memuat...</em>';
  try{
    const res=await fetch('/bazical/api/announcements.php',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'list'})
    });
    const data=await res.json();
    if(data.ok){
      if(data.announcements.length===0){
        list.innerHTML='<em style="color:#aaa;font-size:.82em">Belum ada pengumuman.</em>';return;
      }
      list.innerHTML=data.announcements.map(a=>`
        <div class="ann-item">
          <span class="ann-item-msg">${escHtml(a.message)}</span>
          <span class="ann-item-status ${a.is_active==1?'ann-on':'ann-off'}">${a.is_active==1?'Aktif':'Nonaktif'}</span>
          <div class="ann-actions">
            <button class="ann-btn ann-btn-toggle" onclick="annToggle(${a.id})" title="${a.is_active==1?'Nonaktifkan':'Aktifkan'}">${a.is_active==1?'⏸':'▶'}</button>
            <button class="ann-btn ann-btn-del" onclick="annDelete(${a.id})" title="Hapus">🗑</button>
          </div>
        </div>`).join('');
    }
  }catch(e){list.innerHTML='<em style="color:#c0392b">Gagal memuat</em>';}
}

async function annSave(){
  const msg=document.getElementById('annNewMsg').value.trim();
  if(!msg){alert('Pesan tidak boleh kosong');return;}
  try{
    const res=await fetch('/bazical/api/announcements.php',{
      method:'POST',credentials:'include',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({action:'save',message:msg,is_active:1})
    });
    const data=await res.json();
    if(data.ok){
      document.getElementById('annNewMsg').value='';
      loadAnnList();
      loadAnnouncements(); // refresh ticker
    } else alert('❌ '+(data.error||'Error'));
  }catch(e){alert('❌ '+e.message);}
}

async function annToggle(id){
  await fetch('/bazical/api/announcements.php',{
    method:'POST',credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'toggle',id})
  });
  loadAnnList();
  loadAnnouncements();
}

async function annDelete(id){
  if(!confirm('Hapus pengumuman ini?'))return;
  await fetch('/bazical/api/announcements.php',{
    method:'POST',credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'delete',id})
  });
  loadAnnList();
  loadAnnouncements();
}
</script>
</body>
</html>