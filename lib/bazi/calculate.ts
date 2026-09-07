import JIEQI from "./jieqi.json";
import {
  CS_EN, CS_NM, CS_PY, CS_ST, DZ, DZ_EL, EL_NM, EL_ZH, HS_DISPLAY,
  KE, NY, NY_ID, NY_PY, SHENG, SS, SS_PY, TG, TG_EL,
} from "./tables";

// ============================================================
// Ba Zi engine — a direct TypeScript port of Bambang's PHP calculator
// (reference/bazical date 7 sep 2026/api/calculate_core.php), whose output
// he has verified. The PHP file remains the reference; when the two ever
// disagree, the PHP is right and this port has a bug.
//
// Conventions carried over from the PHP, deliberately:
//  - Jie Qi times are stored as UTC unix seconds and compared in CST
//    (UTC+8) against the birth time read as a NAIVE wall clock. In other
//    words the birth clock is compared to the Beijing clock, NOT converted
//    from the birth location's timezone. This is the convention the PHP
//    calculator uses and it is what the verified output depends on.
//  - Day pillar anchor: 1999-11-08 = jia-zi (index 0).
//  - Default 夜子时: 23:00-23:59 stays on the current day. Pass
//    earlyZi: true for 早子时 (roll to the next day).
// ============================================================

const JIE = JIEQI as unknown as Record<string, [number, number][]>;
const CST_OFFSET = 8 * 3600;

/** Days since epoch for a Gregorian date, timezone-free (mirrors bazi_epDays). */
function epDays(y: number, m: number, d: number): number {
  return Math.floor(Date.UTC(y, m - 1, d, 12, 0, 0) / 86400000);
}

/** Position in the 60 jia-zi cycle for a stem/branch pair (mirrors bazi_gzc). */
function gzc(tg: number, dz: number): number {
  for (let i = 0; i < 60; i++) if (i % 10 === tg && i % 12 === dz) return i;
  return 0;
}

function kongWang(dTg: number, dDz: number): string {
  const opts = ["戌亥", "申酉", "午未", "辰巳", "寅卯", "子丑"];
  const c60 = (((6 * dTg - 5 * dDz) % 60) + 60) % 60;
  return opts[Math.floor(c60 / 10)];
}

/** 十二长生 index of a branch relative to a stem. */
function csIdx(tg: number, dz: number): number {
  const yang = tg % 2 === 0;
  const s = CS_ST[tg];
  return yang ? ((dz - s + 12) % 12) : ((s - dz + 12) % 12);
}

export interface ShiShen { name: string; py: string }

/** 十神 of a target stem seen from the Day Master. */
export function shiShen(dayMaster: number, target: number): ShiShen {
  const EL = TG_EL;
  const YY = [0, 1, 0, 1, 0, 1, 0, 1, 0, 1];
  const elDm = EL[dayMaster];
  const elT = EL[target];
  const same = YY[dayMaster] === YY[target];

  let idx: number;
  if (elDm === elT) idx = same ? 0 : 1;            // 比肩 / 劫财
  else if (SHENG[elDm] === elT) idx = same ? 2 : 3; // 食神 / 伤官
  else if (KE[elDm] === elT) idx = same ? 4 : 5;    // 偏财 / 正财
  else if (KE[elT] === elDm) idx = same ? 6 : 7;    // 七杀 / 正官
  else idx = same ? 8 : 9;                          // 偏印 / 正印

  return { name: SS[idx], py: SS_PY[idx] };
}

export interface HiddenStem {
  tg: string; tgIdx: number; ss: string; ssPy: string; el: number; elZh: string;
}

export interface BaziPillarDetail {
  label: string;              // 年柱 月柱 日柱 时柱
  tg: string; tgIdx: number;
  dz: string; dzIdx: number;
  elTg: number; elTgZh: string; elTgName: string;
  elDz: number; elDzZh: string; elDzName: string;
  tgYang: boolean; dzYang: boolean;
  ss: ShiShen;                // 十神 (Day Master pillar reports 主)
  hidden: HiddenStem[];       // 藏干, main stem first
  csDm: { name: string; py: string; en: string };   // 长生 vs Day Master
  csSelf: { name: string; py: string; en: string }; // 长生 vs own stem
  naYin: { name: string; py: string; id: string };
}

