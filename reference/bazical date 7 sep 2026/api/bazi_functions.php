<?php
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
    // Element: 甲乙=木(0), 丙丁=火(1), 戊己=土(2), 庚辛=金(3), 壬癸=水(4)
    $EL    = [0,0,1,1,2,2,3,3,4,4];
    // Polarity: 甲丙戊庚壬=yang(0), 乙丁己辛癸=yin(1)
    $YY    = [0,1,0,1,0,1,0,1,0,1];
    // 生 (generates): wood→fire→earth→metal→water
    $SHENG = [1,2,3,4,0];
    // 克 (controls):  wood→earth→water→fire→metal
    $KE    = [2,3,4,0,1];

    $el_dm = $EL[$dTg]; $el_t  = $EL[$tTg];
    $same_pol = ($YY[$dTg] === $YY[$tTg]);

    if ($el_dm === $el_t)               $idx = $same_pol ? 0 : 1; // 比肩/劫财
    elseif ($SHENG[$el_dm] === $el_t)   $idx = $same_pol ? 2 : 3; // 食神/伤官
    elseif ($KE[$el_dm] === $el_t)      $idx = $same_pol ? 4 : 5; // 偏财/正财
    elseif ($KE[$el_t] === $el_dm)      $idx = $same_pol ? 6 : 7; // 七杀/正官
    else                                $idx = $same_pol ? 8 : 9; // 偏印/正印

    return ['name' => $SS[$idx], 'py' => $SS_PY[$idx]];
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
    $displayStems = $HS_DISPLAY[$dz] ?? array_map(fn($s)=>$s[0], $HS[$dz]);
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