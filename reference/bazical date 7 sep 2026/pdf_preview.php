<?php
require_once dirname(__FILE__) . '/api/config.php';
$user = requireLogin();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=1200">
<title>BaZi PDF</title>
<style>
/* ── SCREEN: preview window ── */
body{font-family:'Microsoft YaHei','SimHei',Arial,sans-serif;background:#888;margin:0;padding:20px}
.preview-bar{background:#333;color:#fff;padding:8px 16px;display:flex;gap:12px;align-items:center;position:fixed;top:0;left:0;right:0;z-index:99;font-size:.85em}
.preview-bar button{padding:6px 18px;border:none;border-radius:4px;cursor:pointer;font-weight:700}
.btn-print{background:#c0392b;color:#fff}
.btn-close{background:#555;color:#fff}
.page-wrap{margin-top:48px;display:flex;justify-content:center}

/* ── PAGE ── */
.page{
  width:277mm; height:190mm;
  background:#fff;
  padding:5mm 6mm;
  box-shadow:0 4px 20px rgba(0,0,0,.4);
  display:grid;
  grid-template-rows:auto auto 1fr auto;
  gap:2mm;
  overflow:hidden;
  position:relative;
}

/* ── HEADER ── */
.hdr{display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #8b2500;padding-bottom:2mm}
.hdr-left{display:flex;align-items:center;gap:4mm}
.hdr-title{font-size:10pt;font-weight:900;color:#8b2500;letter-spacing:1px}
.hdr-name{font-size:13pt;font-weight:700;color:#222}
.hdr-sub{font-size:7pt;color:#666;margin-top:1px}
.hdr-right{text-align:right;font-size:7pt;color:#555;line-height:1.6}
.kw-val{font-weight:700;font-size:8pt}

/* ── 4 PILLARS ── */
.pillars-section{display:grid;grid-template-columns:repeat(4,1fr);gap:2mm;border-bottom:1px solid #ddd;padding-bottom:2mm}
.p-col{border:1px solid #d4b896;border-radius:3px;padding:1.5mm 2mm;text-align:center;background:#fffef5;position:relative}
.p-lbl{font-size:6.5pt;font-weight:700;color:#5d4037;background:#e8d890;margin:-1.5mm -2mm 1.5mm;padding:1mm 0;border-radius:2px 2px 0 0}
.p-ss{font-size:6.5pt;font-weight:700;color:#2980b9;margin-bottom:0.5mm}
.p-ss.main{color:#c0392b}
.p-ss-py{font-size:5pt;color:#aaa;display:block;font-style:italic}
.p-tg{font-size:22pt;font-weight:900;line-height:1;display:block;margin:0.5mm 0}
.p-el{font-size:5.5pt;color:#888;display:block;margin-bottom:0.5mm}
.p-dz{font-size:22pt;font-weight:900;line-height:1;display:block;margin:0.5mm 0}
.p-hs{border-top:0.5px dashed #ccc;padding-top:1mm;margin-top:1mm;font-size:6pt;line-height:1.5}
.p-hs-line{white-space:nowrap}
.p-qi{border-top:0.5px dashed #ccc;padding-top:0.5mm;margin-top:0.5mm;font-size:5.5pt;line-height:1.5}
.p-qi-row{color:#555}
.p-qi-dm{color:#16a085;font-weight:700}
.p-qi-self{color:#8e44ad;font-weight:700}
.p-ny{font-size:5.5pt;color:#7f8c8d;border-top:0.5px dashed #ccc;padding-top:0.5mm;margin-top:0.5mm}
.kw-badge{font-size:5pt;color:#c0392b;border:0.5px solid #c0392b;border-radius:2px;padding:0 2px;background:#fde8e8;display:inline-block}

/* GAP interactions — aligned to TG and DZ rows */
.pillars-with-gaps{display:grid;grid-template-columns:1fr 7mm 1fr 7mm 1fr 7mm 1fr;gap:1mm;border-bottom:1px solid #ddd;padding-bottom:2mm}
.p-gap-col{display:flex;flex-direction:column;align-items:center;padding-top:0;min-height:50mm}
.p-gap-col .gap-tg-space{flex:0 0 auto} /* spacer to align with TG */
.p-gap-col .gap-dz-space{flex:0 0 auto} /* spacer to align with DZ */
.ix-badge{font-size:6pt;font-weight:700;text-align:center;line-height:1.2;border-radius:2px;padding:1px 2px;width:100%;display:block}
.ix-badge-tg{margin-bottom:1mm}
.ix-badge-dz{margin-top:1mm}
.ix-sheng{color:#27ae60;background:rgba(39,174,96,.1);border:0.5px solid rgba(39,174,96,.4)}
.ix-ke{color:#c0392b;background:rgba(192,57,43,.1);border:0.5px solid rgba(192,57,43,.4)}
.ix-bi{color:#2980b9;background:rgba(41,128,185,.1);border:0.5px solid rgba(41,128,185,.3)}
.gap-label{font-size:4.5pt;color:#aaa;text-align:center;display:block;line-height:1.2}

/* ── SAN YUAN ── */
.san-yuan{display:flex;gap:4mm;align-items:center;font-size:6.5pt;padding:1mm 0;border-bottom:1px solid #ddd}
.sy-item{display:flex;align-items:center;gap:1.5mm}
.sy-lbl{color:#8b4513;font-weight:700}
.sy-val{font-weight:700}
.sy-ny{color:#888;font-size:5.5pt}

/* ── DA YUN TABLE ── */
.dy-section{overflow:hidden}
.dy-title{font-size:7pt;font-weight:700;color:#1a5276;background:#d6eaf8;padding:1mm 2mm;margin-bottom:1.5mm;border-radius:2px}
.dy-tbl{border-collapse:collapse;width:100%;font-size:6pt}
.dy-tbl th{background:#e8d890;color:#5d4037;border:0.5px solid #c8a050;padding:1mm 1.5mm;text-align:center;white-space:nowrap;font-size:5.5pt;font-weight:700}
.dy-tbl td{border:0.5px solid #d4b896;padding:0.8mm 1mm;text-align:center;vertical-align:top}
.row-hd{background:#e8d890;font-weight:700;color:#5d4037;font-size:5.5pt;white-space:nowrap}
.dc-tg{font-size:11pt;font-weight:900;display:block;line-height:1.1}
.dc-dz{font-size:11pt;font-weight:900;display:block;line-height:1.1}
.dc-sm{font-size:5pt;color:#888;display:block}
.col-birth{background:#fffde7}
.col-act{background:#fff3cd}
.ln-cell{font-size:5pt;line-height:1.4;border-bottom:0.3px solid #eee}
.ln-cell:last-child{border-bottom:none}
.ln-yr{color:#999}
.ln-now{background:#fff3cd;border-radius:1px}
.ln-tg{font-weight:700}
.ln-dz{font-weight:700}

/* ELEMENT COLORS */
.el-wood{color:#27ae60}.el-fire{color:#c0392b}.el-earth{color:#c8900a}.el-metal{color:#7f8c8d}.el-water{color:#2980b9}

/* ── PRINT ── */
@page{ size:A4 landscape; margin:0 }
@media print{
  *{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
  html{margin:0;padding:0}
  body{margin:0;padding:0;background:#fff!important}
  .preview-bar{display:none!important}
  .page-wrap{margin:0;padding:0;display:block}
  .page{
    box-shadow:none!important;
    width:277mm;
    height:190mm;
    margin:0;
    padding:5mm 6mm;
    overflow:hidden;
    page-break-after:avoid;
    page-break-inside:avoid;
    break-after:avoid;
    break-inside:avoid;
  }
}
</style>
</head>
<body>

<div class="preview-bar">
  <button class="btn-print" onclick="doPrint()">🖨️ Print / Save as PDF</button>
  <button class="btn-close" onclick="window.close()">✕ Tutup</button>
  <span style="color:#aaa;font-size:.78em">⚠️ Di dialog print: <b>More settings</b> → Layout: <b>Landscape</b> → Margins: <b>None</b> → Scale: <b>100%</b></span>
</div>

<div class="page-wrap">
<div class="page" id="bazi-page">
  <div style="text-align:center;padding:20px;color:#888">Loading...</div>
</div>
</div>

<script>
const EL_CLS=['el-wood','el-fire','el-earth','el-metal','el-water'];
const TG_EL=[0,0,1,1,2,2,3,3,4,4];
const DZ_EL=[4,2,0,0,2,1,1,2,3,3,2,4];
const EL_NM=['Wood+','Wood-','Fire+','Fire-','Earth+','Earth-','Metal+','Metal-','Water+','Water-'];
const EL_SHORT=['Wood','Fire','Earth','Metal','Water'];
const DZ=['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
const TG=['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];

function tgCls(i){return EL_CLS[TG_EL[i]]||'';}
function dzCls(i){return EL_CLS[DZ_EL[i]]||'';}

function ixBadge(ix, isVertical){
  if(!ix) return '';
  let sym,cls,lbl;
  if(ix.type==='bi'){sym='≈';cls='ix-bi';lbl='比';}
  else if(ix.type==='sheng'){
    if(isVertical) sym=ix.dir==='ab'?'↓':'↑';
    else sym=ix.dir==='ab'?'→':'←';
    cls='ix-sheng';lbl='生';
  } else {sym='✕';cls='ix-ke';lbl='克';}
  return`<div class="ix-badge ${cls}" title="${ix.tooltip||''}">${sym}<br>${lbl}</div>`;
}

function ixBadgeHTML(ix, type){
  if(!ix) return '';
  let sym,cls,lbl;
  if(ix.type==='bi'){sym='≈';cls='ix-bi';lbl='比';}
  else if(ix.type==='sheng'){sym=ix.dir==='ab'?'→':'←';cls='ix-sheng';lbl='生';}
  else{sym='✕';cls='ix-ke';lbl='克';}
  return`<div class="ix-badge ${cls} ix-badge-${type}" title="${ix.tooltip||''}">${sym}<br>${lbl}</div>`;
}

function renderPillar(p, kw, showGap, gapData){
  const isKw = kw.includes(p.dz);
  const hsLines = p.hidden.map(h=>
    `<div class="p-hs-line"><span class="${EL_CLS[h.el]}">${h.tg}</span> <span style="color:#e67e22">${h.ss}</span> <span style="color:#aaa;font-style:italic;font-size:5pt">(${h.ss_py})</span></div>`
  ).join('');
  const vIx = p.v_interaction ? ixBadge(p.v_interaction, true) : '';
  return`<div class="p-col">
    <div class="p-lbl">${p.label}</div>
    <div class="p-ss${p.ss.name==='主'?' main':''}">${p.ss.name}<span class="p-ss-py">${p.ss.py}</span></div>
    <span class="p-tg ${tgCls(p.tg_idx)}">${p.tg}</span>
    <span class="p-el ${tgCls(p.tg_idx)}">${EL_NM[p.tg_idx]}</span>
    ${vIx ? `<div style="margin:0.5mm 0">${vIx}</div>` : ''}
    <span class="p-dz ${dzCls(p.dz_idx)}">${p.dz}</span>
    <span class="p-el ${dzCls(p.dz_idx)}">${EL_SHORT[p.el_dz]}</span>
    <div class="p-hs">${hsLines}</div>
    <div class="p-qi">
      <div class="p-qi-row">DM: <span class="p-qi-dm">${p.cs_dm.name}</span> <span style="color:#aaa;font-size:5pt">${p.cs_dm.py}</span></div>
      <div class="p-qi-row">自: <span class="p-qi-self">${p.cs_self.name}</span> <span style="color:#aaa;font-size:5pt">${p.cs_self.py}</span></div>
    </div>
    <div class="p-ny"><span style="color:#7f8c8d">${p.nayin.name}</span> <span style="color:#aaa;font-size:5pt">${p.nayin.py}</span><br><span style="color:#c8900a;font-size:5pt">${p.nayin.id}</span></div>
    ${isKw?'<div style="margin-top:0.5mm"><span class="kw-badge">空亡</span></div>':''}
  </div>`;
}

function render(d, meta){
  const pillars=d.pillars, ixs=d.interactions, dy=d.dayun, lnY=d.lnYear;
  const kw=d.kongwang, kwY=d.kongwangYear;

  // KW colored
  function kwHTML(k){return k.split('').map(c=>{const i=DZ.indexOf(c);return i>=0?`<span class="${dzCls(i)}">${c}</span>`:c;}).join('');}

  // San yuan item
  function syItem(lbl,t){
    return`<div class="sy-item">
      <span class="sy-lbl">${lbl}</span>
      <span class="sy-val ${tgCls(t.tg_idx)}">${t.tg}</span><span class="sy-val ${dzCls(t.dz_idx)}">${t.dz}</span>
      <span class="sy-ny">${t.nayin.name} (${t.nayin.py})</span>
    </div>`;}

  // Pillars with gap columns — TG badge aligned to TG row, DZ badge to DZ row
  let pillarsHTML='';
  pillars.forEach((p,i)=>{
    pillarsHTML += renderPillar(p, kw, i<3, ixs[i]);
    if(i<3){
      const ix=ixs[i];
      const tgBadge = ix.tg ? ixBadgeHTML(ix.tg,'tg') : `<div style="height:5mm"></div>`;
      const dzBadge = ix.dz ? ixBadgeHTML(ix.dz,'dz') : '';
      pillarsHTML+=`<div class="p-gap-col">
        <div style="height:20mm"></div>
        <span class="gap-label">BL</span>
        ${tgBadge}
        <div style="flex:1"></div>
        <span class="gap-label">CB</span>
        ${dzBadge}
        <div style="height:2mm"></div>
      </div>`;
    }
  });

  // Da Yun table — birth col + DY 1..8
  const cols = dy.list.slice(0, 9); // birth + 8
  const activeDyIdx = cols.findIndex((_,i)=>{
    if(i===0)return false; const nx=cols[i+1];
    return _.calYear<=lnY&&(!nx||nx.calYear>lnY);});

  function cc(i){return i===activeDyIdx?' col-act':(cols[i].isBirth?' col-birth':'');}

  let th=`<thead><tr><th class="row-hd">栏位</th>`;
  cols.forEach((_,i)=>{th+=`<th class="${cc(i)}">${_.isBirth?'命造':'大运'+i}</th>`;});
  th+='</tr></thead>';

  function mkRow(lbl,fn){
    let r=`<tr><td class="row-hd">${lbl}</td>`;
    cols.forEach((c,i)=>{r+=`<td class="${cc(i)}">${fn(c,i)}</td>`;});
    return r+'</tr>';}

  let tb='<tbody>';
  tb+=mkRow('十神',(c)=>c.isBirth?'<span style="color:#888;font-size:5pt">生月</span>':`<span style="font-size:6pt;color:#2980b9;font-weight:700">${c.ss||''}</span>`);
  tb+=mkRow('天干',(c)=>`<span class="dc-tg ${tgCls(c.tg_idx)}">${c.tg}</span>`);
  tb+=mkRow('地支',(c)=>`<span class="dc-dz ${dzCls(c.dz_idx)}">${c.dz}</span>`);
  tb+=mkRow('长生',(c)=>`<span class="dc-sm">${c.cs_dm?.name||''}</span>`);
  tb+=mkRow('纳音',(c)=>`<span class="dc-sm">${c.nayin?.name||''}</span>`);
  tb+=mkRow('虚岁',(c)=>`<span style="font-size:6pt;color:#c0392b;font-weight:700">${c.isBirth?'1岁':(Math.ceil(c.startAge)+1)+'岁'}</span>`);
  tb+=mkRow('交运',(c)=>`<span style="font-size:6pt;color:#2980b9">${c.calYear}</span>`);
  tb+=mkRow('日期',(c)=>`<span style="font-size:5.5pt;color:#888">${c.calMo}/${c.calDay}</span>`);

  // Liu Nian rows — 10 years per DY
  for(let li=0;li<10;li++){
    tb+=`<tr><td class="row-hd">第${li+1}年</td>`;
    cols.forEach((col,i)=>{
      const cc2=cc(i);
      if(li<col.ln.length){
        const ln=col.ln[li];const isNow=ln.year===lnY;
        tb+=`<td class="${cc2}"><div class="ln-cell${isNow?' ln-now':''}">
          <span class="ln-yr">${ln.year} </span>
          <span class="ln-tg ${tgCls(ln.tg_idx)}">${ln.tg}</span>
          <span class="ln-dz ${dzCls(ln.dz_idx)}">${ln.dz}</span>
        </div></td>`;
      }else tb+=`<td class="${cc2}"></td>`;});
    tb+='</tr>';}

  tb+=mkRow('止于',(c,i)=>{const nx=cols[i+1];return nx?`<span style="font-size:5.5pt;color:#888">${nx.calYear-1}</span>`:'—';});
  tb+='</tbody>';

  // Si Ling
  const slIdx=TG.indexOf(d.siling);

  document.getElementById('bazi-page').innerHTML=`
  <!-- HEADER -->
  <div class="hdr">
    <div class="hdr-left">
      <div>
        <div class="hdr-title">🔮 DESTINY READING — 四柱八字 BaZi Chart</div>
        <div class="hdr-name">${meta.name||'—'} &nbsp;<span style="font-size:8pt;color:#888">${meta.sex==='M'?'乾 ♂':'坤 ♀'}</span></div>
        <div class="hdr-sub">阳历: ${meta.date} ${meta.time} &nbsp;·&nbsp; ${d.lcNote}</div>
      </div>
    </div>
    <div class="hdr-right">
      <div>日空亡 (Rì Kōng Wáng): <span class="kw-val">${kwHTML(kw)}空</span></div>
      <div>年空亡 (Nián Kōng Wáng): <span class="kw-val">${kwHTML(kwY)}空</span></div>
      <div style="margin-top:1mm;font-size:6pt">Printed: ${new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})}</div>
    </div>
  </div>

  <!-- 4 PILLARS with gaps -->
  <div class="pillars-with-gaps">${pillarsHTML}</div>

  <!-- SAN YUAN -->
  <div class="san-yuan">
    ${syItem('胎元 Tāi Yuán:',d.taiyuan)}
    ${syItem('命宫 Mìng Gōng:',d.minggong)}
    ${syItem('身宫 Shēn Gōng:',d.shengong)}
    ${d.siling?`<div class="sy-item"><span class="sy-lbl">司令 Sī Lìng:</span> <span class="sy-val ${slIdx>=0?tgCls(slIdx):''}">${d.siling}</span></div>`:''}
  </div>

  <!-- DA YUN -->
  <div class="dy-section">
    <div class="dy-title">⏳ 大运 Da Yun — 出生后约 ${dy.ay}年${dy.am}月${dy.ad}天 起运 (${dy.fwd?'顺行 →':'逆行 ←'}) &nbsp;·&nbsp; 实际起运: ${dy.startYear}-${dy.startMo}-${dy.startDay}</div>
    <div style="overflow:hidden"><table class="dy-tbl">${th+tb}</table></div>
  </div>
  `;
}

// ── LOAD DATA ──
window.onload = () => {
  const params = new URLSearchParams(window.location.search);
  const year  = +params.get('y')  || 0;
  const month = +params.get('mo') || 0;
  const day   = +params.get('d')  || 0;
  const hour  = +params.get('h')  || 0;
  const min   = +params.get('mi') || 0;
  const sex   = params.get('s')   || 'M';
  const lnY   = +params.get('ln') || new Date().getFullYear();
  const name  = decodeURIComponent(params.get('n') || '');

  const pad2 = n => String(n).padStart(2,'0');
  const meta = {
    name, sex,
    date: `${year}-${pad2(month)}-${pad2(day)}`,
    time: `${pad2(hour)}:${pad2(min)}`
  };

  fetch('/bazical/api/calculate.php', {
    method:'POST', credentials:'include',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({year,month,day,hour,min,sex,lnYear:lnY})
  })
  .then(r=>{if(r.status===401){window.location='/bazical/index.php?msg=session_expired';return null;}return r.json();})
  .then(data=>{if(data&&data.ok) render(data,meta);else document.getElementById('bazi-page').innerHTML='<div style="padding:20px;color:red">Error loading data</div>';})
  .catch(e=>{document.getElementById('bazi-page').innerHTML=`<div style="padding:20px;color:red">Error: ${e.message}</div>`;});
};

function doPrint(){
  // Add landscape style right before printing
  let s=document.getElementById('print-landscape');
  if(!s){
    s=document.createElement('style');
    s.id='print-landscape';
    document.head.appendChild(s);
  }
  s.textContent='@page{size:A4 landscape!important;margin:0!important}';
  setTimeout(()=>window.print(), 50);
}
</script>
</body>
</html>