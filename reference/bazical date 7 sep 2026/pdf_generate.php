<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once dirname(__FILE__) . '/api/config.php';
$user = requireLogin();

// ── INPUT ─────────────────────────────────────────────────────────────────────
$year  = (int)($_GET['y']  ?? 0);
$month = (int)($_GET['mo'] ?? 0);
$day   = (int)($_GET['d']  ?? 0);
$hour  = (int)($_GET['h']  ?? 0);
$min   = (int)($_GET['mi'] ?? 0);
$sex   = ($_GET['s'] ?? 'M') === 'F' ? 'F' : 'M';
$lnY   = (int)($_GET['ln'] ?? date('Y'));
$name  = htmlspecialchars($_GET['n'] ?? '', ENT_QUOTES);
$unk   = !empty($_GET['unk']);
$earlyZi = !empty($_GET['ez']);

if (!$year || !$month || !$day) {
    http_response_code(400); die('Parameter tidak valid');
}

// ── GET BAZI DATA (direct function call — no HTTP overhead) ───────────────────
require_once dirname(__FILE__) . '/api/calculate_core.php';

try {
    $d = bazi_calculate([
        'year'=>$year,'month'=>$month,'day'=>$day,
        'hour'=>$hour,'min'=>$min,'sex'=>$sex,
        'lnYear'=>$lnY,'unknown_hour'=>$unk,'early_zi'=>$earlyZi
    ]);
} catch (\Exception $e) {
    die('Error kalkulasi BaZi: ' . htmlspecialchars($e->getMessage()));
}

// ── HELPERS ───────────────────────────────────────────────────────────────────
$TG     = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
$DZ     = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
$TG_EL  = [0,0,1,1,2,2,3,3,4,4];
$DZ_EL  = [4,2,0,0,2,1,1,2,3,3,2,4];
$EL_NM  = ['Wood+','Wood-','Fire+','Fire-','Earth+','Earth-','Metal+','Metal-','Water+','Water-'];
$EL_SH  = ['Wood','Fire','Earth','Metal','Water'];
$EL_CSS = ['#27ae60','#c0392b','#c8900a','#7f8c8d','#2980b9'];

function tgCol($i,$ec){$m=[0,0,1,1,2,2,3,3,4,4];return $ec[$m[$i]]??'#333';}
function dzCol($i,$ec){$m=[4,2,0,0,2,1,1,2,3,3,2,4];return $ec[$m[$i]]??'#333';}

$pad2 = fn($n)=>str_pad((string)(int)$n,2,'0',STR_PAD_LEFT);
$dateStr = "$year-{$pad2($month)}-{$pad2($day)}";
$timeStr = $unk ? '??:??' : "{$pad2($hour)}:{$pad2($min)}";

