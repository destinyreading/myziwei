<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

$base = dirname(__FILE__);
require_once $base . '/config.php';
require_once $base . '/jiedata.php';

// ── BAZI DATA ──
// BaZi constants and lookup data

$TG     = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
$DZ     = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
$SS     = ['比肩','劫财','食神','伤官','偏财','正财','七杀','正官','偏印','正印'];
$SS_PY  = ['Bǐ Jiān','Jié Cái','Shí Shén','Shāng Guān','Piān Cái','Zhèng Cái','Qī Shā','Zhèng Guān','Piān Yìn','Zhèng Yìn'];
$CS_NM  = ['长生','沐浴','冠带','临官','帝旺','衰','病','死','墓','绝','胎','养'];
$CS_PY  = ['Cháng Shēng','Mù Yù','Guān Dài','Lín Guān','Dì Wàng','Shuāi','Bìng','Sǐ','Mù','Jué','Tāi','Yǎng'];
$CS_EN  = ['Prosperous Growth','Bath','Coming of Age','Officer','Prosperous Peak','Decline','Sickness','Death','Tomb','Void','Embryo','Nourish'];
$CS_ST  = [11,6,2,9,2,9,5,0,8,3];
$TG_EL  = [0,0,1,1,2,2,3,3,4,4];
$DZ_EL  = [4,2,0,0,2,1,1,2,3,3,2,4];
$EL_ZH  = ['木','火','土','金','水'];
$EL_NM  = ['Wood','Fire','Earth','Metal','Water'];
$SHENG  = [1,2,3,4,0];
$KE     = [2,3,4,0,1];

$NY = [
'海中金','海中金','炉中火','炉中火','大林木','大林木','路旁土','路旁土','剑锋金','剑锋金',
'山头火','山头火','涧下水','涧下水','城墙土','城墙土','白蜡金','白蜡金','杨柳木','杨柳木',
'泉中水','泉中水','屋上土','屋上土','霹雳火','霹雳火','松柏木','松柏木','长流水','长流水',
'沙中金','沙中金','山下火','山下火','平地木','平地木','壁上土','壁上土','金箔金','金箔金',
'覆灯火','覆灯火','天河水','天河水','大驿土','大驿土','钗钏金','钗钏金','桑柘木','桑柘木',
'大溪水','大溪水','沙中土','沙中土','天上火','天上火','石榴木','石榴木','大海水','大海水'
];
$NY_PY = [
'Hǎi Zhōng Jīn','Hǎi Zhōng Jīn','Lú Zhōng Huǒ','Lú Zhōng Huǒ','Dà Lín Mù','Dà Lín Mù',
'Lù Páng Tǔ','Lù Páng Tǔ','Jiàn Fēng Jīn','Jiàn Fēng Jīn','Shān Tóu Huǒ','Shān Tóu Huǒ',
'Jiàn Xià Shuǐ','Jiàn Xià Shuǐ','Chéng Qiáng Tǔ','Chéng Qiáng Tǔ','Bái Là Jīn','Bái Là Jīn',
'Yáng Liǔ Mù','Yáng Liǔ Mù','Quán Zhōng Shuǐ','Quán Zhōng Shuǐ','Wū Shàng Tǔ','Wū Shàng Tǔ',
'Pī Lì Huǒ','Pī Lì Huǒ','Sōng Bǎi Mù','Sōng Bǎi Mù','Cháng Liú Shuǐ','Cháng Liú Shuǐ',
'Shā Zhōng Jīn','Shā Zhōng Jīn','Shān Xià Huǒ','Shān Xià Huǒ','Píng Dì Mù','Píng Dì Mù',
'Bì Shàng Tǔ','Bì Shàng Tǔ','Jīn Bó Jīn','Jīn Bó Jīn','Fù Dēng Huǒ','Fù Dēng Huǒ',
'Tiān Hé Shuǐ','Tiān Hé Shuǐ','Dà Yì Tǔ','Dà Yì Tǔ','Chāi Chuàn Jīn','Chāi Chuàn Jīn',
'Sāng Zhè Mù','Sāng Zhè Mù','Dà Xī Shuǐ','Dà Xī Shuǐ','Shā Zhōng Tǔ','Shā Zhōng Tǔ',
'Tiān Shàng Huǒ','Tiān Shàng Huǒ','Shí Liú Mù','Shí Liú Mù','Dà Hǎi Shuǐ','Dà Hǎi Shuǐ'
];
$NY_ID = [
'Emas dalam laut','Emas dalam laut','Api dalam tungku','Api dalam tungku',
'Kayu hutan besar','Kayu hutan besar','Tanah tepi jalan','Tanah tepi jalan',
'Emas ujung pedang','Emas ujung pedang','Api puncak gunung','Api puncak gunung',
'Air bawah jurang','Air bawah jurang','Tanah tembok kota','Tanah tembok kota',
'Emas lilin putih','Emas lilin putih','Kayu pohon willow','Kayu pohon willow',
'Air mata air','Air mata air','Tanah atas atap','Tanah atas atap',
'Api petir','Api petir','Kayu pinus cemara','Kayu pinus cemara',
'Air mengalir panjang','Air mengalir panjang','Emas dalam pasir','Emas dalam pasir',
'Api bawah gunung','Api bawah gunung','Kayu tanah datar','Kayu tanah datar',
'Tanah di dinding','Tanah di dinding','Emas lembaran tipis','Emas lembaran tipis',
'Api lampu tertutup','Api lampu tertutup','Air sungai langit','Air sungai langit',
'Tanah pos besar','Tanah pos besar','Emas perhiasan','Emas perhiasan',
'Kayu murbei','Kayu murbei','Air sungai besar','Air sungai besar',
'Tanah dalam pasir','Tanah dalam pasir','Api di atas langit','Api di atas langit',
'Kayu pohon delima','Kayu pohon delima','Air laut besar','Air laut besar'
];

