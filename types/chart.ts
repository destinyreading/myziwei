// ============================================================
// Zi Wei Dou Shu — Core Type Definitions
// ============================================================

export type HeavenlyStem =
  | "Jia" | "Yi" | "Bing" | "Ding" | "Wu"
  | "Ji" | "Geng" | "Xin" | "Ren" | "Gui";

export type EarthlyBranch =
  | "Zi" | "Chou" | "Yin" | "Mao" | "Chen" | "Si"
  | "Wu" | "Wei" | "Shen" | "You" | "Xu" | "Hai";

// The 12 life palaces, in their fixed conceptual order starting from Life.
export type PalaceName =
  | "Life" | "Siblings" | "Spouse" | "Children" | "Wealth" | "Health"
  | "Travel" | "Friends" | "Career" | "Property" | "Happiness" | "Parents";

// Which of the 4 transformations a star carries in THIS chart (natal, from birth year).
export type SiHuaType = "Lu" | "Quan" | "Ke" | "Ji";

export interface StarPlacement {
  name: string;              // e.g. "Zi Wei", "Tian Ji"
  nameZh: string;             // e.g. "紫微"
  brightness: 1 | 2 | 3 | 4 | 5 | null; // 1 = brightest, 5 = dimmest, null = minor star w/o rating
  isMajor: boolean;           // true for the 14 major stars, false for auxiliary/minor stars
  /**
   * Display tier:
   *   "major" — the 14 main stars
   *   "aux"   — auxiliary stars that carry Si Hua or set structure
   *             (文昌 文曲 左輔 右弼 祿存 擎羊 陀羅 火星 鈴星 天魁 天鉞 …)
   *   "misc"  — the wider set of reading stars (三台 八座 天刑 天姚 …)
   * Optional so older fixtures without it still type-check; absent means
   * `isMajor ? "major" : "aux"`.
   */
  tier?: "major" | "aux" | "misc";
  natalSiHua: SiHuaType | null; // set if THIS star carries a natal transformation in this chart
  /**
   * 自化 self-transformation: set when this star is transformed by the stem of
   * the very palace it sits in. Independent of the natal transformation — a
   * star can carry both (e.g. natal Ji plus self Ke).
   */
  selfSiHua?: SiHuaType | null;
}

export interface Palace {
  branch: EarthlyBranch;      // fixed position on the 12-branch wheel
  stem: HeavenlyStem;         // this palace's own heavenly stem (used for "flying" si hua)
  name: PalaceName;
  nameZh: string;
  isBodyPalace: boolean;      // true if Body Palace (身宮) coincides with this palace
  ageRange: [number, number]; // Da Xian (decade) age range for this palace
  stars: StarPlacement[];
  /**
   * 長生十二神 — the twelve life stages. Unlike a star, every palace carries
   * exactly one: the cycle starts at a branch set by the Wu Xing Ju and runs
   * in the same direction as the Da Xian.
   */
  changSheng?: { name: string; nameZh: string };
  /**
   * 博士十二神 — the twelve officials. Like 長生, every palace carries exactly
   * one: the cycle starts on 祿存's branch and runs in the Da Xian direction.
   */
  boShi?: { name: string; nameZh: string };
  // Grid position for rendering the classic 4x4 layout (0-indexed, row/col).
  // The 2x2 center block (rows 1-2, cols 1-2) is reserved for the info panel.
  grid: { row: 0 | 1 | 2 | 3; col: 0 | 1 | 2 | 3 };
}

export interface ZwdsChart {
  meta: {
    name: string;
    solarDate: string;         // ISO date, e.g. "1975-07-17"
    solarTime: string;         // "06:23"
    lunarYear: string;         // e.g. "Yi-Mao (乙卯)"
    lunarMonth: number;
    lunarDay: number;
    isLeapMonth: boolean;
    /**
     * Bulan lunar yang BENAR-BENAR dipakai untuk menempatkan palace. Sama
     * dengan `lunarMonth` kecuali kelahiran bulan kabisat yang memakai 半月法
     * dan jatuh di tanggal 16+ — di situ nilainya bulan berikutnya.
     */
    monthUsed?: number;
    /** Aturan bulan kabisat yang dipakai chart ini. */
    leapRule?: "half" | "own";
    gender: "male" | "female";
    fiveElementJu: string;     // e.g. "Metal 4 (金四局)"
    fiveElementNumber: 2 | 3 | 4 | 5 | 6;
  };
  lifePalaceBranch: EarthlyBranch;
  bodyPalaceBranch: EarthlyBranch;
  palaces: Palace[];           // always length 12
}

// The four natal transformations for one birth-year stem, expressed as star names.
// This is also reused for "Flying Palace Si Hua" — the same table applied to
// any palace's own stem instead of the year stem.
export interface SiHuaSet {
  lu: string;
  quan: string;
  ke: string;
  ji: string;
}