// ── PILLAR HTML ───────────────────────────────────────────────────────────────
function pillarHtml($p,$kw,$kwY,$EL_NM,$EL_SH,$EL_CSS){
    if(!empty($p['unknown'])){
        return "<td style='border:1px solid #d4b896;padding:4px;text-align:center;background:#fafafa;width:22%'>
            <div style='font-size:7pt;font-weight:700;color:#5d4037;background:#e8d890;margin:-4px -4px 3px;padding:2px'>时柱</div>
            <div style='font-size:18pt;color:#ccc;line-height:1.2'>?</div>
            <div style='font-size:18pt;color:#ccc;line-height:1.2'>?</div>
            <div style='font-size:6pt;color:#bbb;margin-top:4px'>Jam tidak diketahui</div>
        </td>";
    }
    $ti=$p['tg_idx']; $di=$p['dz_idx'];
    $tc=tgCol($ti,$EL_CSS); $dc=dzCol($di,$EL_CSS);
    $isKwDay=in_array($p['dz'],$kw??[]);
    $isKwYear=in_array($p['dz'],$kwY??[]);
    $hs='';
    foreach($p['hidden']??[] as $h){
        $hc=$EL_CSS[$h['el']]??'#333';
        $hs.="<div style='font-size:5.5pt;line-height:1.5'><span style='color:{$hc};font-weight:700'>{$h['tg']}</span> <span style='color:#e67e22'>{$h['ss']}</span> <span style='color:#aaa;font-style:italic'>({$h['ss_py']})</span></div>";
    }
    $kw_badge='';
    if($isKwDay) $kw_badge.="<div style='font-size:5pt;color:#c0392b;border:0.5px solid #c0392b;border-radius:2px;padding:0 2px;background:#fde8e8;display:inline-block;margin-top:2px'>空亡</div>";
    if($isKwYear) $kw_badge.="<div style='font-size:5pt;color:#1a5276;border:0.5px solid #2980b9;border-radius:2px;padding:0 2px;background:#e8f0fe;display:inline-block;margin-top:2px'>年空亡</div>";
    $lbl=htmlspecialchars($p['label']);
    $ss=htmlspecialchars($p['ss']['name']??''); $sspy=htmlspecialchars($p['ss']['py']??'');
    $tg=htmlspecialchars($p['tg']); $dz=htmlspecialchars($p['dz']);
    $elTg=htmlspecialchars($EL_NM[$ti]??''); $elDz=htmlspecialchars($EL_SH[$p['dz_idx']<=11?([4,2,0,0,2,1,1,2,3,3,2,4][$di]??0):0]??'');
    $dm=htmlspecialchars($p['cs_dm']['name']??''); $dmp=htmlspecialchars($p['cs_dm']['py']??'');
    $sf=htmlspecialchars($p['cs_self']['name']??''); $sfp=htmlspecialchars($p['cs_self']['py']??'');
    $ny=htmlspecialchars($p['nayin']['name']??''); $nyp=htmlspecialchars($p['nayin']['py']??''); $nyi=htmlspecialchars($p['nayin']['id']??'');
    return "<td style='border:1px solid #d4b896;padding:4px;text-align:center;background:#fffef5;width:22%'>
        <div style='font-size:7pt;font-weight:700;color:#5d4037;background:#e8d890;margin:-4px -4px 3px;padding:2px'>{$lbl}</div>
        <div style='font-size:7pt;font-weight:700;color:#2980b9'>{$ss} <span style='font-size:5pt;color:#aaa;font-style:italic'>{$sspy}</span></div>
        <div style='font-size:22pt;font-weight:900;color:{$tc};line-height:1.1'>{$tg}</div>
        <div style='font-size:5.5pt;color:{$tc}'>{$elTg}</div>
        <div style='font-size:22pt;font-weight:900;color:{$dc};line-height:1.1'>{$dz}</div>
        <div style='font-size:5.5pt;color:{$dc}'>{$elDz}</div>
        <div style='border-top:0.5px dashed #ccc;padding-top:2px;margin-top:2px'>{$hs}</div>
        <div style='border-top:0.5px dashed #ccc;padding-top:2px;margin-top:2px;font-size:5.5pt'>
            <div style='color:#16a085;font-weight:700'>DM: {$dm} <span style='color:#aaa;font-style:italic'>{$dmp}</span></div>
            <div style='color:#8e44ad;font-weight:700'>自: {$sf} <span style='color:#aaa;font-style:italic'>{$sfp}</span></div>
        </div>
        <div style='border-top:0.5px dashed #ccc;padding-top:2px;margin-top:2px;font-size:5pt;color:#7f8c8d'>{$ny} <span style='color:#aaa'>{$nyp}</span><br><span style='color:#c8900a'>{$nyi}</span></div>
        {$kw_badge}
    </td>";
}