// Hidden stems for DISPLAY (正气 first)
$HS_DISPLAY = [
 0=>[9],1=>[5,9,7],2=>[0,2,4],3=>[1],4=>[4,1,9],5=>[2,4,6],
 6=>[3,5],7=>[5,3,1],8=>[6,4,8],9=>[7],10=>[4,7,3],11=>[8,0]
];

// Hidden stems for SI LING (余气→中气→正气, fractional days from astronomical table)
$HS = [
 0=>[[8,10.05],[9,19.41]],                      // 子: 壬10.05,癸19.41
 1=>[[9,13.00],[7,4.13],[5,12.36]],             // 丑: 癸13,辛4.13,己12.36
 2=>[[4,3.26],[2,8.00],[0,18.50]],              // 寅: 戊3.26,丙8,甲18.50
 3=>[[0,10.00],[1,20.21]],                      // 卯: 甲10,乙20.21
 4=>[[1,13.29],[9,4.20],[4,13.25]],             // 辰: 乙13.29,癸4.20,戊13.25
 5=>[[4,7.00],[6,9.00],[2,15.20]],              // 巳: 戊7,庚9,丙15.20
 6=>[[2,13.30],[5,6.00],[3,12.12]],             // 午: 丙13.30,己6,丁12.12
 7=>[[3,16.40],[1,3.00],[5,12.00]],             // 未: 丁16.40,乙3,己12
 8=>[[5,6.00],[4,1.00],[8,7.00],[6,17.11]],     // 申: 己6,戊1,壬7,庚17.11
 9=>[[6,10.39],[7,20.07]],                      // 酉: 庚10.39,辛20.07
10=>[[7,12.12],[3,8.00],[4,10.00]],             // 戌: 辛12.12,丁8,戊10
11=>[[4,2.24],[0,8.00],[8,19.45]],              // 亥: 戊2.24,甲8,壬19.45
];

// ── BAZI FUNCTIONS ──
// BaZi calculation functions

