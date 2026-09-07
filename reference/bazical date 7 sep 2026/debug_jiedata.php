<?php
// Upload ke public_html/bazical/debug_jiedata.php
// Hapus setelah selesai
require_once dirname(__FILE__) . '/api/config.php';
$user = requireLogin();

header('Content-Type: text/plain; charset=utf-8');

require_once dirname(__FILE__) . '/api/jiedata.php';

// Check format jiedata
echo "=== JIEDATA FORMAT CHECK ===\n";
$jd2001 = $JIEDATA[2001] ?? $JIEDATA['2001'] ?? null;
echo "Key type int exists: " . (isset($JIEDATA[2001]) ? 'YES' : 'NO') . "\n";
echo "Key type str exists: " . (isset($JIEDATA['2001']) ? 'YES' : 'NO') . "\n";

if ($jd2001) {
    echo "First entry: " . json_encode($jd2001[0]) . "\n";
    echo "Entry count: " . count($jd2001) . "\n";
    
    // Is it Unix timestamp format or old format?
    $first = $jd2001[0];
    if (count($first) == 2 && $first[0] > 1000000) {
        echo "FORMAT: NEW (Unix timestamp)\n";
        // Show 立春 2001
        foreach ($jd2001 as $e) {
            if ($e[1] == 2) {
                $dt = gmdate('Y-m-d H:i', (int)$e[0]);
                $wib = gmdate('Y-m-d H:i', (int)$e[0] + 7*3600);
                echo "立春 2001: UTC=$dt, WIB=$wib\n";
                break;
            }
        }
    } else {
        echo "FORMAT: OLD (month/day/hour/min)\n";
        foreach ($jd2001 as $e) {
            if ($e[4] == 2) {
                echo "立春 2001: {$e[0]}/{$e[1]} {$e[2]}:{$e[3]}\n";
                break;
            }
        }
    }
} else {
    echo "JIEDATA 2001 NOT FOUND\n";
    echo "Available keys sample: " . implode(', ', array_slice(array_keys($JIEDATA), 0, 5)) . "\n";
}

// Check calculate.php for new code
echo "\n=== CALCULATE.PHP CHECK ===\n";
$calc = file_get_contents(dirname(__FILE__) . '/api/calculate.php');
echo "Has birthUtc: " . (strpos($calc, 'birthUtc') !== false ? 'YES (NEW)' : 'NO (OLD)') . "\n";
echo "Has gmmktime: " . (strpos($calc, 'gmmktime') !== false ? 'YES' : 'NO') . "\n";
echo "Has Unix timestamp: " . (strpos($calc, '86400.0') !== false ? 'YES (NEW)' : 'NO (OLD)') . "\n";

// Quick test Michelle
echo "\n=== QUICK TEST Michelle 2001-02-04 10:10 ===\n";
$birthH = 10 - 7; // UTC
$birthUtc = gmmktime($birthH, 10, 0, 2, 4, 2001);
echo "Birth UTC ts: $birthUtc\n";
echo "Birth UTC: " . gmdate('Y-m-d H:i', $birthUtc) . "\n";

$lc = null;
$jd = $JIEDATA[2001] ?? $JIEDATA['2001'] ?? [];
foreach ($jd as $e) {
    $dz_idx = isset($e[4]) ? $e[4] : $e[1];
    if ((int)$dz_idx === 2) { $lc = $e; break; }
}

if ($lc) {
    $lcTs = isset($lc[4]) ? ($lc[0]*44640+$lc[1]*1440+$lc[2]*60+$lc[3]) : (float)$lc[0];
    echo "立春 ts: $lcTs\n";
    echo "Birth > 立春: " . ($birthUtc >= $lcTs ? 'YES → 辛巳' : 'NO → 庚辰') . "\n";
}