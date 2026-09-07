<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
$base = dirname(__FILE__);
require_once $base . '/api/config.php';
$user = requireLogin();

// ── Prefill dari calculator.php (opsional) ───────────────────────────────────
$pf = [
  'n'  => isset($_GET['n'])  ? substr((string)$_GET['n'], 0, 100) : '',
  'y'  => isset($_GET['y'])  ? (int)$_GET['y']  : 0,
  'mo' => isset($_GET['mo']) ? (int)$_GET['mo'] : 0,
  'd'  => isset($_GET['d'])  ? (int)$_GET['d']  : 0,
  'h'  => isset($_GET['h'])  ? (int)$_GET['h']  : 0,
  'mi' => isset($_GET['mi']) ? (int)$_GET['mi'] : 0,
  's'  => (($_GET['s'] ?? 'M') === 'F') ? 'F' : 'M',
  'unk'=> !empty($_GET['unk']),
  'ez' => !empty($_GET['ez']),
];
$hasPrefill = ($pf['y'] >= 1900 && $pf['y'] <= 2100 && $pf['mo'] >= 1 && $pf['d'] >= 1);
$dateA = $hasPrefill
    ? sprintf('%04d-%02d-%02d', $pf['y'], $pf['mo'], $pf['d'])
    : date('Y-m-d');