function bazi_gzc($tg, $dz) {
    for ($i = 0; $i < 60; $i++) {
        if ($i % 10 === $tg && $i % 12 === $dz) return $i;
    }
    return 0;
}

function bazi_epDays($y, $m, $d) {
    // Use gmmktime to avoid DST issues that cause off-by-1 errors
    return (int)floor((gmmktime(12,0,0,$m,$d,$y) - gmmktime(12,0,0,1,1,1970)) / 86400);
}

function bazi_kongWang($dTg, $dDz) {
    $opts = ['戌亥','申酉','午未','辰巳','寅卯','子丑'];
    $c60  = ((6*$dTg - 5*$dDz) % 60 + 60) % 60;
    return $opts[(int)floor($c60/10)];
}

function bazi_csIdx($dTg, $tDz, $CS_ST) {
    $y = ($dTg % 2 === 0);
    $s = $CS_ST[$dTg];
    return $y ? (($tDz - $s + 12) % 12) : (($s - $tDz + 12) % 12);
}

function bazi_csSelfIdx($tg, $dz, $CS_ST) {
    $y = ($tg % 2 === 0);
    $s = $CS_ST[$tg];
    return $y ? (($dz - $s + 12) % 12) : (($s - $dz + 12) % 12);
}

function bazi_shiShen($dTg, $tTg, $SS, $SS_PY) {
    $EL=[0,0,1,1,2,2,3,3,4,4];
    $YY=[0,1,0,1,0,1,0,1,0,1];
    $SHENG=[1,2,3,4,0]; $KE=[2,3,4,0,1];
    $el_dm=$EL[$dTg]; $el_t=$EL[$tTg];
    $same=($YY[$dTg]===$YY[$tTg]);
    if($el_dm===$el_t)             $idx=$same?0:1;
    elseif($SHENG[$el_dm]===$el_t) $idx=$same?2:3;
    elseif($KE[$el_dm]===$el_t)    $idx=$same?4:5;
    elseif($KE[$el_t]===$el_dm)    $idx=$same?6:7;
    else                           $idx=$same?8:9;
    return ['name'=>$SS[$idx],'py'=>$SS_PY[$idx]];
}

function bazi_getMonthDz($year, $month, $day, $hour, $min, $JIEDATA) {
    $jd = $JIEDATA[(string)$year] ?? [];
    $bk = $month*44640 + $day*1440 + $hour*60 + $min;
    usort($jd, function($a,$b){
        return ($a[0]*44640+$a[1]*1440+$a[2]*60+$a[3]) - ($b[0]*44640+$b[1]*1440+$b[2]*60+$b[3]);
    });
    $cDz = null; $jieE = null;
    foreach (array_reverse($jd) as $e) {
        if ($e[0]*44640+$e[1]*1440+$e[2]*60+$e[3] <= $bk) {
            $cDz = $e[4]; $jieE = $e; break;
        }
    }
    if ($cDz === null) {
        $prev = $JIEDATA[(string)($year-1)] ?? [];
        if ($prev) {
            usort($prev, function($a,$b){
                return ($b[0]*44640+$b[1]*1440) - ($a[0]*44640+$a[1]*1440);
            });
            $cDz = $prev[0][4]; $jieE = $prev[0];
        } else {
            $cDz = 1;
        }
    }
    return [$cDz, $jieE];
}