export interface BaziResult {
  /** Ba Zi year used for the year pillar (rolls back one year before 立春). */
  baziYear: number;
  liChunNote: string;
  pillars: {
    year: BaziPillarDetail;
    month: BaziPillarDetail;
    day: BaziPillarDetail;
    hour: BaziPillarDetail | null; // null when the birth hour is unknown
  };
  dayMaster: { tg: string; tgIdx: number; el: number; elZh: string; elName: string };
  kongWang: { day: string; year: string };
  taiYuan: { tg: string; dz: string };   // 胎元
  mingGong: { tg: string; dz: string } | null; // 命宫 (needs the hour)
  shenGong: { tg: string; dz: string } | null; // 身宫 (needs the hour)
}

export interface BaziInput {
  year: number; month: number; day: number;
  hour: number; minute: number;
  unknownHour?: boolean;
  /** 早子时: treat 23:00-23:59 as the next day. Default false (夜子时). */
  earlyZi?: boolean;
}

function buildPillar(tg: number, dz: number, dayMaster: number, label: string): BaziPillarDetail {
  const isDayMaster = label === "日柱" && tg === dayMaster;
  const hidden: HiddenStem[] = HS_DISPLAY[dz].map((st) => {
    const hss = shiShen(dayMaster, st);
    return { tg: TG[st], tgIdx: st, ss: hss.name, ssPy: hss.py, el: TG_EL[st], elZh: EL_ZH[TG_EL[st]] };
  });
  const dm = csIdx(dayMaster, dz);
  const self = csIdx(tg, dz);
  const nyi = gzc(tg, dz);

  return {
    label,
    tg: TG[tg], tgIdx: tg,
    dz: DZ[dz], dzIdx: dz,
    elTg: TG_EL[tg], elTgZh: EL_ZH[TG_EL[tg]], elTgName: EL_NM[TG_EL[tg]],
    elDz: DZ_EL[dz], elDzZh: EL_ZH[DZ_EL[dz]], elDzName: EL_NM[DZ_EL[dz]],
    tgYang: tg % 2 === 0,
    dzYang: dz % 2 === 0,
    ss: isDayMaster ? { name: "主", py: "Zhǔ — Day Master" } : shiShen(dayMaster, tg),
    hidden,
    csDm: { name: CS_NM[dm], py: CS_PY[dm], en: CS_EN[dm] },
    csSelf: { name: CS_NM[self], py: CS_PY[self], en: CS_EN[self] },
    naYin: { name: NY[nyi], py: NY_PY[nyi], id: NY_ID[nyi] },
  };
}

