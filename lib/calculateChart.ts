import type {
  EarthlyBranch,
  HeavenlyStem,
  Palace,
  PalaceName,
  SiHuaType,
  StarPlacement,
  ZwdsChart,
} from "../types/chart";
import { SI_HUA_TABLE } from "../data/siHuaTable";
import type { BirthInfo } from "./birthInfo";

// ============================================================
// calculateChart() — builds a full ZWDS chart from BirthInfo.
//
// Index conventions inside this file:
//   branch 0..11 = Zi Chou Yin Mao Chen Si Wu Wei Shen You Xu Hai (子..亥)
//   stem   0..9  = Jia .. Gui (甲..癸)
// These match lib/bazi's DZ/TG indices, so BaziResult indices drop straight in.
//
// Everything here is derived — no fixture data. Validated against
// data/exampleChart.json (Bambang, 1975-07-17 06:23, male): Ming Gong 辰,
// Shen Gong 戌, 金四局, Zi Wei 丑, Tian Fu 卯, plus Lu Cun / Qing Yang /
// Tuo Luo / Tian Kui / Huo Xing / Ling Xing / Di Jie / Hong Luan positions.
// ============================================================

const BRANCHES: EarthlyBranch[] = [
  "Zi", "Chou", "Yin", "Mao", "Chen", "Si",
  "Wu", "Wei", "Shen", "You", "Xu", "Hai",
];

const STEMS: HeavenlyStem[] = [
  "Jia", "Yi", "Bing", "Ding", "Wu",
  "Ji", "Geng", "Xin", "Ren", "Gui",
];

const PALACE_NAMES: PalaceName[] = [
  "Life", "Siblings", "Spouse", "Children", "Wealth", "Health",
  "Travel", "Friends", "Career", "Property", "Happiness", "Parents",
];

const PALACE_NAMES_ZH: Record<PalaceName, string> = {
  Life: "命宮", Siblings: "兄弟宮", Spouse: "夫妻宮", Children: "子女宮",
  Wealth: "財帛宮", Health: "疾厄宮", Travel: "遷移宮", Friends: "僕役宮",
  Career: "官祿宮", Property: "田宅宮", Happiness: "福德宮", Parents: "父母宮",
};

/** Classic 4x4 layout: Si top-left, running clockwise around the ring. */
const GRID: Record<number, { row: 0 | 1 | 2 | 3; col: 0 | 1 | 2 | 3 }> = {
  5: { row: 0, col: 0 },  // Si
  6: { row: 0, col: 1 },  // Wu
  7: { row: 0, col: 2 },  // Wei
  8: { row: 0, col: 3 },  // Shen
  9: { row: 1, col: 3 },  // You
  10: { row: 2, col: 3 }, // Xu
  11: { row: 3, col: 3 }, // Hai
  0: { row: 3, col: 2 },  // Zi
  1: { row: 3, col: 1 },  // Chou
  2: { row: 3, col: 0 },  // Yin
  3: { row: 2, col: 0 },  // Mao
  4: { row: 1, col: 0 },  // Chen
};

const mod12 = (n: number) => ((n % 12) + 12) % 12;
const mod10 = (n: number) => ((n % 10) + 10) % 10;

// ── Star registry ─────────────────────────────────────────────────────────
// English name (must match SI_HUA_TABLE values) -> Chinese name.
const STAR_ZH: Record<string, string> = {
  "Zi Wei": "紫微", "Tian Ji": "天機", "Tai Yang": "太陽", "Wu Qu": "武曲",
  "Tian Tong": "天同", "Lian Zhen": "廉貞", "Tian Fu": "天府", "Tai Yin": "太陰",
  "Tan Lang": "貪狼", "Ju Men": "巨門", "Tian Xiang": "天相", "Tian Liang": "天梁",
  "Qi Sha": "七殺", "Po Jun": "破軍",
  "Wen Chang": "文昌", "Wen Qu": "文曲", "Zuo Fu": "左輔", "You Bi": "右弼",
  "Lu Cun": "祿存", "Qing Yang": "擎羊", "Tuo Luo": "陀羅",
  "Tian Kui": "天魁", "Tian Yue": "天鉞",
  "Huo Xing": "火星", "Ling Xing": "鈴星",
  "Di Kong": "地空", "Di Jie": "地劫",
  "Hong Luan": "紅鸞", "Tian Xi": "天喜", "Tian Ma": "天馬",
};