function bazi_getInteraction($elA, $elB, $nameA, $nameB, $SHENG, $KE, $EL_ZH, $EL_NM) {
    if ($elA === $elB) {
        return ['type'=>'bi','tooltip'=>"{$nameA} & {$nameB}: Elemen sama (比和 Bǐ Hé)"];
    }
    if ($SHENG[$elA] === $elB) {
        return ['type'=>'sheng','dir'=>'ab','tooltip'=>"{$nameA}({$EL_ZH[$elA]}) 生 {$nameB}({$EL_ZH[$elB]}) — {$EL_NM[$elA]} menghidupkan {$EL_NM[$elB]}"];
    }
    if ($SHENG[$elB] === $elA) {
        return ['type'=>'sheng','dir'=>'ba','tooltip'=>"{$nameB}({$EL_ZH[$elB]}) 生 {$nameA}({$EL_ZH[$elA]}) — {$EL_NM[$elB]} menghidupkan {$EL_NM[$elA]}"];
    }
    if ($KE[$elA] === $elB) {
        return ['type'=>'ke','dir'=>'ab','tooltip'=>"{$nameA}({$EL_ZH[$elA]}) 克 {$nameB}({$EL_ZH[$elB]}) — {$EL_NM[$elA]} mengalahkan {$EL_NM[$elB]}"];
    }
    if ($KE[$elB] === $elA) {
        return ['type'=>'ke','dir'=>'ba','tooltip'=>"{$nameB}({$EL_ZH[$elB]}) 克 {$nameA}({$EL_ZH[$elA]}) — {$EL_NM[$elB]} mengalahkan {$EL_NM[$elA]}"];
    }
    return null;
}

function bazi_buildPillar($tg, $dz, $dTg, $label, $allVars) {
    extract($allVars);
    $isMain   = ($label === '日柱' && $tg === $dTg);
    $ssData   = $isMain ? ['name'=>'主','py'=>'Zhǔ — Day Master'] : bazi_shiShen($dTg, $tg, $SS, $SS_PY);
    $hidden   = [];
    // Use HS_DISPLAY (正气 first) for display order
    $displayStems = $HS_DISPLAY[$dz] ?? [$HS[$dz][0][0]];
    foreach ($displayStems as $st) {
        $hss    = bazi_shiShen($dTg, $st, $SS, $SS_PY);
        $hidden[] = [
            'tg'     => $TG[$st],
            'tg_idx' => $st,
            'ss'     => $hss['name'],
            'ss_py'  => $hss['py'],
            'el'     => $TG_EL[$st],
            'el_zh'  => $EL_ZH[$TG_EL[$st]]
        ];
    }
    $csIdxDM   = bazi_csIdx($dTg, $dz, $CS_ST);
    $csIdxSelf = bazi_csSelfIdx($tg, $dz, $CS_ST);
    $nyi       = bazi_gzc($tg, $dz);
    return [
        'label'    => $label,
        'tg'       => $TG[$tg],  'tg_idx'   => $tg,
        'dz'       => $DZ[$dz],  'dz_idx'   => $dz,
        'el_tg'    => $TG_EL[$tg], 'el_tg_zh' => $EL_ZH[$TG_EL[$tg]], 'el_tg_name' => $EL_NM[$TG_EL[$tg]],
        'el_dz'    => $DZ_EL[$dz], 'el_dz_zh' => $EL_ZH[$DZ_EL[$dz]], 'el_dz_name' => $EL_NM[$DZ_EL[$dz]],
        'tg_yang'  => ($tg % 2 === 0),
        'dz_yang'  => ($dz % 2 === 0),
        'ss'       => $ssData,
        'hidden'   => $hidden,
        'cs_dm'    => ['name'=>$CS_NM[$csIdxDM],   'py'=>$CS_PY[$csIdxDM],   'en'=>$CS_EN[$csIdxDM]],
        'cs_self'  => ['name'=>$CS_NM[$csIdxSelf],  'py'=>$CS_PY[$csIdxSelf],  'en'=>$CS_EN[$csIdxSelf]],
        'nayin'    => ['name'=>$NY[$nyi], 'py'=>$NY_PY[$nyi], 'id'=>$NY_ID[$nyi]],
        'v_interaction' => bazi_getInteraction($TG_EL[$tg], $DZ_EL[$dz], $TG[$tg], $DZ[$dz], $SHENG, $KE, $EL_ZH, $EL_NM),
    ];
}