$hA = $hasPrefill ? $pf['h']  : (int)date('G');
$mA = $hasPrefill ? $pf['mi'] : (int)date('i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Compare BaZi — Destiny Reading</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Microsoft YaHei','Segoe UI',Arial,sans-serif;background:#f5f0e0;color:#222;font-size:13px}
.navbar{background:linear-gradient(135deg,#8b2500,#c0392b);color:#fff;padding:0 16px;display:flex;align-items:center;justify-content:space-between;height:48px;box-shadow:0 2px 8px rgba(0,0,0,.2)}
.nav-brand{font-size:1em;font-weight:700;color:#f5c842}
.nav-right{display:flex;align-items:center;gap:12px;font-size:.8em}
.nav-right a{color:#ffd;text-decoration:none;padding:5px 10px;border-radius:4px;transition:.2s}
.nav-right a:hover{background:rgba(255,255,255,.15)}
.nav-user{color:#ffd;font-size:.82em}
.wrap{max-width:1600px;margin:0 auto;padding:8px}
/* ── FORM DUA SISI ── */
.cmp-forms{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
.form-card{background:#fffde8;border:2px solid #c8a050;border-radius:6px;padding:10px 14px}
.form-card.side-b{border-color:#1a5276;background:#f0f8ff}
.side-title{font-size:.82em;font-weight:700;color:#8b4513;margin-bottom:8px;padding-bottom:5px;border-bottom:1px solid #e0d0a0;display:flex;align-items:center;gap:6px}
.side-b .side-title{color:#1a5276;border-bottom-color:#bcd8ec}
.form-row{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end}
.fg{display:flex;flex-direction:column;gap:3px}
.fg label{font-size:.72em;color:#8b4513;font-weight:700}
.side-b .fg label{color:#1a5276}
.fg input,.fg select{padding:5px 8px;border:1px solid #c8a050;border-radius:4px;font-size:.88em;background:#fffef5;min-width:100px}
.side-b .fg input,.side-b .fg select{border-color:#9dc3dd;background:#fbfdff}
.fg input:focus,.fg select:focus{outline:none;border-color:#8b2500}
.chk-lbl{display:flex;align-items:center;gap:5px;cursor:pointer;font-size:.8em;color:#5d4037;white-space:nowrap}
.chk-lbl input{width:14px;height:14px;min-width:14px;cursor:pointer;accent-color:#8b2500}
/* ── ACTION BAR ── */
.action-bar{text-align:center;margin-bottom:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.gen-btn{padding:10px 40px;background:#8b2500;color:#fff;border:none;border-radius:5px;font-size:1em;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(139,37,0,.3)}
.gen-btn:hover{background:#c0392b}
.gen-btn:disabled{background:#aaa;cursor:not-allowed;box-shadow:none}
.back-btn{padding:10px 20px;background:#7f8c8d;color:#fff;border:none;border-radius:5px;font-size:.9em;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center}
.back-btn:hover{background:#95a5a6}
/* ── OUTPUT DUA SISI ── */
.cmp-out{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.board{background:#fffef0;border:2px solid #c8a050;border-radius:4px;overflow:hidden}
.board.side-b{border-color:#1a5276}
.board-hd{background:#e8d890;padding:6px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #c8a050;flex-wrap:wrap;gap:8px}
.board.side-b .board-hd{background:#d6eaf8;border-bottom-color:#1a5276}
.bd-left{display:flex;align-items:center;gap:10px}
.bd-qian{font-size:1.6em;font-weight:900;color:#c0392b}
.bd-info{display:flex;flex-direction:column;gap:1px}
.bd-name{font-size:1em;font-weight:700}
.bd-date{font-size:.75em;color:#666}
.bd-kw-val{font-size:.9em;font-weight:700;letter-spacing:1px}
/* PILLAR GRID */
.pillars-outer{border-bottom:2px solid #c8a050}
.pillars-grid{display:grid;grid-template-columns:1fr 20px 1fr 20px 1fr 20px 1fr}
.p-col{border-right:1px solid #d4b896;text-align:center;padding:5px 3px 8px}
.p-col:last-child{border-right:none}
.p-label{background:#e8d890;font-size:.72em;color:#5d4037;font-weight:700;padding:3px 0;margin:-5px -3px 4px;border-bottom:1px solid #c8a050}
.board.side-b .p-label{background:#d6eaf8;color:#1a5276;border-bottom-color:#9dc3dd}
.p-ss{font-size:.85em;font-weight:700;color:#2980b9;padding:2px 0;min-height:18px}
.p-ss.main{color:#c0392b}
.p-ss-py{font-size:.66em;color:#aaa;font-style:italic;display:block;line-height:1.2}
.p-tg{font-size:2.8em;font-weight:900;display:block;line-height:1;padding:3px 0 2px}
.p-tg-el{font-size:.68em;color:#888;display:block;padding:0 0 3px}
.v-badge{display:flex;flex-direction:column;align-items:center;margin:1px auto;cursor:help}
.v-badge .bs{font-size:1em;line-height:1;font-weight:700}
.v-badge .bl{font-size:.58em;color:#666}
.p-dz{font-size:2.8em;font-weight:900;display:block;line-height:1;padding:2px 0}
.p-dz-el{font-size:.68em;color:#888;display:block;padding:0 0 2px}
.p-hs-block{border-top:1px dashed #e0d0a0;border-bottom:1px dashed #e0d0a0;padding:3px 0 2px;margin:2px 0;min-height:18px}
.p-hs-line{font-size:.76em;line-height:1.5;white-space:nowrap}
.p-hs-char{font-weight:700;font-size:1.05em}
.p-hs-ss{color:#e67e22;font-size:.88em;margin-left:2px}
.p-qi-block{margin-top:3px;border-top:1px dashed #e0d0a0;padding-top:2px}
.p-qi-row{font-size:.72em;padding:1px 0}
.p-qi-lbl{color:#888;font-size:.88em}
.p-qi-val{font-weight:700;color:#16a085}
.p-qi-val.self{color:#8e44ad}
.p-ny{font-size:.7em;color:#7f8c8d;padding:1px 0 0;display:block}
.p-ny-id{font-size:.64em;color:#c8900a;display:block;padding:0 0 2px}
.kw-badge{display:inline-block;background:#fde8e8;border:1px solid #e74c3c;border-radius:3px;font-size:.62em;color:#c0392b;font-weight:700;padding:1px 4px;margin-top:2px}
/* SHEN SHA BADGE DI PILAR */
.p-ss-badges{display:flex;flex-wrap:wrap;gap:3px;justify-content:center;margin-top:3px;padding-top:3px;border-top:1px dashed #e0d0a0;min-height:16px}
.ss-badge{display:inline-flex;flex-direction:column;align-items:center;font-size:.62em;color:#fff;border-radius:3px;padding:2px 5px;cursor:help;white-space:nowrap}
.ss-badge b{font-size:1.05em;font-weight:700;line-height:1.25}
.ss-badge i{font-size:.88em;font-weight:400;font-style:italic;opacity:.88;line-height:1.2}
/* ELEMENT COLORS */
.el-wood{color:#27ae60}.el-fire{color:#c0392b}.el-earth{color:#c8900a}.el-metal{color:#7f8c8d}.el-water{color:#2980b9}
/* GAP */
.p-gap{display:flex;flex-direction:column;justify-content:space-around;align-items:center;padding:4px 0;background:#fffef0}
.board.side-b .p-gap{background:#fbfdff}
.h-badge{display:flex;flex-direction:column;align-items:center;font-size:.72em;padding:2px;cursor:help;border-radius:4px;min-width:18px;text-align:center}
.h-badge .sym{font-size:1.1em;line-height:1;font-weight:700}
.h-badge .lbl{font-size:.58em;color:#666;margin-top:1px}
.int-sheng{color:#27ae60}.int-ke{color:#c0392b}.int-bi{color:#2980b9}
.int-bg-sheng{background:rgba(39,174,96,.1);border:1px solid rgba(39,174,96,.3)}
.int-bg-ke{background:rgba(192,57,43,.1);border:1px solid rgba(192,57,43,.3)}
.int-bg-bi{background:rgba(41,128,185,.1);border:1px solid rgba(41,128,185,.3)}
/* SAN YUAN */
.san-yuan{display:flex;flex-wrap:wrap;gap:12px;padding:6px 12px;background:#fffde0;font-size:.8em;align-items:center}
.board.side-b .san-yuan{background:#f4fafe}
.sy-item{display:flex;align-items:baseline;gap:3px}
.sy-lbl{color:#8b4513;font-weight:700;margin-right:3px;white-space:nowrap;font-size:.9em}
.board.side-b .sy-lbl{color:#1a5276}
.sy-ny{color:#7f8c8d;font-size:.85em;margin-left:3px}
/* ── SHEN SHA PANEL ── */
.shensha-wrap{margin-top:10px;background:#fffef0;border:2px solid #c8a050;border-radius:4px;overflow:hidden}
.shensha-hd{background:#e8d890;padding:7px 12px;font-size:.92em;font-weight:700;color:#5d4037;border-bottom:2px solid #c8a050;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.ss-legend{display:flex;gap:12px;flex-wrap:wrap;font-weight:400;font-size:.8em}
.ss-legend span{display:inline-flex;align-items:center;gap:4px;color:#5d4037;white-space:nowrap}
.ss-legend i{width:11px;height:11px;border-radius:2px;display:inline-block}
.shensha-grid{display:grid;grid-template-columns:1fr 1fr;gap:0}
.ss-side{padding:10px 12px}
.ss-side:first-child{border-right:1px solid #d4b896}
.ss-side-title{font-size:.85em;font-weight:700;color:#8b2500;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid #e0d0a0}
.ss-side.b .ss-side-title{color:#1a5276;border-bottom-color:#bcd8ec}
.ss-row{padding:6px 0;border-bottom:1px solid #f0e8d0;font-size:.85em}
.ss-row:last-child{border-bottom:none}
.ss-row-top{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.ss-name{font-weight:700;flex-shrink:0}
.ss-latin{color:#999;font-size:.86em;font-style:italic;flex-shrink:0}
.ss-pillars{display:flex;gap:4px;flex-wrap:wrap;margin-left:auto}
.ss-pill{font-size:.85em;background:#fff3cd;border:1px solid #e0c060;border-radius:3px;padding:1px 6px;color:#5d4037;font-weight:700}
.ss-desc{color:#777;font-size:.85em;line-height:1.45;margin-top:2px}
.ss-none{color:#bbb;font-size:.85em;padding:8px 0;font-style:italic}
/* LOADING */
.loading{text-align:center;padding:30px;color:#8b4513;font-size:1em}
.spinner{display:inline-block;width:22px;height:22px;border:3px solid #c8a050;border-top-color:#8b2500;border-radius:50%;animation:spin .8s linear infinite;margin-right:8px;vertical-align:middle}
@keyframes spin{to{transform:rotate(360deg)}}
[title]{cursor:help}
/* ── MOBILE: stack atas-bawah ── */
@media(max-width:900px){
  .cmp-forms{grid-template-columns:1fr}
  .cmp-out{grid-template-columns:1fr}
  .shensha-grid{grid-template-columns:1fr}
  .ss-side:first-child{border-right:none;border-bottom:1px solid #d4b896}
  .pillars-grid{grid-template-columns:1fr 16px 1fr 16px 1fr 16px 1fr}
  .p-tg,.p-dz{font-size:2.2em}
  .ss-badge i{display:none}
}
</style>
</head>
<body>
<div class="navbar">
  <div class="nav-brand">⚖️ Compare BaZi 八字对比</div>
  <div class="nav-right">
    <span class="nav-user">👤 <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></span>
    <a href="/bazical/calculator.php">◀ Kalkulator</a>
    <a href="/bazical/logout.php">🚪 Logout</a>
  </div>
</div>

<div class="wrap">

<!-- ── FORM DUA SISI ─────────────────────────────────────────────────────── -->
<div class="cmp-forms">

  <!-- SISI A -->
  <div class="form-card side-a">
    <div class="side-title">🅰️ Orang Pertama</div>
    <div class="form-row">
      <div class="fg"><label>姓名 Nama</label>
        <input type="text" id="a_name" placeholder="Nama" value="<?= htmlspecialchars($pf['n']) ?>" style="min-width:130px"></div>
      <div class="fg"><label>出生日期 Tanggal</label>
        <input type="date" id="a_date" value="<?= $dateA ?>"></div>
      <div class="fg"><label>时 Jam</label>
        <input type="number" id="a_h" min="0" max="23" value="<?= $hA ?>" style="min-width:60px"></div>
      <div class="fg"><label>分 Menit</label>
        <input type="number" id="a_m" min="0" max="59" value="<?= $mA ?>" style="min-width:60px"></div>
      <div class="fg" style="align-self:flex-end;padding-bottom:6px">
        <label class="chk-lbl">
          <input type="checkbox" id="a_unk" onchange="toggleUnk('a',this.checked)" <?= $pf['unk'] ? 'checked' : '' ?>>
          Jam tidak diketahui
        </label>
      </div>
      <div class="fg"><label>子时 Zi Mode</label>
        <select id="a_zi" style="font-size:.82em;min-width:112px">
          <option value="late" <?= !$pf['ez'] ? 'selected' : '' ?>>夜子时 Late Zi</option>
          <option value="early" <?= $pf['ez'] ? 'selected' : '' ?>>早子时 Early Zi</option>
        </select></div>
      <div class="fg"><label>性别 JK</label>
        <select id="a_sex">
          <option value="M" <?= $pf['s']==='M' ? 'selected' : '' ?>>乾 ♂ Pria</option>
          <option value="F" <?= $pf['s']==='F' ? 'selected' : '' ?>>坤 ♀ Wanita</option>
        </select></div>
    </div>
  </div>

  <!-- SISI B -->
  <div class="form-card side-b">
    <div class="side-title">🅱️ Orang Kedua / Tanggal Pembanding</div>
    <div class="form-row">
      <div class="fg"><label>姓名 Nama</label>
        <input type="text" id="b_name" placeholder="Nama" style="min-width:130px"></div>
      <div class="fg"><label>出生日期 Tanggal</label>
        <input type="date" id="b_date" value="<?= date('Y-m-d') ?>"></div>
      <div class="fg"><label>时 Jam</label>
        <input type="number" id="b_h" min="0" max="23" value="<?= date('G') ?>" style="min-width:60px"></div>
      <div class="fg"><label>分 Menit</label>
        <input type="number" id="b_m" min="0" max="59" value="<?= (int)date('i') ?>" style="min-width:60px"></div>
      <div class="fg" style="align-self:flex-end;padding-bottom:6px">
        <label class="chk-lbl">
          <input type="checkbox" id="b_unk" onchange="toggleUnk('b',this.checked)">
          Jam tidak diketahui
        </label>
      </div>
      <div class="fg"><label>子时 Zi Mode</label>
        <select id="b_zi" style="font-size:.82em;min-width:112px">
          <option value="late">夜子时 Late Zi</option>
          <option value="early">早子时 Early Zi</option>
        </select></div>
      <div class="fg"><label>性别 JK</label>
        <select id="b_sex"><option value="M">乾 ♂ Pria</option><option value="F">坤 ♀ Wanita</option></select></div>
    </div>
  </div>

</div>

<div class="action-bar">
  <a href="/bazical/calculator.php" class="back-btn">◀ Kembali</a>
  <button class="gen-btn" id="gen_btn" onclick="generate()">⚖️ Generate 对比 ▶</button>
</div>

<div id="output"></div>
<div id="shensha_output"></div>

</div>

<script>
/* ══════════════════════════════════════════════════════════════════════════
   KONSTANTA
   ══════════════════════════════════════════════════════════════════════════ */
const EL_CLS=['el-wood','el-fire','el-earth','el-metal','el-water'];
const TG_EL=[0,0,1,1,2,2,3,3,4,4];
const DZ_EL=[4,2,0,0,2,1,1,2,3,3,2,4];
const DZ=['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
const TG=['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
function tgCls(i){return EL_CLS[TG_EL[i]]||'';}
function dzCls(i){return EL_CLS[DZ_EL[i]]||'';}
function escHtml(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

/* ══════════════════════════════════════════════════════════════════════════
   SHEN SHA 神煞 — tabel & perhitungan
   Index: TG 0-9 = 甲乙丙丁戊己庚辛壬癸 | DZ 0-11 = 子丑寅卯辰巳午未申酉戌亥
   ══════════════════════════════════════════════════════════════════════════ */

// 天乙贵人 — basis Day Stem
// 甲戊庚牛羊(丑未), 乙己鼠猴乡(子申), 丙丁猪鸡位(亥酉), 壬癸兔蛇藏(卯巳), 六辛逢马虎(午寅)
const SS_TIANYI=[[1,7],[0,8],[11,9],[11,9],[1,7],[0,8],[1,7],[6,2],[3,5],[3,5]];

// 文昌贵人 — basis Day Stem
const SS_WENCHANG=[[5],[6],[8],[9],[8],[9],[11],[0],[2],[3]];

// 羊刃 — basis Day Stem (klasik 子平: 禄前一位为阳刃, 禄后一位为阴刃)
// CATATAN: aliran lain pakai 甲卯 乙辰 丙午 丁未 戊午 己未 庚酉 辛戌 壬子 癸丑
const SS_YANGREN=[[3],[2],[6],[5],[6],[5],[9],[8],[0],[11]];

// 红艳 — basis Day Stem
const SS_HONGYAN=[[6],[8],[2],[7],[4],[4],[10],[9],[0],[8]];

// Kelompok 三合 per DZ: 0=申子辰, 1=寅午戌, 2=巳酉丑, 3=亥卯未
const SS_GROUP=[0,2,1,3,0,2,1,3,0,2,1,3];
// [驿马, 桃花, 将星, 华盖] per kelompok
const SS_GROUP_TBL=[[2,9,0,4],[8,3,6,10],[11,6,9,1],[5,0,3,7]];

// Metadata: kode → [nama CJK, latin, warna, penjelasan tooltip]
// Kategori sifat → warna badge (sekali lihat langsung terbaca)
//   ji    吉 = menguntungkan
//   zhong 中 = netral / tergantung konteks
//   xiong 凶 = perlu diwaspadai
const SS_TYPE_COLOR={ji:'#1e8449', zhong:'#c8900a', xiong:'#c0392b'};
const SS_TYPE_LABEL={ji:'吉 Jí — menguntungkan', zhong:'中 Zhōng — netral', xiong:'凶 Xiōng — waspada'};

// [0]=hanzi, [1]=pinyin bertanda nada, [2]=kategori sifat, [3]=arti,
// [4]=pinyin ringkas untuk badge di dalam pilar (ruang sempit)
const SS_META={
  tianyi   :['天乙贵人','Tiānyǐ Guìrén','ji','Bintang penolong tertinggi — bantuan dari orang berpengaruh, terhindar dari bahaya.','Tiānyǐ'],
  wenchang :['文昌贵人','Wénchāng Guìrén','ji','Bintang akademis — kecerdasan, kemampuan belajar, tulis-menulis, ujian.','Wénchāng'],
  jiangxing:['将星','Jiàng Xīng','ji','Bintang jenderal — kepemimpinan, wibawa, kemampuan memimpin banyak orang.','Jiàng Xīng'],
  yima     :['驿马','Yì Mǎ','zhong','Kuda pos — perpindahan, perjalanan, perantauan, karier yang banyak bergerak.','Yì Mǎ'],
  taohua   :['桃花','Táo Huā','zhong','Bunga persik — daya tarik, popularitas, asmara, seni dan hiburan. Berlebihan = rawan skandal.','Táo Huā'],
  huagai   :['华盖','Huá Gài','zhong','Kanopi — spiritualitas, kesendirian, bakat seni & filsafat, cenderung menyendiri.','Huá Gài'],
  hongyan  :['红艳','Hóng Yàn','zhong','Bintang asmara — pesona romantis, mudah menarik lawan jenis; rawan gosip.','Hóng Yàn'],
  yangren  :['羊刃','Yáng Rèn','xiong','Pisau kambing — ketegasan berlebih, nekat, potensi cedera; kuat namun tajam.','Yáng Rèn']
};
// Urutan tampil: 吉 dulu, lalu 中, terakhir 凶
const SS_ORDER=['tianyi','wenchang','jiangxing','yima','taohua','huagai','hongyan','yangren'];

// Label pilar (urutan array pillars dari API: 0=时 1=日 2=月 3=年)
const SLOT_LABEL=['时柱','日柱','月柱','年柱'];

/**
 * Hitung Shen Sha dari hasil bazi_calculate().
 * @param d hasil API
 * @returns {byIdx: [[kode..] x4], list: {kode:{...,pillars:[idx..]}}}
 */
function calcShenSha(d){
  const P=d.pillars;                 // [时, 日, 月, 年]
  const dTg=P[1].tg_idx;             // Day Stem
  const dDz=P[1].dz_idx;             // Day Branch
  const yDz=P[3].dz_idx;             // Year Branch

  // Pilar yang dicek — 时柱 dilewati bila jam tidak diketahui
  const slots=[];
  for(let i=0;i<4;i++){
    if(P[i].unknown||P[i].dz_idx<0)continue;
    slots.push({idx:i,dz:P[i].dz_idx});
  }

  // Target branch tiap Shen Sha
  const rowY=SS_GROUP_TBL[SS_GROUP[yDz]];
  const rowD=SS_GROUP_TBL[SS_GROUP[dDz]];
  const uniq=(a,b)=>a===b?[a]:[a,b];
  const targets={
    tianyi   :SS_TIANYI[dTg],
    wenchang :SS_WENCHANG[dTg],
    yangren  :SS_YANGREN[dTg],
    hongyan  :SS_HONGYAN[dTg],
    yima     :uniq(rowY[0],rowD[0]),
    taohua   :uniq(rowY[1],rowD[1]),
    jiangxing:uniq(rowY[2],rowD[2]),
    huagai   :uniq(rowY[3],rowD[3])
  };

  const byIdx=[[],[],[],[]];
  const list={};
  SS_ORDER.forEach(code=>{
    const tg=targets[code];
    if(!tg||!tg.length)return;
    const hit=[];
    slots.forEach(s=>{
      if(tg.indexOf(s.dz)>=0){byIdx[s.idx].push(code);hit.push(s.idx);}
    });
    if(hit.length){
      const m=SS_META[code];
      list[code]={name:m[0],latin:m[1],type:m[2],color:SS_TYPE_COLOR[m[2]],desc:m[3],pillars:hit};
    }
  });
  return {byIdx,list};
}

/* ══════════════════════════════════════════════════════════════════════════
   RENDER
   ══════════════════════════════════════════════════════════════════════════ */
function vBadge(ix){
  if(!ix)return'';
  let sym,color,lbl;
  if(ix.type==='bi'){sym='≈';color='#2980b9';lbl='比和';}
  else if(ix.type==='sheng'){sym=ix.dir==='ab'?'↓':'↑';color='#27ae60';lbl='生';}
  else{sym='✕';color='#c0392b';lbl='克';}
  return`<div class="v-badge" title="${escHtml(ix.tooltip||'')}"><span class="bs" style="color:${color}">${sym}</span><span class="bl" style="color:${color}">${lbl}</span></div>`;
}
function hBadge(ix){
  if(!ix)return`<div class="h-badge" style="opacity:.15">·</div>`;
  let sym,lbl,cls,bg;
  if(ix.type==='bi'){sym='≈';lbl='比';cls='int-bi';bg='int-bg-bi';}
  else if(ix.type==='sheng'){sym=ix.dir==='ab'?'→':'←';lbl='生';cls='int-sheng';bg='int-bg-sheng';}
  else{sym='✕';lbl='克';cls='int-ke';bg='int-bg-ke';}
  return`<div class="h-badge ${cls} ${bg}" title="${escHtml(ix.tooltip||'')}"><span class="sym">${sym}</span><span class="lbl">${lbl}</span></div>`;
}

function renderPillar(p,kw,kwY,ssCodes){
  if(p.unknown){
    return`<div class="p-col">
      <div class="p-label">${p.label}</div>
      <div class="p-ss">?</div>
      <span class="p-tg" style="color:#aaa;font-size:1.8em">?</span>
      <span class="p-tg-el" style="color:#aaa">—</span>
      <div style="margin:4px 0"></div>
      <span class="p-dz" style="color:#aaa;font-size:1.8em">?</span>
      <span class="p-dz-el" style="color:#aaa">—</span>
      <div class="p-hs-block" style="color:#bbb;font-size:.78em;padding:4px">—</div>
      <div class="p-qi-block" style="color:#bbb;font-size:.75em">Jam tidak diketahui</div>
      <span class="p-ny" style="color:#bbb">—</span>
    </div>`;
  }
  const isKwDay=kw.includes(p.dz);
  const isKwYear=kwY.includes(p.dz);
  let kwBadge='';
  if(isKwDay) kwBadge+='<div><span class="kw-badge">空亡</span></div>';
  if(isKwYear) kwBadge+='<div><span class="kw-badge" style="background:#e8f0fe;border-color:#2980b9;color:#1a5276">年空亡</span></div>';

  const hsHTML=p.hidden.map(h=>`<div class="p-hs-line">
    <span class="p-hs-char ${EL_CLS[h.el]}">${h.tg}</span>
    <span class="p-hs-ss">${h.ss}</span>
  </div>`).join('');

  const ssHTML=(ssCodes&&ssCodes.length)
    ? ssCodes.map(c=>{const m=SS_META[c];
        return`<span class="ss-badge" style="background:${SS_TYPE_COLOR[m[2]]}" title="${escHtml(m[0]+' '+m[1]+' ('+SS_TYPE_LABEL[m[2]]+') — '+m[3])}"><b>${m[0]}</b><i>${m[4]}</i></span>`;}).join('')
    : '';

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
      <div class="p-qi-row"><span class="p-qi-lbl">DM:</span> <span class="p-qi-val">${p.cs_dm.name}</span></div>
      <div class="p-qi-row"><span class="p-qi-lbl">自:</span> <span class="p-qi-val self">${p.cs_self.name}</span></div>
    </div>
    <span class="p-ny">${p.nayin.name}</span>
    <span class="p-ny-id">${p.nayin.id}</span>
    ${kwBadge}
    <div class="p-ss-badges">${ssHTML}</div>
  </div>`;
}

function kwHTML(kw){return kw.split('').map(c=>{const i=DZ.indexOf(c);return i>=0?`<span class="${dzCls(i)}">${c}</span>`:c;}).join('');}

function syItem(lbl,t){
  if(!t||t.tg==='?'){
    return`<div class="sy-item"><span class="sy-lbl">${lbl}</span><span style="color:#aaa">? ?</span></div>`;}
  return`<div class="sy-item"><span class="sy-lbl">${lbl}</span>
    <span class="${tgCls(t.tg_idx)}" style="font-weight:700">${t.tg}</span>
    <span class="${dzCls(t.dz_idx)}" style="font-weight:700">${t.dz}</span>
    <span class="sy-ny">${t.nayin.name}</span></div>`;}

function renderBoard(d,inp,side,ss){
  const kw=d.kongwang,kwY=d.kongwangYear;
  let grid='';
  d.pillars.forEach((p,i)=>{
    grid+=renderPillar(p,kw,kwY,ss.byIdx[i]);
    if(i<3) grid+=`<div class="p-gap">${hBadge(d.interactions[i].tg)}${hBadge(d.interactions[i].dz)}</div>`;
  });
  const slIdx=TG.indexOf(d.siling);
  const slHTML=d.siling?`<div class="sy-item"><span class="sy-lbl">司令:</span>
    <span class="${slIdx>=0?tgCls(slIdx):''}" style="font-weight:700">${d.siling}</span></div>`:'';
  const sexLbl=inp.sex==='M'?'乾 ♂':'坤 ♀';
  const jam=d.unknownHour?'??:??':String(inp.hour).padStart(2,'0')+':'+String(inp.min).padStart(2,'0');

  return`<div class="board ${side==='b'?'side-b':''}">
    <div class="board-hd">
      <div class="bd-left">
        <span class="bd-qian">${inp.sex==='M'?'乾':'坤'}</span>
        <div class="bd-info">
          <span class="bd-name">${escHtml(inp.name)||(side==='a'?'Orang Pertama':'Orang Kedua')} &nbsp;<span style="font-size:.8em;color:#888">${sexLbl}</span></span>
          <span class="bd-date">阳历: ${inp.year}-${String(inp.month).padStart(2,'0')}-${String(inp.day).padStart(2,'0')} ${jam} (${d.ziMode==='early'?'早子时':'夜子时'})</span>
        </div>
      </div>
      <div style="text-align:right">
        <span style="font-size:.68em;color:#888;display:block">日空亡</span>
        <span class="bd-kw-val">${kwHTML(kw)}空</span>
      </div>
    </div>
    <div class="pillars-outer"><div class="pillars-grid">${grid}</div></div>
    <div class="san-yuan">
      ${syItem('胎元:',d.taiyuan)}${syItem('命宫:',d.minggong)}${syItem('身宫:',d.shengong)}${slHTML}
      <div class="sy-item"><span class="sy-lbl">年空亡:</span><span class="bd-kw-val">${kwHTML(kwY)}空</span></div>
    </div>
  </div>`;
}

function renderShenShaSide(ss,inp,side){
  const codes=SS_ORDER.filter(c=>ss.list[c]);
  const title=escHtml(inp.name)||(side==='a'?'Orang Pertama':'Orang Kedua');
  if(!codes.length){
    return`<div class="ss-side ${side}">
      <div class="ss-side-title">${side==='a'?'🅰️':'🅱️'} ${title}</div>
      <div class="ss-none">Tidak ada Shen Sha yang terdeteksi.</div>
    </div>`;
  }
  const rows=codes.map(c=>{
    const s=ss.list[c];
    const pills=s.pillars.map(i=>`<span class="ss-pill">${SLOT_LABEL[i]}</span>`).join('');
    return`<div class="ss-row">
      <div class="ss-row-top">
        <span class="ss-name" style="color:${s.color}">${s.name}</span>
        <span class="ss-latin">${s.latin}</span>
        <span class="ss-pillars">${pills}</span>
      </div>
      <div class="ss-desc">${escHtml(s.desc)}</div>
    </div>`;
  }).join('');
  return`<div class="ss-side ${side}">
    <div class="ss-side-title">${side==='a'?'🅰️':'🅱️'} ${title} &nbsp;<span style="font-weight:400;color:#aaa;font-size:.86em">(${codes.length} Shen Sha)</span></div>
    ${rows}
  </div>`;
}

/* ══════════════════════════════════════════════════════════════════════════
   AKSI
   ══════════════════════════════════════════════════════════════════════════ */
function toggleUnk(side,unk){
  const h=document.getElementById(side+'_h');
  const m=document.getElementById(side+'_m');
  h.disabled=unk; m.disabled=unk;
  h.style.background=unk?'#f0f0f0':'';
  m.style.background=unk?'#f0f0f0':'';
}

function readSide(side){
  const dv=document.getElementById(side+'_date').value;
  if(!dv)return null;
  const[y,mo,d]=dv.split('-').map(Number);
  const unk=document.getElementById(side+'_unk').checked;
  return {
    year:y, month:mo, day:d,
    hour: unk?0:(+document.getElementById(side+'_h').value||0),
    min:  unk?0:(+document.getElementById(side+'_m').value||0),
    sex:  document.getElementById(side+'_sex').value,
    name: document.getElementById(side+'_name').value||'',
    unknownHour: unk,
    earlyZi: document.getElementById(side+'_zi').value==='early'
  };
}

async function fetchBazi(inp){
  const res=await fetch('/bazical/api/calculate.php',{
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      year:inp.year, month:inp.month, day:inp.day,
      hour:inp.hour, min:inp.min, sex:inp.sex,
      lnYear:new Date().getFullYear(),
      unknown_hour:inp.unknownHour, early_zi:inp.earlyZi
    })
  });
  if(res.status===401){window.location='/bazical/index.php?msg=session_expired';throw new Error('Session expired');}
  const data=await res.json();
  if(!data.ok)throw new Error(data.error||'Server error');
  return data;
}

async function generate(){
  const a=readSide('a'), b=readSide('b');
  if(!a||!b){alert('Tanggal kedua sisi wajib diisi.');return;}

  const out=document.getElementById('output');
  const ssOut=document.getElementById('shensha_output');
  out.innerHTML='<div class="loading"><span class="spinner"></span>Menghitung kedua BaZi...</div>';
  ssOut.innerHTML='';
  document.getElementById('gen_btn').disabled=true;

  try{
    const [dA,dB]=await Promise.all([fetchBazi(a),fetchBazi(b)]);
    const ssA=calcShenSha(dA), ssB=calcShenSha(dB);

    out.innerHTML=`<div class="cmp-out">
      ${renderBoard(dA,a,'a',ssA)}
      ${renderBoard(dB,b,'b',ssB)}
    </div>`;

    ssOut.innerHTML=`<div class="shensha-wrap">
      <div class="shensha-hd">
        <span>🌟 神煞 Shén Shà</span>
        <span class="ss-legend">
          <span><i style="background:${SS_TYPE_COLOR.ji}"></i> 吉 Jí — menguntungkan</span>
          <span><i style="background:${SS_TYPE_COLOR.zhong}"></i> 中 Zhōng — netral</span>
          <span><i style="background:${SS_TYPE_COLOR.xiong}"></i> 凶 Xiōng — waspada</span>
        </span>
      </div>
      <div class="shensha-grid">
        ${renderShenShaSide(ssA,a,'a')}
        ${renderShenShaSide(ssB,b,'b')}
      </div>
    </div>`;
  }catch(e){
    out.innerHTML=`<div class="loading" style="color:#c0392b">❌ Error: ${escHtml(e.message)}</div>`;
  }
  document.getElementById('gen_btn').disabled=false;
}

// Init: sinkronkan state checkbox unknown-hour, lalu auto-generate bila datang dari kalkulator
toggleUnk('a',document.getElementById('a_unk').checked);
toggleUnk('b',document.getElementById('b_unk').checked);
<?php if ($hasPrefill): ?>
window.onload=()=>generate();
<?php endif; ?>
</script>
</body>
</html>