function syItemHtml($lbl,$t,$EL_CSS){
    if(($t['tg']??'?')==='?')
        return "<span style='margin-right:20pt'><b style='color:#8b4513'>{$lbl}</b> <span style='color:#aaa'>? ?</span></span>";
    $tc=tgCol($t['tg_idx']??0,$EL_CSS); $dc=dzCol($t['dz_idx']??0,$EL_CSS);
    $tg=htmlspecialchars($t['tg']??''); $dz=htmlspecialchars($t['dz']??'');
    $ny=htmlspecialchars($t['nayin']['name']??''); $nyp=htmlspecialchars($t['nayin']['py']??'');
    return "<span style='margin-right:20pt'><b style='color:#8b4513'>{$lbl}</b>
        <span style='color:{$tc};font-weight:700'>{$tg}</span><span style='color:{$dc};font-weight:700'>{$dz}</span>
        <span style='color:#888;font-size:5.5pt'> {$ny} ({$nyp})</span></span>";
}

// ── BUILD HTML ────────────────────────────────────────────────────────────────
$pillars  = $d['pillars'];
$dy       = $d['dayun'];
$kw       = is_array($d['kongwang']??'') ? $d['kongwang'] : mb_str_split($d['kongwang']??'');
$kwY      = is_array($d['kongwangYear']??'') ? $d['kongwangYear'] : mb_str_split($d['kongwangYear']??'');
$siling   = $d['siling']??'';

// Pillars
$pillarsHtml='';
foreach($pillars as $p) $pillarsHtml.=pillarHtml($p,$kw,$kwY,$EL_NM,$EL_SH,$EL_CSS);

// KW
function kwHtml($arr,$DZ,$EL_CSS){
    $out='';
    foreach($arr??[] as $c){$i=array_search($c,$DZ);$col=$i!==false?dzCol($i,$EL_CSS):'#333';$out.="<span style='color:{$col};font-weight:700'>{$c}</span>";}
    return $out.'空';
}

// San Yuan
$sanYuan = syItemHtml('胎元:',$d['taiyuan']??[],$EL_CSS)
         . '&nbsp;&nbsp;&nbsp;' . syItemHtml('命宫:',$d['minggong']??[],$EL_CSS)
         . '&nbsp;&nbsp;&nbsp;' . syItemHtml('身宫:',$d['shengong']??[],$EL_CSS);
if($siling){
    $si=array_search($siling,$TG);
    $sc=$si!==false?tgCol($si,$EL_CSS):'#333';
    $sanYuan.="&nbsp;&nbsp;&nbsp;<span style='margin-right:20pt'><b style='color:#8b4513'>司令:</b> <span style='color:{$sc};font-weight:700'>{$siling}</span></span>";
}
// Kong Wang di sebelah kanan Si Ling
$sanYuan.="&nbsp;&nbsp;&nbsp;<span style='margin-right:20pt'><b style='color:#8b4513'>日空亡:</b> <b>".kwHtml($kw,$DZ,$EL_CSS)."</b></span>";
$sanYuan.="&nbsp;&nbsp;&nbsp;<span><b style='color:#8b4513'>年空亡:</b> <b>".kwHtml($kwY,$DZ,$EL_CSS)."</b></span>";

// Da Yun Table
$cols=array_slice($dy['list']??[],0,10);
$activeIdx=-1;
foreach($cols as $i=>$col){
    if($i===0)continue;
    $nx=$cols[$i+1]??null;
    if(($col['calYear']??0)<=$lnY&&(!$nx||($nx['calYear']??0)>$lnY)){$activeIdx=$i;break;}
}

function ccBg($i,$activeIdx,$col){
    if($i===$activeIdx)return'#fff3cd';
    return($col['isBirth']??false)?'#fffde7':'#ffffff';
}

$thRow='<th style="background:#c8a050;color:#fff;border:0.5px solid #b89040;padding:2px 3px;font-size:5.5pt;white-space:nowrap">栏位</th>';
foreach($cols as $i=>$col){
    $bg=ccBg($i,$activeIdx,$col);
    $lb=($col['isBirth']??false)?'命造':'大运'.$i;
    $thRow.="<th style='background:{$bg};border:0.5px solid #d4b896;padding:2px 3px;font-size:5.5pt;text-align:center'>{$lb}</th>";
}