// ── BAZI CALCULATION CORE FUNCTION ──
// Callable directly (no HTTP overhead). Takes input array, returns result array.
function bazi_calculate($input) {
    // Access all data constants from parent scope via global
    global $TG, $DZ, $SS, $SS_PY, $CS_NM, $CS_PY, $CS_EN, $CS_ST;
    global $TG_EL, $DZ_EL, $EL_ZH, $EL_NM, $SHENG, $KE;
    global $NY, $NY_PY, $NY_ID, $HS, $HS_DISPLAY, $JIEDATA;

    if (!$input || !is_array($input)) throw new \InvalidArgumentException('Invalid input');

$year        = (int)($input['year']   ?? 0);
$month       = (int)($input['month']  ?? 0);
$day         = (int)($input['day']    ?? 0);
$hour        = (int)($input['hour']   ?? 0);
$min         = (int)($input['min']    ?? 0);
$sex         = ($input['sex'] ?? 'M') === 'F' ? 'F' : 'M';
$lnYear      = (int)($input['lnYear'] ?? date('Y'));
$unknownHour = !empty($input['unknown_hour']);

if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
    jsonResponse(['error' => 'Invalid date'], 400);
}

// ── YEAR PILLAR ──────────────────────────────────────────────────────────────
// Rule: compare birth WIB time (naive) vs jie CST time (naive)
// Use gmmktime to get a timezone-naive timestamp for birth WIB
// Jie stored as UTC → add 8h to get CST naive timestamp
$birthUtc = gmmktime($hour, $min, 0, $month, $day, $year); // birth WIB as naive ts
$CST_OFFSET = 8 * 3600; // convert jie UTC → CST naive

// Find 立春 of birth year
$lc = null;
foreach (($JIEDATA[$year] ?? []) as $e) {
    if ((int)$e[1] === 2) { $lc = $e; break; }
}
if (!$lc) {
    foreach (($JIEDATA[$year-1] ?? []) as $e) {
        if ((int)$e[1] === 2) { $lc = $e; break; }
    }
}

$baziYear = $year;
$lcNote   = '';
if ($lc) {
    $lcCstTs = (float)$lc[0] + $CST_OFFSET;
    $lcWib   = gmdate('n/j H:i', (int)$lc[0] + 7*3600);
    $lcCst   = gmdate('n/j H:i', (int)$lcCstTs);
    if ($birthUtc < $lcCstTs) {
        $baziYear = $year - 1;
        $lcNote   = "Sebelum 立春 {$year} ({$lcCst} CST / {$lcWib} WIB) BaZi={$baziYear}";
    } else {
        $lcNote = "Setelah 立春 {$year} ({$lcCst} CST / {$lcWib} WIB)";
    }
}
$yTg = (($baziYear - 4) % 10 + 10) % 10;
$yDz = (($baziYear - 4) % 12 + 12) % 12;

// ── MONTH PILLAR ─────────────────────────────────────────────────────────────
// Collect ALL jies from year-1, year, year+1, sort by ts, find last jie_cst <= birth_wib_ts
$mDz   = 2; // default 寅
$jieE  = null;
$jieTs = null;

$allJies = [];
foreach ([$year-1, $year, $year+1] as $searchYear) {
    foreach (($JIEDATA[$searchYear] ?? []) as $e) {
        $allJies[] = $e;
    }
}
usort($allJies, function($a, $b) { return $a[0] <=> $b[0]; });
foreach (array_reverse($allJies) as $e) {
    if ((float)$e[0] + $CST_OFFSET <= $birthUtc) {
        $mDz   = (int)$e[1];
        $jieE  = $e;
        $jieTs = (float)$e[0];
        break;
    }
}

$bases = [2,4,6,8,0,2,4,6,8,0];
$mTg   = ($bases[$yTg] + (($mDz - 2 + 12) % 12)) % 10;