// Brightness (廟旺得平陷 -> 1..5) per major star, indexed by branch 子..亥.
// Schools differ on a handful of cells; this is the common table. If Bambang's
// reference disagrees, this table is the single place to correct it.
const BRIGHTNESS: Record<string, (1 | 2 | 3 | 4 | 5)[]> = {
  "Zi Wei":     [4, 1, 1, 2, 3, 2, 1, 1, 2, 4, 3, 2],
  "Tian Ji":    [1, 5, 2, 2, 3, 4, 1, 5, 2, 2, 3, 4],
  "Tai Yang":   [5, 5, 2, 1, 1, 2, 1, 3, 3, 4, 5, 5],
  "Wu Qu":      [2, 1, 4, 3, 1, 4, 2, 1, 4, 4, 1, 4],
  "Tian Tong":  [2, 5, 3, 4, 4, 1, 5, 3, 2, 2, 4, 1],
  "Lian Zhen":  [4, 3, 1, 5, 2, 5, 4, 3, 1, 5, 2, 5],
  "Tian Fu":    [1, 1, 2, 3, 1, 4, 2, 1, 3, 4, 1, 3],
  "Tai Yin":    [1, 1, 5, 5, 5, 5, 5, 3, 2, 2, 1, 1],
  "Tan Lang":   [2, 1, 4, 3, 1, 5, 2, 1, 4, 3, 1, 5],
  "Ju Men":     [2, 5, 1, 1, 4, 3, 2, 5, 1, 2, 4, 2],
  "Tian Xiang": [1, 1, 1, 3, 3, 4, 1, 3, 1, 3, 3, 2],
  "Tian Liang": [1, 2, 1, 1, 1, 5, 1, 3, 5, 3, 2, 5],
  "Qi Sha":     [2, 1, 1, 4, 2, 4, 2, 1, 1, 2, 2, 4],
  "Po Jun":     [1, 2, 3, 5, 2, 4, 1, 2, 3, 5, 2, 4],
};

// Na Yin element per ganzhi PAIR (60 ganzhi / 2 = 30 pairs), used only to
// derive the Wu Xing Ju from the Ming Gong's own stem+branch.
const NAYIN_PAIR_JU: number[] = [
  4, 6, 3, 5, 4, 6,
  2, 5, 4, 3, 2, 5,
  6, 3, 2, 4, 6, 3,
  5, 4, 6, 2, 5, 4,
  3, 2, 5, 6, 3, 2,
];

const JU_LABEL: Record<number, string> = {
  2: "Water 2 (水二局)",
  3: "Wood 3 (木三局)",
  4: "Metal 4 (金四局)",
  5: "Earth 5 (土五局)",
  6: "Fire 6 (火六局)",
};

/** Lu Cun's branch per year stem 甲..癸 (寅卯巳午巳午申酉亥子). */
const LU_CUN_BY_STEM = [2, 3, 5, 6, 5, 6, 8, 9, 11, 0];

/** Tian Kui / Tian Yue branches per year stem 甲..癸. */
const TIAN_KUI_BY_STEM = [1, 0, 11, 11, 1, 0, 1, 6, 3, 3];
const TIAN_YUE_BY_STEM = [7, 8, 9, 9, 7, 8, 7, 2, 5, 5];

/** Tian Ma's branch, by year-branch triad (寅午戌→申, 申子辰→寅, 巳酉丑→亥, 亥卯未→巳). */
function tianMaBranch(yearBranch: number): number {
  switch (yearBranch) {
    case 2: case 6: case 10: return 8;
    case 8: case 0: case 4:  return 2;
    case 5: case 9: case 1:  return 11;
    default:                 return 5;
  }
}