function dyRow($lbl,$cols,$activeIdx,$fn){
    $r="<tr><td style='background:#e8d890;font-weight:700;color:#5d4037;font-size:5.5pt;white-space:nowrap;border:0.5px solid #d4b896;padding:1px 3px'>{$lbl}</td>";
    foreach($cols as $i=>$col){
        $bg=ccBg($i,$activeIdx,$col);
        $r.="<td style='border:0.5px solid #d4b896;padding:1px 2px;text-align:center;vertical-align:middle;background:{$bg}'>".$fn($col,$i)."</td>";
    }
    return $r.'</tr>';
}

$tb='';
$tb.=dyRow('十神',$cols,$activeIdx,fn($c,$i)=>($c['isBirth']??false)?"<span style='color:#888;font-size:5pt'>生月</span>":"<span style='font-size:6pt;color:#2980b9;font-weight:700'>".htmlspecialchars($c['ss']??'')."</span>");
$tb.=dyRow('天干',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:13pt;font-weight:900;color:".tgCol($c['tg_idx']??0,$GLOBALS['EL_CSS'])."'>".htmlspecialchars($c['tg']??'')."</span>");
$tb.=dyRow('地支',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:13pt;font-weight:900;color:".dzCol($c['dz_idx']??0,$GLOBALS['EL_CSS'])."'>".htmlspecialchars($c['dz']??'')."</span>");
$tb.=dyRow('长生',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:5.5pt;color:#888'>".htmlspecialchars($c['cs_dm']['name']??'')."</span>");
$tb.=dyRow('纳音',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:5.5pt;color:#888'>".htmlspecialchars($c['nayin']['name']??'')."</span>");
$tb.=dyRow('虚岁',$cols,$activeIdx,fn($c,$i)=>($c['isBirth']??false)?"<span style='font-size:6pt;color:#c0392b;font-weight:700'>1岁</span>":"<span style='font-size:6pt;color:#c0392b;font-weight:700'>".(ceil($c['startAge']??0)+1)."岁</span>");
$tb.=dyRow('交运',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:6pt;color:#2980b9'>".htmlspecialchars($c['calYear']??'')."</span>");
$tb.=dyRow('日期',$cols,$activeIdx,fn($c,$i)=>"<span style='font-size:5.5pt;color:#888'>".htmlspecialchars(($c['calMo']??'').'/'.($c['calDay']??''))."</span>");

for($li=0;$li<10;$li++){
    $tb.="<tr><td style='background:#e8d890;font-weight:700;color:#5d4037;font-size:5.5pt;white-space:nowrap;border:0.5px solid #d4b896;padding:1px 3px'>第".($li+1)."年</td>";
    foreach($cols as $i=>$col){
        $bg=ccBg($i,$activeIdx,$col);
        if(isset($col['ln'][$li])){
            $ln=$col['ln'][$li]; $isNow=($ln['year']??0)==$lnY;
            $lnBg=$isNow?'#fff3cd':$bg;
            $tc2=tgCol($ln['tg_idx']??0,$EL_CSS); $dc2=dzCol($ln['dz_idx']??0,$EL_CSS);
            $tb.="<td style='border:0.5px solid #d4b896;padding:1px 2px;text-align:center;background:{$lnBg}'>
                <span style='font-size:5.5pt;color:#999'>".($ln['year']??'')."</span>
                <span style='font-size:5.5pt;font-weight:700;color:{$tc2}'>".htmlspecialchars($ln['tg']??'')."</span>
                <span style='font-size:5.5pt;font-weight:700;color:{$dc2}'>".htmlspecialchars($ln['dz']??'')."</span>
            </td>";
        } else {
            $tb.="<td style='border:0.5px solid #d4b896;background:{$bg}'></td>";
        }
    }
    $tb.='</tr>';
}
$tb.=dyRow('止于',$cols,$activeIdx,fn($c,$i)=>isset($GLOBALS['cols'][$i+1])?"<span style='font-size:5.5pt;color:#888'>".($GLOBALS['cols'][$i+1]['calYear']-1)."</span>":'—');
$GLOBALS['cols']=$cols;

$sexLabel=$sex==='F'?'坤 &#9792; Wanita':'乾 &#9794; Pria';
$printDate=date('d F Y');
$dyInfo="出生后约 {$dy['ay']}年{$dy['am']}月{$dy['ad']}天 起运 (".($dy['fwd']?'顺行':'逆行').") · 实际起运: {$dy['startYear']}-{$dy['startMo']}-{$dy['startDay']}";

// ── FULL HTML ─────────────────────────────────────────────────────────────────
$html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:DejaVu Sans,Arial Unicode MS,Arial,sans-serif;font-size:8pt;margin:0;padding:0'>

<table width='100%' style='border-bottom:2px solid #8b2500;margin-bottom:5px;padding-bottom:3px;border-collapse:collapse'>
<tr>
  <td>
    <div style='font-size:9.5pt;font-weight:900;color:#8b2500;letter-spacing:1px'>DESTINY READING -- 四柱八字 BaZi Chart</div>
    <div style='font-size:12pt;font-weight:700;color:#222'>{$name} &nbsp;<span style='font-size:7pt;color:#888'>{$sexLabel}</span></div>
$ziLabel = $earlyZi ? '早子时' : '夜子时';
    <div style='font-size:6.5pt;color:#666'>阳历: {$dateStr} {$timeStr} ({$ziLabel}) &nbsp;&middot;&nbsp; ".htmlspecialchars($d['lcNote']??'')."</div>
  </td>
  <td style='text-align:right;font-size:6.5pt;color:#555;line-height:1.8'>
    <div style='font-size:5.5pt;color:#aaa'>{$printDate}</div>
    <div style='font-size:5.5pt;color:#aaa'>Dicetak oleh: ".htmlspecialchars($user['full_name']??$user['username']??'')."</div>
  </td>
</tr>
</table>

<table width='100%' style='border-collapse:collapse;margin-bottom:5px'>
<tr>{$pillarsHtml}</tr>
</table>

<div style='font-size:6.5pt;border-top:1px solid #ddd;border-bottom:1px solid #ddd;padding:3px 0;margin-bottom:5px'>
  {$sanYuan}
</div>

<div style='font-size:6.5pt;font-weight:700;color:#1a5276;background:#d6eaf8;padding:2px 4px;margin-bottom:3px;border-radius:2px'>
  大运 Da Yun -- {$dyInfo}
</div>
<table width='100%' style='border-collapse:collapse;font-size:6pt'>
  <thead><tr>{$thRow}</tr></thead>
  <tbody>{$tb}</tbody>
</table>

</body></html>";

// ── GENERATE PDF ──────────────────────────────────────────────────────────────
require_once dirname(__FILE__) . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode'            => 'utf-8',
    'format'          => 'A4-L',
    'margin_top'      => 8,
    'margin_bottom'   => 8,
    'margin_left'     => 8,
    'margin_right'    => 8,
    'default_font'    => 'dejavusans',
    'tempDir'         => sys_get_temp_dir(),
    'autoLangToFont'  => true,   // auto-detect CJK and switch font
    'autoScriptToLang'=> true,   // auto-detect script (Han, Latin, etc.)
]);

$mpdf->SetTitle("BaZi Chart - {$name}");
$mpdf->SetAuthor('Destiny Reading');
$mpdf->WriteHTML($html);

$fname = 'BaZi_'.preg_replace('/[^a-zA-Z0-9]/', '_', $name ?: 'Chart').'_'.$dateStr.'.pdf';
$mpdf->Output($fname, 'D');