// ── DAY PILLAR ───────────────────────────────────────────────────────────────
$earlyZi = !empty($input['early_zi']);
$cy = $year; $cm = $month; $cd = $day;
if ($hour >= 23 && $earlyZi) {
    // 早子时: 23:00-23:59 belongs to NEXT day
    $dt = new DateTime("{$year}-{$month}-{$day}");
    $dt->modify('+1 day');
    $cy = (int)$dt->format('Y');
    $cm = (int)$dt->format('m');
    $cd = (int)$dt->format('d');
}
// 夜子时 (default): 23:00-23:59 stays on current day — no adjustment needed
$c60 = ((bazi_epDays($cy, $cm, $cd) - bazi_epDays(1999,11,8)) % 60 + 60) % 60;
$dTg = $c60 % 10;
$dDz = $c60 % 12;

// ── HOUR PILLAR ──────────────────────────────────────────────────────────────
// 五鼠遁日起时法: 甲己日子时起甲(0), 乙庚日起丙(2), 丙辛日起戊(4), 丁壬日起庚(6), 戊癸日起壬(8)
$bases_hour = [0,2,4,6,8,0,2,4,6,8];
if ($unknownHour) {
    $hDz = null;
    $hTg = null;
} else {
    $hDz = ($hour >= 23) ? 0 : (int)floor(($hour + 1) / 2) % 12;
    $hTg = ($bases_hour[$dTg] + $hDz) % 10;
}

// ── KONG WANG ────────────────────────────────────────────────────────────────
$kw     = bazi_kongWang($dTg, $dDz);
$kwYear = bazi_kongWang($yTg, $yDz);

// ── TAI YUAN, MING GONG, SHEN GONG ──────────────────────────────────────────
$taiTg = ($mTg + 1) % 10;
$taiDz = ($mDz + 3) % 12;
if ($unknownHour) {
    // 命宫 and 身宫 cannot be calculated without hour
    $mgTg = null; $mgDz = null;
    $sgTg = null; $sgDz = null;
} else {
    // 命宫: 寅宫起子时逆布 → (17 - 月支 - 时支) % 12
    $mgDz  = (17 - $mDz - $hDz + 24) % 12;
    $mgTg  = ($bases[$yTg] + (($mgDz - 2 + 12) % 12)) % 10;
    // 身宫: (月支 + 时支 + 1) % 12 — same for Male and Female
    $sgDz  = ($mDz + $hDz + 1) % 12;
    $sgTg  = ($bases[$yTg] + (($sgDz - 2 + 12) % 12)) % 10;
}

// ── SI LING ──────────────────────────────────────────────────────────────────
$siLing = '';
if ($jieTs !== null) {
    // Fractional days: birth WIB vs jie CST
    $fracDays = ($birthUtc - ($jieTs + $CST_OFFSET)) / 86400.0;
    $segs = $HS[$mDz] ?? [[0,30]];
    $cum  = 0.0;
    foreach ($segs as $seg) {
        if ($fracDays < $cum + $seg[1]) { $siLing = $TG[$seg[0]]; break; }
        $cum += $seg[1];
    }
    if (!$siLing) $siLing = $TG[$segs[count($segs)-1][0]];
}

// ── BUILD 4 PILLARS ──────────────────────────────────────────────────────────
$allVars = compact('TG','DZ','SS','SS_PY','CS_NM','CS_PY','CS_EN','CS_ST',
                   'NY','NY_PY','NY_ID','HS','HS_DISPLAY','TG_EL','DZ_EL','EL_ZH','EL_NM','SHENG','KE');

$pillars = [
    $unknownHour
        ? ['tg'=>'?','dz'=>'?','tg_idx'=>-1,'dz_idx'=>-1,'label'=>'时柱','hidden'=>[],
           'ss'=>['name'=>'?','py'=>''],'el_tg_name'=>'?','el_dz_name'=>'?',
           'tg_yang'=>false,'dz_yang'=>false,
           'cs_dm'=>['name'=>'?','py'=>'','en'=>''],'cs_self'=>['name'=>'?','py'=>'','en'=>''],
           'nayin'=>['name'=>'?','py'=>'','id'=>''],'v_interaction'=>null,'unknown'=>true]
        : bazi_buildPillar($hTg, $hDz, $dTg, '时柱', $allVars),
    bazi_buildPillar($dTg, $dDz, $dTg, '日柱', $allVars),
    bazi_buildPillar($mTg, $mDz, $dTg, '月柱', $allVars),
    bazi_buildPillar($yTg, $yDz, $dTg, '年柱', $allVars),
];