/** Huo Xing / Ling Xing start branch, by year-branch triad. */
function huoLingStart(yearBranch: number): [number, number] {
  switch (yearBranch) {
    case 2: case 6: case 10: return [1, 3];   // 寅午戌 -> 丑 / 卯
    case 8: case 0: case 4:  return [2, 10];  // 申子辰 -> 寅 / 戌
    case 5: case 9: case 1:  return [3, 10];  // 巳酉丑 -> 卯 / 戌
    default:                 return [9, 10];  // 亥卯未 -> 酉 / 戌
  }
}

/** 60-ganzhi index (0..59) for a stem/branch combination. */
function ganzhiIndex(stem: number, branch: number): number {
  for (let n = 0; n < 60; n++) {
    if (n % 10 === stem && n % 12 === branch) return n;
  }
  return 0; // unreachable for valid stem/branch parity
}

export interface ChartOptions {
  /** Some schools put Ren's Hua Ke on Tian Fu instead of Zuo Fu. */
  renKeTianFu?: boolean;
}

export function calculateChart(info: BirthInfo, options: ChartOptions = {}): ZwdsChart {
  const yearStem = info.bazi.pillars.year.tgIdx;
  const yearBranch = info.bazi.pillars.year.dzIdx;
  const hourBranch = info.bazi.pillars.hour
    ? info.bazi.pillars.hour.dzIdx
    : mod12(Math.floor((Number(info.solarTime.slice(0, 2)) + 1) / 2));

  const lunarMonth = info.lunarMonth;
  const lunarDay = info.lunarDay;

  // ── 1. Ming Gong / Shen Gong ────────────────────────────────────────────
  // Count forward from Yin by (lunar month - 1), then back (Ming) or
  // forward (Shen) by the hour branch.
  const monthStep = lunarMonth - 1;
  const mingBranch = mod12(2 + monthStep - hourBranch);
  const shenBranch = mod12(2 + monthStep + hourBranch);

  // ── 2. Palace stems (五虎遁 from the birth-year stem) ────────────────────
  const yinStem = mod10((yearStem % 5) * 2 + 2);
  const stemOfBranch = (b: number) => mod10(yinStem + mod12(b - 2));

  // ── 3. Wu Xing Ju, from the Ming Gong's own ganzhi ──────────────────────
  const mingStem = stemOfBranch(mingBranch);
  const juNumber = NAYIN_PAIR_JU[Math.floor(ganzhiIndex(mingStem, mingBranch) / 2)];

  // ── 4. Zi Wei, from the Ju number and the lunar day ─────────────────────
  let pad = 0;
  while ((lunarDay + pad) % juNumber !== 0) pad++;
  const quotient = (lunarDay + pad) / juNumber;
  const ziWei = mod12(2 + (quotient - 1) + (pad % 2 === 0 ? pad : -pad));

  // ── 5. The 14 major stars ───────────────────────────────────────────────
  const tianFu = mod12(4 - ziWei);
  const majors: Record<string, number> = {
    "Zi Wei": ziWei,
    "Tian Ji": mod12(ziWei - 1),
    "Tai Yang": mod12(ziWei - 3),
    "Wu Qu": mod12(ziWei - 4),
    "Tian Tong": mod12(ziWei - 5),
    "Lian Zhen": mod12(ziWei - 8),
    "Tian Fu": tianFu,
    "Tai Yin": mod12(tianFu + 1),
    "Tan Lang": mod12(tianFu + 2),
    "Ju Men": mod12(tianFu + 3),
    "Tian Xiang": mod12(tianFu + 4),
    "Tian Liang": mod12(tianFu + 5),
    "Qi Sha": mod12(tianFu + 6),
    "Po Jun": mod12(tianFu + 10),
  };

  // ── 6. Auxiliary / minor stars ──────────────────────────────────────────
  const luCun = LU_CUN_BY_STEM[yearStem];
  const [huoStart, lingStart] = huoLingStart(yearBranch);
  const hongLuan = mod12(3 - yearBranch);

  const minors: Record<string, number> = {
    "Wen Chang": mod12(10 - hourBranch),
    "Wen Qu": mod12(4 + hourBranch),
    "Zuo Fu": mod12(4 + monthStep),
    "You Bi": mod12(10 - monthStep),
    "Lu Cun": luCun,
    "Qing Yang": mod12(luCun + 1),
    "Tuo Luo": mod12(luCun - 1),
    "Tian Kui": TIAN_KUI_BY_STEM[yearStem],
    "Tian Yue": TIAN_YUE_BY_STEM[yearStem],
    "Huo Xing": mod12(huoStart + hourBranch),
    "Ling Xing": mod12(lingStart + hourBranch),
    "Di Kong": mod12(11 - hourBranch),
    "Di Jie": mod12(11 + hourBranch),
    "Hong Luan": hongLuan,
    "Tian Xi": mod12(hongLuan + 6),
    "Tian Ma": tianMaBranch(yearBranch),
  };

  // ── 7. Natal Si Hua, from the birth-year stem ───────────────────────────
  const siHuaSet = SI_HUA_TABLE[STEMS[yearStem]];
  const natalSiHua: Record<string, SiHuaType> = {};
  natalSiHua[siHuaSet.lu] = "Lu";
  natalSiHua[siHuaSet.quan] = "Quan";
  natalSiHua[options.renKeTianFu && STEMS[yearStem] === "Ren" ? "Tian Fu" : siHuaSet.ke] = "Ke";
  natalSiHua[siHuaSet.ji] = "Ji";

  // ── 8. Da Xian direction and starting age ───────────────────────────────
  // Yang year stem + male, or yin year stem + female -> clockwise (forward).
  const yangYear = yearStem % 2 === 0;
  const forward = yangYear === (info.gender === "male");

  // ── 9. Assemble the 12 palaces ──────────────────────────────────────────
  const starsByBranch = new Map<number, StarPlacement[]>();
  const push = (branch: number, name: string, isMajor: boolean) => {
    const list = starsByBranch.get(branch) ?? [];
    list.push({
      name,
      nameZh: STAR_ZH[name] ?? name,
      brightness: isMajor ? BRIGHTNESS[name][branch] : null,
      isMajor,
      natalSiHua: natalSiHua[name] ?? null,
    });
    starsByBranch.set(branch, list);
  };
  Object.entries(majors).forEach(([n, b]) => push(b, n, true));
  Object.entries(minors).forEach(([n, b]) => push(b, n, false));

  const palaces: Palace[] = PALACE_NAMES.map((palaceName, i) => {
    // Palaces run counter-clockwise (decreasing branch) from Ming Gong.
    const branch = mod12(mingBranch - i);
    // Backward charts: palace order and Da Xian order coincide, so i is the
    // decade index. Forward charts are fixed up after this map.
    const startAge = juNumber + i * 10;
    return {
      branch: BRANCHES[branch],
      stem: STEMS[stemOfBranch(branch)],
      name: palaceName,
      nameZh: PALACE_NAMES_ZH[palaceName],
      isBodyPalace: branch === shenBranch,
      ageRange: [startAge, startAge + 9] as [number, number],
      stars: (starsByBranch.get(branch) ?? []).sort(
        (a, b) => Number(b.isMajor) - Number(a.isMajor)
      ),
      grid: GRID[branch],
    };
  });

  // Da Xian ranges follow the direction, not the palace order: when the chart
  // runs forward, the decade after Ming Gong sits at branch+1 (Parents), not
  // at branch-1 (Siblings).
  if (forward) {
    for (let step = 0; step < 12; step++) {
      const branch = mod12(mingBranch + step);
      const p = palaces.find((x) => x.branch === BRANCHES[branch])!;
      p.ageRange = [juNumber + step * 10, juNumber + step * 10 + 9];
    }
  }

  return {
    meta: {
      name: info.name,
      solarDate: info.solarDate,
      solarTime: info.solarTime,
      lunarYear: `${STEMS[yearStem]}-${BRANCHES[yearBranch]} (${info.bazi.pillars.year.tg}${info.bazi.pillars.year.dz})`,
      lunarMonth,
      lunarDay,
      isLeapMonth: info.isLeapMonth,
      gender: info.gender,
      fiveElementJu: JU_LABEL[juNumber],
      fiveElementNumber: juNumber as 2 | 3 | 4 | 5 | 6,
    },
    lifePalaceBranch: BRANCHES[mingBranch],
    bodyPalaceBranch: BRANCHES[shenBranch],
    palaces,
  };
}