export function calculateBazi(input: BaziInput): BaziResult {
  const { year, month, day } = input;
  const hour = input.hour;
  const minute = input.minute;

  if (year < 1901 || year > 2100) {
    throw new Error("Tahun di luar jangkauan tabel Jie Qi (1901-2100).");
  }

  // Birth clock read as a naive timestamp — see the header note.
  const birthTs = Math.floor(Date.UTC(year, month - 1, day, hour, minute, 0) / 1000);

  // ── YEAR PILLAR: rolls back if born before 立春 ────────────────────────
  let liChun = (JIE[String(year)] ?? []).find((e) => e[1] === 2)
    ?? (JIE[String(year - 1)] ?? []).find((e) => e[1] === 2);

  let baziYear = year;
  let liChunNote = "";
  if (liChun) {
    const lcCst = liChun[0] + CST_OFFSET;
    const fmt = (ts: number) => {
      const d = new Date(ts * 1000);
      return `${d.getUTCMonth() + 1}/${d.getUTCDate()} ${String(d.getUTCHours()).padStart(2, "0")}:${String(d.getUTCMinutes()).padStart(2, "0")}`;
    };
    if (birthTs < lcCst) {
      baziYear = year - 1;
      liChunNote = `Sebelum 立春 ${year} (${fmt(lcCst)} CST) — BaZi ${baziYear}`;
    } else {
      liChunNote = `Setelah 立春 ${year} (${fmt(lcCst)} CST)`;
    }
  }
  const yTg = (((baziYear - 4) % 10) + 10) % 10;
  const yDz = (((baziYear - 4) % 12) + 12) % 12;

  // ── MONTH PILLAR: last Jie at or before birth, across year-1..year+1 ──
  const all: [number, number][] = [];
  for (const y of [year - 1, year, year + 1]) all.push(...(JIE[String(y)] ?? []));
  all.sort((a, b) => a[0] - b[0]);

  let mDz = 2; // 寅 fallback
  for (let i = all.length - 1; i >= 0; i--) {
    if (all[i][0] + CST_OFFSET <= birthTs) { mDz = all[i][1]; break; }
  }

  // 五虎遁年起月法
  const monthBases = [2, 4, 6, 8, 0, 2, 4, 6, 8, 0];
  const mTg = (monthBases[yTg] + ((mDz - 2 + 12) % 12)) % 10;

  // ── DAY PILLAR ─────────────────────────────────────────────────────────
  let cy = year, cm = month, cd = day;
  if (hour >= 23 && input.earlyZi) {
    const next = new Date(Date.UTC(year, month - 1, day + 1));
    cy = next.getUTCFullYear(); cm = next.getUTCMonth() + 1; cd = next.getUTCDate();
  }
  const c60 = (((epDays(cy, cm, cd) - epDays(1999, 11, 8)) % 60) + 60) % 60;
  const dTg = c60 % 10;
  const dDz = c60 % 12;

  // ── HOUR PILLAR: 五鼠遁日起时法 ────────────────────────────────────────
  const hourBases = [0, 2, 4, 6, 8, 0, 2, 4, 6, 8];
  let hDz: number | null = null;
  let hTg: number | null = null;
  if (!input.unknownHour) {
    hDz = hour >= 23 ? 0 : Math.floor((hour + 1) / 2) % 12;
    hTg = (hourBases[dTg] + hDz) % 10;
  }

  // ── 胎元 / 命宫 / 身宫 ─────────────────────────────────────────────────
  const taiTg = (mTg + 1) % 10;
  const taiDz = (mDz + 3) % 12;

  let mingGong: BaziResult["mingGong"] = null;
  let shenGong: BaziResult["shenGong"] = null;
  if (hDz !== null) {
    const mgDz = (17 - mDz - hDz + 24) % 12;
    const mgTg = (monthBases[yTg] + ((mgDz - 2 + 12) % 12)) % 10;
    const sgDz = (mDz + hDz + 1) % 12;
    const sgTg = (monthBases[yTg] + ((sgDz - 2 + 12) % 12)) % 10;
    mingGong = { tg: TG[mgTg], dz: DZ[mgDz] };
    shenGong = { tg: TG[sgTg], dz: DZ[sgDz] };
  }

  return {
    baziYear,
    liChunNote,
    pillars: {
      year: buildPillar(yTg, yDz, dTg, "年柱"),
      month: buildPillar(mTg, mDz, dTg, "月柱"),
      day: buildPillar(dTg, dDz, dTg, "日柱"),
      hour: hTg !== null && hDz !== null ? buildPillar(hTg, hDz, dTg, "时柱") : null,
    },
    dayMaster: { tg: TG[dTg], tgIdx: dTg, el: TG_EL[dTg], elZh: EL_ZH[TG_EL[dTg]], elName: EL_NM[TG_EL[dTg]] },
    kongWang: { day: kongWang(dTg, dDz), year: kongWang(yTg, yDz) },
    taiYuan: { tg: TG[taiTg], dz: DZ[taiDz] },
    mingGong,
    shenGong,
  };
}