// ── HORIZONTAL INTERACTIONS ──────────────────────────────────────────────────
$interactions = [];
for ($i = 0; $i < 3; $i++) {
    $a = $pillars[$i];
    $b = $pillars[$i+1];
    $interactions[] = [
        'tg' => bazi_getInteraction($a['el_tg'], $b['el_tg'], $a['tg'], $b['tg'], $SHENG, $KE, $EL_ZH, $EL_NM),
        'dz' => bazi_getInteraction($a['el_dz'], $b['el_dz'], $a['dz'], $b['dz'], $SHENG, $KE, $EL_ZH, $EL_NM),
    ];
}

// ── DA YUN ───────────────────────────────────────────────────────────────────
$isYang = ($yTg % 2 === 0);
$fwd    = ($sex === 'M' && $isYang) || ($sex === 'F' && !$isYang);
$diffD  = 0;
$dirs   = $fwd ? [$year, $year+1, $year+2] : [$year, $year-1, $year-2];

foreach ($dirs as $yr) {
    $jies = $JIEDATA[$yr] ?? [];
    usort($jies, function($a, $b){ return $a[0] <=> $b[0]; });
    if (!$fwd) $jies = array_reverse($jies);
    $found = false;
    foreach ($jies as $e) {
        $eCstTs = (float)$e[0] + $CST_OFFSET;
        if ($fwd ? $eCstTs > $birthUtc : $eCstTs < $birthUtc) {
            $diffD = abs($eCstTs - $birthUtc) / 86400.0;
            $found = true;
            break;
        }
    }
    if ($found) break;
}

$sAY      = $diffD / 3;
$ym       = $sAY * 12;
$ay       = (int)floor($ym / 12);
$am       = (int)floor(fmod($ym, 12));
$ad       = (int)round(fmod($ym, 1) * 30);
$startTs  = mktime($hour, $min, 0, $month, $day, $year) + (int)($sAY * 365.25 * 86400);
$startY   = (int)date('Y', $startTs);
$startMo  = (int)date('n', $startTs);
$startDay = (int)date('j', $startTs);
$mc       = bazi_gzc($mTg, $mDz);

$dyList = [];
// Col 0 = birth reference
$dyList[] = [
    'tg'=>$TG[$mTg], 'tg_idx'=>$mTg, 'dz'=>$DZ[$mDz], 'dz_idx'=>$mDz,
    'el_tg'=>$TG_EL[$mTg], 'el_dz'=>$DZ_EL[$mDz],
    'calYear'=>$year, 'calMo'=>$month, 'calDay'=>$day,
    'startAge'=>0, 'isBirth'=>true, 'ss'=>'', 'cs_dm'=>null, 'nayin'=>null, 'ln'=>[]
];

for ($i = 1; $i <= 12; $i++) {
    $c2   = $fwd ? ($mc + $i) % 60 : (($mc - $i) % 60 + 60) % 60;
    $dyTg = $c2 % 10;
    $dyDz = $c2 % 12;
    $dyTs = $startTs + (int)(($i-1) * 10 * 365.25 * 86400);
    $dyY  = (int)date('Y', $dyTs);
    $dyMo = (int)date('n', $dyTs);
    $dyDy = (int)date('j', $dyTs);
    $nyi  = bazi_gzc($dyTg, $dyDz);
    $csI  = bazi_csIdx($dTg, $dyDz, $CS_ST);
    $ssData = bazi_shiShen($dTg, $dyTg, $SS, $SS_PY);
    $ssIdx_name = $ssData['name'];

    $lnList = [];
    for ($yr = $dyY; $yr < $dyY + 10; $yr++) {
        $ltg = (($yr - 4) % 10 + 10) % 10;
        $ldz = (($yr - 4) % 12 + 12) % 12;
        $lnList[] = [
            'year'=>$yr, 'tg'=>$TG[$ltg], 'tg_idx'=>$ltg,
            'dz'=>$DZ[$ldz], 'dz_idx'=>$ldz,
            'el_tg'=>$TG_EL[$ltg], 'el_dz'=>$DZ_EL[$ldz],
            'isActive'=>($yr === $lnYear)
        ];
    }

    $dyList[] = [
        'tg'=>$TG[$dyTg], 'tg_idx'=>$dyTg, 'dz'=>$DZ[$dyDz], 'dz_idx'=>$dyDz,
        'el_tg'=>$TG_EL[$dyTg], 'el_dz'=>$DZ_EL[$dyDz],
        'calYear'=>$dyY, 'calMo'=>$dyMo, 'calDay'=>$dyDy,
        'startAge'=>$sAY + ($i-1)*10, 'isBirth'=>false,
        'ss'=>$ssIdx_name,
        'cs_dm'=>['name'=>$CS_NM[$csI], 'py'=>$CS_PY[$csI]],
        'nayin'=>['name'=>$NY[$nyi], 'py'=>$NY_PY[$nyi]],
        'ln'=>$lnList
    ];
}

// ── SAN YUAN NAYIN ───────────────────────────────────────────────────────────
$nTai = bazi_gzc($taiTg, $taiDz);

// ── RESPONSE ─────────────────────────────────────────────────────────────────
$unknownSanYuan = ['tg'=>'?','tg_idx'=>-1,'dz'=>'?','dz_idx'=>-1,
                   'nayin'=>['name'=>'?','py'=>'']];

return [
    'ok'           => true,
    'pillars'      => $pillars,
    'interactions' => $interactions,
    'lcNote'       => $lcNote,
    'kongwang'     => $kw,
    'kongwangYear' => $kwYear,
    'unknownHour'  => $unknownHour,
    'taiyuan'  => ['tg'=>$TG[$taiTg],'tg_idx'=>$taiTg,'dz'=>$DZ[$taiDz],'dz_idx'=>$taiDz,
                   'nayin'=>['name'=>$NY[$nTai],'py'=>$NY_PY[$nTai]]],
    'minggong' => $unknownHour ? $unknownSanYuan
                : (function() use ($mgTg,$mgDz,$TG,$DZ,$NY,$NY_PY) {
                    $n=bazi_gzc($mgTg,$mgDz);
                    return ['tg'=>$TG[$mgTg],'tg_idx'=>$mgTg,'dz'=>$DZ[$mgDz],'dz_idx'=>$mgDz,
                            'nayin'=>['name'=>$NY[$n],'py'=>$NY_PY[$n]]];
                  })(),
    'shengong' => $unknownHour ? $unknownSanYuan
                : (function() use ($sgTg,$sgDz,$TG,$DZ,$NY,$NY_PY) {
                    $n=bazi_gzc($sgTg,$sgDz);
                    return ['tg'=>$TG[$sgTg],'tg_idx'=>$sgTg,'dz'=>$DZ[$sgDz],'dz_idx'=>$sgDz,
                            'nayin'=>['name'=>$NY[$n],'py'=>$NY_PY[$n]]];
                  })(),
    'siling'   => $siLing,
    'dayun'    => [
        'fwd'=>$fwd, 'ay'=>$ay, 'am'=>$am, 'ad'=>$ad,
        'startYear'=>$startY, 'startMo'=>$startMo, 'startDay'=>$startDay,
        'list'=>$dyList
    ],
    'lnYear' => $lnYear,
    'ziMode' => $earlyZi ? 'early' : 'late',
];
}