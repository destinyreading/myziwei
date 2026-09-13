"use client";

import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";
import { Cormorant_Garamond } from "next/font/google";
import type { EarthlyBranch, HeavenlyStem, Palace, ZwdsChart as ZwdsChartData } from "../types/chart";
import { SI_HUA_TABLE, resolveFlyingSiHua } from "../data/siHuaTable";
import BirthForm from "./BirthForm";
import BaziPanel from "./BaziPanel";
import { computeBirthInfo, type BirthInfo } from "../lib/birthInfo";
import { calculateChart } from "../lib/calculateChart";
import { STAR_MEANINGS } from "../lib/starMeanings";

// ============================================================
// Design notes (read before extending):
//
// - Grid: classic 4x4 Zi Wei layout. The 12 palaces occupy the ring;
//   the center 2x2 block is reserved for chart meta / active detail.
//   Palace.grid.{row,col} in the data model drives placement directly,
//   so re-ordering palaces never requires touching this component.
//
// - Interaction: click/tap a palace to select it (tap works identically
//   on mobile and desktop). Hovering on desktop previews without
//   needing a click. Selecting a palace computes where ITS OWN stem's
//   four transformations (Lu/Quan/Ke/Ji) currently sit in the chart —
//   "Flying Palace Si Hua" — and draws colored lines to those palaces.
//   A transformation landing back on its own source palace (自化,
//   self-transformation) is drawn as a dashed ring instead of a line.
//
// - Mobile: rather than a fixed-position bottom sheet (which fights
//   iOS Safari's viewport/keyboard quirks), the detail panel flows
//   normally below the grid and the page scrolls to it. Swap in a
//   real drawer component (e.g. vaul) later if you want the slide-up
//   feel — the data flow here doesn't change either way.
// ============================================================

const HUA_COLOR: Record<"lu" | "quan" | "ke" | "ji", string> = {
  lu: "#10b981",   // emerald-500 — abundance / smooth flow
  quan: "#3b82f6", // blue-500    — authority / power
  ke: "#d97706",   // amber-600   — reputation / recognition
  ji: "#e11d48",   // rose-600    — obstruction / fixation
};

/**
 * 煞星 — the harmful stars, drawn in red so a troubled palace is obvious at a
 * glance. Six of them are aux tier (visible with "Show Minor Star"); 天刑 is
 * misc tier (visible with "Show Misc Star").
 */
const SHA_STARS = new Set([
  "Qing Yang", // 擎羊
  "Tuo Luo",   // 陀羅
  "Huo Xing",  // 火星
  "Ling Xing", // 鈴星
  "Di Kong",   // 地空
  "Di Jie",    // 地劫
  "Tian Xing", // 天刑
]);

// Self-hosted at build time by next/font — no runtime request to Google.
const brandFont = Cormorant_Garamond({ subsets: ["latin"], weight: ["500", "600"], display: "swap" });

const HUA_LABEL: Record<"lu" | "quan" | "ke" | "ji", string> = {
  lu: "化祿 Lu",
  quan: "化權 Quan",
  ke: "化科 Ke",
  ji: "化忌 Ji",
};

/**
 * 大限四化 / 流年四化 tag colours. Deliberately the same rose/violet as the
 * DLife / ALife ring labels in the palace footer — one colour per LAYER, not
 * per transformation, so a tag and its ring read as the same thing.
 * Rose is also natal 忌; the "D" prefix is what separates them. If that ever
 * reads as ambiguous, move 忌/煞星 to brick red — not the ring colour, which
 * has to stay matched to its label.
 */
const LAYER_COLOR = { D: "#e11d48", A: "#7c3aed" } as const;

// Latin tag text per transformation, matching the natal tags already shown.
const HUA_TAG = { lu: "Lu", quan: "Quan", ke: "Ke", ji: "Ji" } as const;
const HUA_ZH = { lu: "祿", quan: "權", ke: "科", ji: "忌" } as const;

// Stem wheel, for 流年天干: 1984 is 甲子, so (year - 4) mod 10 indexes it.
const STEM_ORDER: HeavenlyStem[] = [
  "Jia", "Yi", "Bing", "Ding", "Wu", "Ji", "Geng", "Xin", "Ren", "Gui",
];

// Branch wheel order, so the opposite palace (対宮) is simply +6.
const BRANCH_ORDER: EarthlyBranch[] = [
  "Zi", "Chou", "Yin", "Mao", "Chen", "Si",
  "Wu", "Wei", "Shen", "You", "Xu", "Hai",
];

function centerOf(row: number, col: number) {
  return { x: ((col + 0.5) / 4) * 100, y: ((row + 0.5) / 4) * 100 };
}

// Brightness (廟旺得平陷) as ONE dot plus its number. The dot is colour-coded
// on a gold-to-black ramp: 1 = brightest = gold, 5 = dimmest = black. Five
// separate dots read as a rating bar and cost far more horizontal room in a
// palace box, which is scarce.
const BRIGHTNESS_COLOR: Record<1 | 2 | 3 | 4 | 5, string> = {
  1: "#d4af37", // gold — 廟, brightest
  2: "#a8862c",
  3: "#7d5f22",
  4: "#4a3a18",
  5: "#1c1c1c", // black — 陷, dimmest
};

function BrightnessMark({
  level,
  inheritSize,
}: {
  level: 1 | 2 | 3 | 4 | 5 | null;
  /** Desktop Version sets one font size on the whole star list; the number
      inherits it instead of pinning its own. */
  inheritSize?: boolean;
}) {
  if (level === null) return null;
  return (
    <span
      className="inline-flex items-center gap-[2px] align-middle ml-1"
      aria-label={`brightness ${level} of 5`}
      title={`Brightness ${level}/5 — 1 = brightest`}
    >
      <span
        className="inline-block w-[6px] h-[6px] rounded-full"
        style={{ backgroundColor: BRIGHTNESS_COLOR[level] }}
      />
      <span
        className={
          (inheritSize ? "" : "text-[9px] ") + "leading-none text-neutral-500"
        }
      >
        {level}
      </span>
    </span>
  );
}

function ToggleButton({
  active,
  onClick,
  disabled,
  title,
  children,
}: {
  active: boolean;
  onClick: () => void;
  disabled?: boolean;
  title?: string;
  children: React.ReactNode;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      title={title}
      aria-pressed={active}
      className={[
        "rounded border px-2 py-1 text-[11px] transition-colors",
        disabled
          ? "cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-300"
          : active
          ? "border-neutral-800 bg-neutral-800 text-white"
          : "border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50",
      ].join(" ")}
    >
      {children}
    </button>
  );
}

export default function ZwdsChart({ chart: fallbackChart }: { chart: ZwdsChartData }) {
  const [hovered, setHovered] = useState<EarthlyBranch | null>(null);
  const [selected, setSelected] = useState<EarthlyBranch | null>(null);
  /**
   * PNG export. While `exportBox` is set the chart is laid out at a FIXED size
   * off-screen and photographed, so the picture is the same on a phone as on a
   * 27-inch monitor. Bambang asked for the plain view in it: no palace picked,
   * therefore none of the four long flying-Si-Hua lines and no amber highlight.
   * The short arrows at the palace edges stay — those are always on screen and
   * are not the result of a click.
   */
  const [exportBox, setExportBox] = useState<{ w: number; h: number } | null>(null);
  const [exportBusy, setExportBusy] = useState(false);
  const [exportErr, setExportErr] = useState<string | null>(null);
  /** Finished PNG, kept on screen because iOS often refuses a direct download. */
  const [pngUrl, setPngUrl] = useState<string | null>(null);

  /**
   * Bintang yang sedang difokuskan (Desktop Version). Mengklik sebuah bintang
   * membalik arah bacaan Si Hua: alih-alih menggambar empat transformasi yang
   * KELUAR dari sebuah palace, kita menggambar setiap panah yang MASUK ke
   * bintang ini — dari palace mana pun yang stem-nya mengirim 祿/權/科/忌 ke
   * sana. Diverifikasi pada chart 1975: 太陰 menerima 忌 dari 酉(乙), 科 dari
   * 未(癸), 權 dari 寅(戊) dan 子(戊), 祿 dari 亥(丁), plus 自化科 dari 辰(庚).
   *
   * Dua mode ini saling meniadakan, atas permintaan Bambang: selama sebuah
   * bintang difokuskan, garis keluar milik palace tidak digambar — kalau tidak,
   * dua kumpulan garis akan saling bertabrakan di layar yang sama.
   */
  const [focusStar, setFocusStar] = useState<
    { name: string; nameZh: string; palace: string } | null
  >(null);

  const active = exportBox || focusStar ? null : hovered ?? selected;

  // Birth input + derived lunar/Ba Zi data. `info === null` means the center
  // block shows the input form; generating fills it in.
  const [info, setInfo] = useState<BirthInfo | null>(null);
  const [editing, setEditing] = useState(true);
  const [showBazi, setShowBazi] = useState(false);
  const [showMinor, setShowMinor] = useState(false);
  const [showClash, setShowClash] = useState(false);
  const [showSanFang, setShowSanFang] = useState(false);
  // Set on mount (never during SSR) so the clock matches the visitor's.
  const [todayYear, setTodayYear] = useState<number | null>(null);
  /** Age at which the selected Da Xian starts; null = follow "today". */
  const [decadeStart, setDecadeStart] = useState<number | null>(null);
  /** Selected Liu Nian year; null = decade mode (years + ages in the footer). */
  const [selectedYear, setSelectedYear] = useState<number | null>(null);
  /** Selected 流月 (lunar month 1..12) within the selected year; null = year mode. */
  const [selectedMonth, setSelectedMonth] = useState<number | null>(null);
  /**
   * Star whose reading is showing in the centre block. Set by CLICKING a star,
   * never by hovering — the centre must not change just because the cursor
   * crossed the grid. Clicking the same star again, or the ✕, restores the
   * birth data.
   */
  const [selectedStar, setSelectedStar] = useState<
    { name: string; nameZh: string; palace: string; text: string } | null
  >(null);
  /** True while showing the chart auto-generated for the visitor's clock. */
  const [isNow, setIsNow] = useState(false);
  /**
   * Desktop Version: a full-screen layer on the SAME page (not window.open),
   * so every bit of state travels with it — decade, year, month, the star
   * being read, all three toggles. A second window would mean a second page,
   * birth data through the URL, state lost, and pop-up blockers.
   * The palace boxes are deliberately NOT square here: the grid fills a
   * landscape screen, so each palace becomes a wide rectangle (~2.2:1). The
   * CONTENT is unchanged — same 漢字 + pinyin, brightness dots, Si Hua tags,
   * D/A/M labels, 3x2 footer, red 煞星. Only the box shape differs.
   */
  const [desktop, setDesktop] = useState(false);
  /**
   * Which stem 流年四化 is taken from. 年干 (the year's own Heavenly Stem) is
   * the common reading and the default; 宮干 uses the stem of the palace the
   * year lands on, which some schools prefer. 大限四化 has no such split —
   * it always comes from the decade palace's own stem.
   */
  const [liuNianStem, setLiuNianStem] = useState<"year" | "palace">("year");

  // Auto-generate a chart for "now" as soon as the page opens, so a visitor
  // sees a real chart instead of an empty form. Done in an effect (not in
  // useState) because `new Date()` during the server render would not match
  // the visitor's clock and React would flag a hydration mismatch.
  useEffect(() => {
    if (info) return;
    const d = new Date();
    const p2 = (n: number) => String(n).padStart(2, "0");
    setTodayYear(d.getFullYear());
    try {
      setInfo(
        computeBirthInfo({
          name: "",
          date: `${d.getFullYear()}-${p2(d.getMonth() + 1)}-${p2(d.getDate())}`,
          time: `${p2(d.getHours())}:${p2(d.getMinutes())}`,
          gender: "male",
        })
      );
      setEditing(false);
      setIsNow(true);
    } catch {
      // out of the Jie Qi table's range, or similar — leave the form showing
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);
  const [showMisc, setShowMisc] = useState(false);

  // Esc leaves the full-screen layer. Bound only while it is open, so it can
  // never swallow Esc from anything else on the page.
  useEffect(() => {
    if (!desktop) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setDesktop(false);
    };
    window.addEventListener("keydown", onKey);
    // The layer covers the viewport; letting the page behind it scroll only
    // moves content the visitor cannot see.
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = prev;
    };
  }, [desktop]);

  // Once birth data exists the grid is computed; before that we render the
  // fixture passed in as a prop so the layout is still visible.
  const chart = useMemo(
    () => (info ? calculateChart(info) : fallbackChart),
    [info, fallbackChart]
  );

  // ── Star anchors ────────────────────────────────────────────────────────
  // Si Hua lines must land on the STAR that transforms, not on the middle of
  // its palace: two transformations pointing at the same palace would other-
  // wise be drawn as one overlapping line. We measure each rendered star row
  // and draw in pixel space.
  // Mutable (`| null` in the type argument) because the callback ref below
  // assigns to it; a plain useRef<HTMLDivElement>(null) is read-only.
  const gridRef = useRef<HTMLDivElement | null>(null);
  const starRefs = useRef(new Map<string, HTMLElement>());
  const [geom, setGeom] = useState<{
    w: number;
    h: number;
    stars: Record<string, { x: number; y: number; left: number; right: number }>;
  } | null>(null);

  /** Room the layer leaves for the grid; see measure(). */
  const [availH, setAvailH] = useState<number | null>(null);

  const measure = useCallback(() => {
    const el = gridRef.current;
    if (!el) return;
    const box = el.getBoundingClientRect();
    if (!box.width || !box.height) return;
    // How much vertical room the layer gives the grid. Taken from the grid's
    // TOP plus the viewport, never from the grid's own height — in Desktop
    // Version the grid is allowed to grow past the screen, and measuring its
    // height here would feed that growth back into the font size.
    setAvailH(Math.max(240, Math.round(window.innerHeight - box.top - 12)));
    const stars: Record<string, { x: number; y: number; left: number; right: number }> = {};
    starRefs.current.forEach((node, name) => {
      const r = node.getBoundingClientRect();
      // Rounded so repeat measurements of an unchanged layout compare equal.
      stars[name] = {
        x: Math.round(r.left - box.left + r.width / 2),
        y: Math.round(r.top - box.top + r.height / 2),
        left: Math.round(r.left - box.left),
        right: Math.round(r.right - box.left),
      };
    });
    // Bail out when nothing moved, so a ResizeObserver burst does not
    // re-render the chart for every intermediate pixel.
    setGeom((prev) => {
      const w = Math.round(box.width);
      const h = Math.round(box.height);
      if (
        prev &&
        prev.w === w &&
        prev.h === h &&
        Object.keys(prev.stars).length === Object.keys(stars).length &&
        Object.entries(stars).every(([n, v]) => {
          const q = prev.stars[n];
          return q && q.x === v.x && q.y === v.y && q.left === v.left && q.right === v.right;
        })
      ) {
        return prev;
      }
      return { w, h, stars };
    });
  }, []);

  /**
   * Desktop Version uses ONE font size for the whole star list — no bigger and
   * smaller — at Bambang's request (2026-09-09). A single fixed number does not
   * work: measured on the 1975 chart with minor + misc + D + A all on, a 1440
   * wide screen only survives 12px while a 1920 one takes 16px comfortably. So
   * the size follows the palace height, which is what actually runs out.
   *
   * palaceHeight / 17.5 comes from those measurements (209px → 12, 254px → 14.5)
   * and is clamped to 11–16. The grid's height does not depend on the font, so
   * this cannot feed back on itself; `starFont` is in the measure effect's deps
   * so the Si Hua anchors are re-measured once after the size settles.
   */
  /**
   * The box the desktop layout is sized against: the export size while a PNG is
   * being made, otherwise the room the viewport leaves. Kept separate from
   * `geom`, which is measured FROM the grid and would feed back into the size.
   */
  const layoutH = exportBox ? exportBox.h : availH;
  const layoutW = exportBox ? exportBox.w : geom?.w ?? null;

  const starFont = useMemo(() => {
    if (!desktop || !layoutH || !layoutW) return null;
    // Height is usually what runs out, but not always: an iPad rotated to
    // portrait has plenty of height and a narrow palace, and at 16px five
    // pinyin names were being truncated ("Tian Liang" -> "Tian..."). So the
    // size is capped by whichever of the two runs out first. Measured: at a
    // 193px palace truncation stops at 11px, i.e. the same 17.5 divisor that
    // the height calibration produced.
    // geom.w cannot grow with the font (the grid never gets wider than the
    // viewport), so this stays free of feedback.
    const limit = Math.min(layoutH / 4, layoutW / 4);
    return Math.min(16, Math.max(11, Math.round(limit / 17.5)));
  }, [desktop, layoutH, layoutW]);

  const palaceByBranch = useMemo(() => {
    const map = new Map<EarthlyBranch, Palace>();
    chart.palaces.forEach((p) => map.set(p.branch, p));
    return map;
  }, [chart]);

  // star name -> palace name, for resolving where a transformed star lives
  const starIndex = useMemo(() => {
    const map = new Map<string, string>();
    chart.palaces.forEach((p) => p.stars.forEach((s) => map.set(s.name, p.name)));
    return map;
  }, [chart]);

  // star name -> Chinese name, so the Si Hua list can show both scripts
  const STAR_ZH_BY_NAME = useMemo(() => {
    const map = new Map<string, string>();
    chart.palaces.forEach((p) => p.stars.forEach((s) => map.set(s.name, s.nameZh)));
    return map;
  }, [chart]);

  // name -> branch, needed to draw lines (we key palaces by branch on the grid)
  const branchByName = useMemo(() => {
    const map = new Map<string, EarthlyBranch>();
    chart.palaces.forEach((p) => map.set(p.name, p.branch));
    return map;
  }, [chart]);

  const activePalace = active ? palaceByBranch.get(active) ?? null : null;

  // 三方四正 of the active palace: itself, the two trine palaces (±4), and the
  // one opposite (+6). Only used when the toggle is on.
  const sanFang = useMemo(() => {
    if (!showSanFang || !activePalace) return null;
    const i = BRANCH_ORDER.indexOf(activePalace.branch);
    const at = (n: number) => BRANCH_ORDER[((i + n) % 12 + 12) % 12];
    return { trine: new Set([at(4), at(8)]), opposite: at(6) };
  }, [showSanFang, activePalace]);

  // Measure only when something that moves the stars changes. Running this
  // after EVERY render is what caused an update loop: setGeom re-renders, the
  // effect fires again, forever. Size changes are covered by the observer
  // below instead.
  useLayoutEffect(() => {
    measure();
    // decadeStart / selectedYear / liuNianStem are listed rather than
    // activeDecade and annual: those memos are declared BELOW this effect, and
    // naming them in the dep array would read them before initialisation.
  }, [
    measure, chart, showMinor, showMisc, showBazi, editing, info, desktop,
    decadeStart, selectedYear, liuNianStem, starFont, availH, activePalace?.branch,
  ]);

  /**
   * Attach the ResizeObserver through a CALLBACK ref, not a mount-time effect.
   * Opening Desktop Version re-parents the whole tree into the full-screen
   * layer, so React unmounts the grid and mounts a NEW node. A `useEffect` that
   * observed `gridRef.current` once kept watching the OLD, detached node — and
   * from then on no resize was ever measured again: after entering the layer,
   * rotating an iPad or resizing the window left `geom` stale (wrong star
   * anchors for the Si Hua lines) and the font frozen at its previous size.
   * A callback ref runs again for every node React gives us, so the observer
   * always follows the element that is actually on screen.
   */
  const observerRef = useRef<ResizeObserver | null>(null);
  const setGridRef = useCallback(
    (el: HTMLDivElement | null) => {
      gridRef.current = el;
      observerRef.current?.disconnect();
      observerRef.current = null;
      if (el && typeof ResizeObserver !== "undefined") {
        const ro = new ResizeObserver(measure);
        ro.observe(el);
        observerRef.current = ro;
      }
      if (el) measure();
    },
    [measure]
  );

  // Rotating a tablet fires `resize` reliably even when the box happens to end
  // up the same size; cheap belt and braces alongside the observer.
  useEffect(() => {
    const onResize = () => measure();
    window.addEventListener("resize", onResize);
    window.addEventListener("orientationchange", onResize);
    return () => {
      window.removeEventListener("resize", onResize);
      window.removeEventListener("orientationchange", onResize);
    };
  }, [measure]);

  // ── 大限 / 流年 ──────────────────────────────────────────────────────────
  // Lunar age (虚岁): 1 at birth, +1 each lunar new year, so age = year - birth
  // year + 1. The visible decade defaults to the one containing today's age.
  const lunarAgeNow =
    info && todayYear ? todayYear - info.lunarYear + 1 : null;

  const activeDecade = useMemo(() => {
    if (!info) return null;
    // The decade containing today's age; when the age falls outside every
    // range — a chart cast for someone born today is age 1, while the ranges
    // start at the Ju number — fall back to the first decade instead of
    // dropping the whole Da Xian / Liu Nian layer.
    const firstStart = Math.min(...chart.palaces.map((p) => p.ageRange[0]));
    const start =
      decadeStart ??
      (lunarAgeNow != null
        ? chart.palaces.find(
            (p) => lunarAgeNow >= p.ageRange[0] && lunarAgeNow <= p.ageRange[1]
          )?.ageRange[0] ?? firstStart
        : firstStart);
    if (start == null) return null;
    const palace = chart.palaces.find((p) => p.ageRange[0] === start);
    if (!palace) return null;

    // Each year of the decade sits on the palace whose branch matches that
    // year's own branch (2018 戊戌 -> 戌). Ten years over twelve palaces, so
    // two palaces stay blank.
    const years = new Map<EarthlyBranch, { year: number; age: number }>();
    for (let age = start; age <= start + 9; age++) {
      const year = info.lunarYear + age - 1;
      years.set(BRANCH_ORDER[((year - 4) % 12 + 12) % 12], { year, age });
    }

    // 大限十二宮: the decade palace is D-Self, then the same anticlockwise order.
    const i = BRANCH_ORDER.indexOf(palace.branch);
    const names = new Map<EarthlyBranch, string>();
    chart.palaces.forEach((_, offset) => {
      names.set(BRANCH_ORDER[((i - offset) % 12 + 12) % 12], chart.palaces[offset].name);
    });

    return { start, palace, years, names };
  }, [info, chart, decadeStart, lunarAgeNow]);

  // ── 流年 ────────────────────────────────────────────────────────────────
  // Picking a year turns the footer into month mode: 流年斗君 puts month 1 on
  // (year branch − (lunar month − 1) + birth hour branch), months then run
  // forward. The year's own branch carries 流年命宮, and the twelve annual
  // palaces run anticlockwise from it, like the natal and decade rings.
  const annual = useMemo(() => {
    if (!info || selectedYear == null) return null;
    const hour = info.bazi.pillars.hour?.dzIdx ?? 0;
    const yearBranchIdx = ((selectedYear - 4) % 12 + 12) % 12;
    const douJun =
      ((yearBranchIdx - (info.lunarMonth - 1) + hour) % 12 + 12) % 12;

    const months = new Map<EarthlyBranch, number>();
    for (let m = 1; m <= 12; m++) {
      months.set(BRANCH_ORDER[(douJun + m - 1) % 12], m);
    }
    const names = new Map<EarthlyBranch, string>();
    chart.palaces.forEach((_, offset) => {
      names.set(
        BRANCH_ORDER[((yearBranchIdx - offset) % 12 + 12) % 12],
        chart.palaces[offset].name
      );
    });
    return { year: selectedYear, branch: BRANCH_ORDER[yearBranchIdx], months, names };
  }, [info, selectedYear, chart]);

  // ── 流月 ────────────────────────────────────────────────────────────────
  // Picking one of the M1..M12 cells makes that palace 流月命宮; the twelve
  // 流月十二宮 then run anticlockwise from it, exactly like the natal, decade
  // and annual rings. Without a chosen month there is no 流月命宮, so no labels.
  const monthly = useMemo(() => {
    if (!annual || selectedMonth == null) return null;
    // Plain lookup over BRANCH_ORDER — assigning inside a forEach callback
    // would leave TypeScript narrowing the variable to null.
    const branch = BRANCH_ORDER.find((b) => annual.months.get(b) === selectedMonth);
    if (!branch) return null;
    const i = BRANCH_ORDER.indexOf(branch);
    const names = new Map<EarthlyBranch, string>();
    chart.palaces.forEach((_, offset) => {
      names.set(BRANCH_ORDER[((i - offset) % 12 + 12) % 12], chart.palaces[offset].name);
    });
    return { month: selectedMonth, branch, names };
  }, [annual, selectedMonth, chart]);

  // ── 大限四化 / 流年四化 ──────────────────────────────────────────────────
  // Which stars the DECADE's and the YEAR's four transformations land on, as a
  // star -> tags map so the grid can label the star itself. Deliberately gated
  // on `desktop`: these tags exist only inside the full-screen layer, where a
  // palace is 354px wide. Off that layer the map is empty, so nothing is
  // computed and nothing is rendered — on a phone they are absent, not hidden.
  const layerSiHua = useMemo(() => {
    const map = new Map<string, { layer: "D" | "A"; hua: "Lu" | "Quan" | "Ke" | "Ji"; huaZh: string }[]>();
    if (!desktop) return map;

    const add = (layer: "D" | "A", stem: HeavenlyStem) => {
      const set = SI_HUA_TABLE[stem];
      (["lu", "quan", "ke", "ji"] as const).forEach((k) => {
        const star = set[k];
        // A star the chart never placed carries no tag.
        if (!starIndex.has(star)) return;
        const list = map.get(star) ?? [];
        list.push({ layer, hua: HUA_TAG[k], huaZh: HUA_ZH[k] });
        map.set(star, list);
      });
    };

    if (activeDecade) add("D", activeDecade.palace.stem);
    if (annual) {
      const stem =
        liuNianStem === "year"
          ? STEM_ORDER[((annual.year - 4) % 10 + 10) % 10]
          : palaceByBranch.get(annual.branch)?.stem;
      if (stem) add("A", stem);
    }
    return map;
  }, [desktop, activeDecade, annual, liuNianStem, starIndex, palaceByBranch]);

  /**
   * Siapa saja yang mengirim Si Hua ke bintang yang sedang difokuskan.
   * Sumbernya adalah dua belas 宮干 — keputusan Bambang: "masuk dari palace
   * mana menuju bintang yang ditunjuk", bukan dari stem natal/大限/流年.
   * Satu bintang bisa menerima hua yang sama dari dua palace sekaligus ketika
   * kedua palace itu kebetulan ber-stem sama, jadi ini daftar, bukan peta.
   */
  const incoming = useMemo(() => {
    if (!desktop || !focusStar) return [];
    const out: {
      branch: EarthlyBranch;
      palace: string;
      stem: HeavenlyStem;
      hua: "lu" | "quan" | "ke" | "ji";
      isSelf: boolean;
    }[] = [];
    chart.palaces.forEach((src) => {
      const set = SI_HUA_TABLE[src.stem];
      (["lu", "quan", "ke", "ji"] as const).forEach((k) => {
        if (set[k] !== focusStar.name) return;
        out.push({
          branch: src.branch,
          palace: src.name,
          stem: src.stem,
          hua: k,
          isSelf: src.name === focusStar.palace,
        });
      });
    });
    return out;
  }, [desktop, focusStar, chart]);

  /**
   * 借宮 — palace yang diklik diperlakukan sebagai 命宮 baru, lalu kedua belas
   * palace diberi label relatifnya (1..12) mengikuti urutan berlawanan arah
   * jarum jam yang sama dengan cincin natal. Dipakai untuk membaca chart dari
   * sudut pandang orang lain: pasangan, anak, orang tua.
   *
   * Tidak berlaku saat yang diklik adalah 命宮 itu sendiri — hasilnya akan
   * sama persis dengan cincin natal, jadi tidak ada yang bisa dibaca.
   * Cincin 大限/流年/流月 sengaja TIDAK ikut di-anchor ulang.
   */
  const interchange = useMemo(() => {
    if (!desktop || !activePalace) return null;
    // chart.palaces[0] adalah 命宮; urutan array-nya sudah berlawanan arah
    // jarum jam, sama seperti yang dipakai cincin 大限.
    if (chart.palaces[0].branch === activePalace.branch) return null;
    const i = BRANCH_ORDER.indexOf(activePalace.branch);
    const map = new Map<EarthlyBranch, { n: number; name: string }>();
    chart.palaces.forEach((_, offset) => {
      map.set(BRANCH_ORDER[((i - offset) % 12 + 12) % 12], {
        n: offset + 1,
        name: chart.palaces[offset].name,
      });
    });
    return { anchor: activePalace.branch, map };
  }, [desktop, activePalace, chart]);

  const flying = useMemo(() => {
    if (!activePalace) return null;
    return resolveFlyingSiHua(activePalace.stem, (starName) => {
      const palaceName = starIndex.get(starName);
      return palaceName ?? null;
    });
  }, [activePalace, starIndex]);

  // Which star each of the active palace's four transformations lands on, so
  // the receiving star itself can be highlighted in the grid.
  const huaOfStar = useMemo(() => {
    const map = new Map<string, "lu" | "quan" | "ke" | "ji">();
    if (!flying) return map;
    (["lu", "quan", "ke", "ji"] as const).forEach((k) => {
      if (flying[k].palace) map.set(flying[k].star, k);
    });
    return map;
  }, [flying]);

  /**
   * Build the PNG. `html-to-image` is imported on demand so it never lands in
   * the first-load bundle for the people who only ever look at the chart.
   */
  async function handleDownloadPng() {
    if (exportBusy) return;
    setExportBusy(true);
    setExportErr(null);
    setPngUrl(null);

    const wasDesktop = desktop;
    // 1600 x 1050 puts the font at its 15px ceiling and keeps the palace near
    // the reference proportions; x2 gives a 3200 x 2100 picture.
    const box = { w: 1600, h: 1050 };
    try {
      setDesktop(true);
      setExportBox(box);
      // Two frames for React to commit, a beat more for the measure pass that
      // positions the Si Hua lines, and the fonts before anything is painted.
      await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(() => r(null))));
      await new Promise((r) => setTimeout(r, 400));
      try {
        await (document as Document & { fonts?: { ready: Promise<unknown> } }).fonts?.ready;
      } catch {
        // font loading API missing — carry on, the faces are self-hosted anyway
      }

      const node = gridRef.current;
      if (!node) throw new Error("Chart belum siap.");
      const { toPng } = await import("html-to-image");
      const url = await toPng(node, {
        pixelRatio: 2,
        backgroundColor: "#ffffff",
        width: box.w,
        height: node.scrollHeight,
        cacheBust: true,
        // html-to-image draws a CLONE inside an SVG <foreignObject> anchored at
        // 0,0. The clone inherits this node's `position: fixed; left: -20000px`
        // and lands outside the frame, which produced a blank white PNG the
        // first time round. Overriding the clone's own position puts it back at
        // the origin; the live node stays off-screen.
        style: { position: "static", left: "0", top: "0", margin: "0" },
      });
      setPngUrl(url);

      // Desktop browsers save it straight away. iOS usually ignores this and
      // does nothing, which is why the picture is also shown below the chart.
      try {
        const a = document.createElement("a");
        a.href = url;
        const who = (isNow ? "waktu-saat-ini" : info?.name || "chart")
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, "-")
          .replace(/^-|-$/g, "");
        a.download = `zwds-${who || "chart"}-${info?.solarDate ?? ""}.png`;
        a.click();
      } catch {
        // blocked — the on-screen picture is the fallback
      }
    } catch (err) {
      setExportErr(err instanceof Error ? err.message : "Gagal membuat gambar.");
    } finally {
      setExportBox(null);
      setDesktop(wasDesktop);
      setExportBusy(false);
    }
  }

  function handleSelect(branch: EarthlyBranch) {
    // Mode palace dan mode bintang saling meniadakan.
    setFocusStar(null);
    setSelected((prev) => (prev === branch ? null : branch));
  }

  // The whole component is the same tree in both modes. Desktop Version only
  // swaps the outer box: fixed full-screen instead of the 672px column, and a
  // grid that fills the leftover height instead of `aspect-square`.
  const body = (
    <div
      className={
        desktop
          ? "flex h-full w-full flex-col select-none"
          : "w-full max-w-2xl mx-auto select-none"
      }
    >
      {/* legend + view toggles */}
      <div className="mb-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <div className="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-neutral-500">
          {(["lu", "quan", "ke", "ji"] as const).map((k) => (
            <span key={k} className="inline-flex items-center gap-1">
              <span className="inline-block w-2.5 h-2.5 rounded-full" style={{ backgroundColor: HUA_COLOR[k] }} />
              {HUA_LABEL[k]}
            </span>
          ))}
          <span className="inline-flex items-center gap-1">
            <span className="rounded-sm border border-neutral-400 px-[2px] text-[9px] leading-[1.4]">自X</span>
            自化 self-transform
          </span>
          <span className="inline-flex items-center gap-1">
            {([1, 2, 3, 4, 5] as const).map((l) => (
              <span
                key={l}
                className="inline-block w-[6px] h-[6px] rounded-full"
                style={{ backgroundColor: BRIGHTNESS_COLOR[l] }}
              />
            ))}
            <span className="ml-0.5">terang 1 (emas) → 5 (hitam)</span>
          </span>
        </div>
        <div className="flex gap-1.5">
          <ToggleButton
            active={showBazi}
            onClick={() => setShowBazi((v) => !v)}
            disabled={!info}
            title={info ? undefined : "Generate dulu untuk melihat Ba Zi"}
          >
            Show Ba Zi
          </ToggleButton>
          <ToggleButton active={showMinor} onClick={() => setShowMinor((v) => !v)}>
            Show Minor Star
          </ToggleButton>
          <ToggleButton
            active={showMisc}
            onClick={() => setShowMisc((v) => !v)}
            title="三台 八座 天刑 天姚 龍池 鳳閣 孤辰 寡宿 …"
          >
            Show Misc Star
          </ToggleButton>
          <ToggleButton
            active={showSanFang}
            onClick={() => setShowSanFang((v) => !v)}
            title="三方四正 — the palace, its two trine palaces, and the one opposite"
          >
            三方四正
          </ToggleButton>
          <ToggleButton
            active={showClash}
            onClick={() => setShowClash((v) => !v)}
            title="沖: the palace struck by Hua Ji also afflicts its opposite palace"
          >
            Show Clash 沖
          </ToggleButton>
          <button
            type="button"
            onClick={handleDownloadPng}
            disabled={exportBusy || !info}
            title="Simpan chart sebagai gambar PNG beresolusi tinggi (3200 x 2100)"
            className={
              "rounded border px-2 py-1 text-[11px] transition-colors " +
              (exportBusy || !info
                ? "cursor-not-allowed border-neutral-200 bg-neutral-50 text-neutral-300"
                : "border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50")
            }
          >
            {exportBusy ? "Menyiapkan…" : "Unduh PNG"}
          </button>
          {/* Only from 1024px up. On a phone the button does not exist at all,
              so it cannot be pressed there, and it disappears again if the
              desktop window is narrowed — a layout this wide is useless in a
              narrow window. */}
          {/* Only meaningful inside the layer, because that is the only place
              the 流年 tags exist. Showing it in the normal view would offer a
              choice with no visible effect. */}
          {desktop && (
            <button
              type="button"
              onClick={() => setLiuNianStem((m) => (m === "year" ? "palace" : "year"))}
              title={
                liuNianStem === "year"
                  ? "流年四化 dari stem tahun (年干) — cara yang lazim. Klik untuk memakai stem palace (宮干)."
                  : "流年四化 dari stem palace tempat tahun itu jatuh (宮干). Klik untuk kembali ke stem tahun (年干)."
              }
              className="rounded border border-neutral-300 bg-white px-2 py-1 text-[11px] text-neutral-600 transition-colors hover:bg-neutral-50"
            >
              流年 {liuNianStem === "year" ? "年干" : "宮干"}
            </button>
          )}
          {desktop ? (
            <button
              type="button"
              onClick={() => setDesktop(false)}
              title="Tutup (Esc)"
              className="rounded border border-neutral-300 bg-white px-2 py-1 text-[11px] text-neutral-600 transition-colors hover:bg-neutral-50"
            >
              ✕ Tutup
            </button>
          ) : (
            <button
              type="button"
              onClick={() => setDesktop(true)}
              title="Layar penuh — kotak palace melebar mengikuti layar landscape"
              className="hidden rounded border border-neutral-300 bg-white px-2 py-1 text-[11px] text-neutral-600 transition-colors hover:bg-neutral-50 lg:inline-flex"
            >
              Desktop Version
            </button>
          )}
        </div>
      </div>

      {/* grid + svg overlay */}
      {/* Square from `sm` up. On narrow screens the pinyin sits on its own
          line, so a square grid clips the 4th/5th star out of a busy palace —
          give it a taller box instead, taller again once minor stars are on
          (a busy palace then holds 5 two-line entries). The Si Hua overlay is
          measured in pixels, so a non-square grid is fine. */}
      {/* Desktop Version: the grid fills the layer instead of being square, so
          each palace comes out as a wide rectangle. Its rows are
          `minmax(quarter, auto)`: normally a quarter of the available height
          each, but a row GROWS when its busiest palace needs more. On a short
          screen — an iPad in landscape with every layer switched on — the grid
          then becomes taller than the viewport and the whole layer scrolls,
          which is one ordinary page scroll. The old behaviour clipped the star
          list inside each palace and left it scrollable on its own; that works
          with a mouse but not by touch, because the palace is a <button> and
          iOS treats a drag inside it as a tap. Star names simply vanished.
          `availH` comes from the viewport, never from this box, so growth here
          can never feed back into the font size. */}
      <div
        ref={setGridRef}
        className={
          desktop
            ? "relative w-full shrink-0 border border-neutral-300"
            : `relative w-full ${showMinor ? "h-[46rem]" : "h-[34rem]"} sm:h-auto sm:aspect-square border border-neutral-300`
        }
        style={
          exportBox
            ? {
                // Rendered off-screen rather than in place: the layer would
                // otherwise flash a 1600px chart across the viewport. It is a
                // real layout, not a transform, so text stays sharp at any
                // pixel ratio.
                position: "fixed",
                left: -20000,
                top: 0,
                width: exportBox.w,
                minHeight: exportBox.h,
                background: "#fff",
              }
            : desktop && layoutH
              ? { minHeight: layoutH }
              : undefined
        }
      >
        <div
          className={
            desktop
              ? "grid w-full grid-cols-4"
              : "absolute inset-0 grid grid-cols-4 grid-rows-4"
          }
          style={
            desktop && layoutH
              ? {
                  minHeight: layoutH,
                  gridTemplateRows: `repeat(4, minmax(${Math.floor(layoutH / 4)}px, auto))`,
                }
              : undefined
          }
        >
          {chart.palaces.map((p) => (
            <button
              key={p.branch}
              type="button"
              onClick={() => handleSelect(p.branch)}
              onMouseEnter={() => setHovered(p.branch)}
              onMouseLeave={() => setHovered(null)}
              style={{ gridRow: p.grid.row + 1, gridColumn: p.grid.col + 1 }}
              className={[
                "flex flex-col items-start justify-start text-left border border-neutral-200",
                desktop ? "p-2" : "p-1",
                "min-h-11 transition-colors",
                desktop ? "" : "overflow-hidden",
                active === p.branch
                  ? sanFang ? "bg-amber-100" : "bg-amber-50"
                  : sanFang?.trine.has(p.branch)
                    ? "bg-amber-50"
                    : sanFang?.opposite === p.branch
                      ? "bg-amber-50/60"
                      : "bg-white hover:bg-neutral-50",
                p.isBodyPalace ? "ring-1 ring-inset ring-neutral-400" : "",
              ].join(" ")}
            >

              {/* The star list is the only part allowed to scroll. It takes the
                  leftover height (flex-1 + min-h-0) so the footer below it can
                  never be pushed past the bottom edge of the palace — that was
                  the old bug: with `mt-auto` alone, a long list simply shoved
                  the footer out of an `overflow-hidden` box.
                  Major and minor stars keep one line each (they carry the
                  brightness dot and the Si Hua tag); misc stars flow inline,
                  several per line, which is what buys the height back. */}
              {/* content-start is NOT optional: a wrapping flex container whose
                  height exceeds its content defaults to align-content: stretch,
                  which shares the leftover height out among the lines. With
                  flex-1 giving this box all the spare height of the palace,
                  two stars ended up pushed to the top and bottom of a huge gap.
                  content-start packs the lines together and leaves the slack
                  below, where it belongs. */}
              <div
                className={
                  "mt-1 flex w-full flex-1 flex-wrap content-start items-baseline gap-x-1.5 " +
                  // Desktop Version grows the row instead of scrolling inside
                  // the palace — see the note on the grid above.
                  (desktop ? "" : "min-h-0 overflow-y-auto")
                }
                // Desktop Version: every descendant inherits this, so the
                // per-element sizes below are dropped in that mode.
                style={starFont ? { fontSize: `${starFont}px` } : undefined}
              >
                {/* One star = one line. The pinyin is the only part allowed to
                    shrink/truncate; brightness and the Si Hua tag must never
                    wrap onto their own line, or a narrow palace turns into a
                    ladder and pushes later stars out of the box. */}
                {p.stars
                  .filter((s) =>
                    s.tier === "misc" ? showMisc : s.isMajor || showMinor
                  )
                  .map((s) => {
                  // Misc tier: pinyin only, italic, flowing inline. 漢字 and the
                  // full name live in the tooltip and in the palace detail
                  // panel, so nothing is lost on a touch screen where there is
                  // no hover. Measured on Bambang's chart at 672px with minor +
                  // misc both on: one line per misc star overflowed 8 palaces
                  // by up to 46px; flowing them inline brings that to 2 palaces
                  // by 13px, which the pinned footer absorbs.
                  if (s.tier === "misc") {
                    return (
                      <span
                        key={s.name}
                        title={`${s.nameZh} ${s.name}`}
                        className={
                          "shrink-0 whitespace-nowrap italic leading-tight " +
                          (desktop ? "" : "text-[8px] ") +
                          (SHA_STARS.has(s.name) ? "text-red-600" : "text-neutral-400/90")
                        }
                      >
                        {/* A 465px palace has room to spell it out again; the
                            pinyin-only form exists purely to save height in the
                            672px layout. */}
                        {desktop && <span className="mr-0.5">{s.nameZh}</span>}
                        {s.name}
                      </span>
                    );
                  }
                  // Highlighted when the ACTIVE palace sends a transformation
                  // to this star: filled with that Si Hua's colour, white text.
                  const hl = huaOfStar.get(s.name);
                  const hlStyle = hl
                    ? { backgroundColor: HUA_COLOR[hl], color: "#fff" }
                    : undefined;
                  const hlClass = hl ? "rounded-sm px-[3px]" : "";
                  // Reading for this star IN THIS PALACE. Missing for the misc
                  // tier (no text written for those yet), and such a star stays
                  // unclickable rather than opening an empty panel.
                  const meaning = STAR_MEANINGS[s.name]?.[p.name];
                  return (
                  <div
                    key={s.name}
                    onClick={(e) => {
                      // Desktop Version: klik bintang = mode bintang. Klik
                      // dihentikan di sini supaya palace-nya TIDAK ikut
                      // terpilih — kalau lolos, garis keluar palace dan panah
                      // masuk bintang akan tergambar bersamaan.
                      // Di mode normal perilaku lama dipertahankan: satu ketukan
                      // memberi garis Si Hua palace sekaligus penjelasan bintang.
                      if (desktop) {
                        e.stopPropagation();
                        setSelected(null);
                        setHovered(null);
                        setFocusStar((cur) =>
                          cur && cur.name === s.name && cur.palace === p.name
                            ? null
                            : { name: s.name, nameZh: s.nameZh, palace: p.name }
                        );
                      }
                      setSelectedStar((cur) =>
                        cur && cur.name === s.name && cur.palace === p.name
                          ? null
                          : meaning
                            ? { name: s.name, nameZh: s.nameZh, palace: p.name, text: meaning }
                            : null
                      );
                    }}
                    title={
                      desktop
                        ? "Klik untuk melihat Si Hua yang masuk ke bintang ini"
                        : meaning
                          ? "Klik untuk penjelasannya"
                          : undefined
                    }
                    style={
                      // Bintang utama & minor dibesarkan 1,3x di Desktop
                      // Version (permintaan Bambang): kotaknya sangat lega, dan
                      // sejak baris grid boleh tumbuh, font besar tidak lagi
                      // memotong apa pun — paling banter chart jadi lebih
                      // tinggi. Misc, 博士/長生, dan footer tetap di ukuran dasar.
                      desktop && starFont
                        ? { fontSize: `${Math.round(starFont * 1.3)}px` }
                        : undefined
                    }
                    className={
                      (desktop || meaning ? "cursor-pointer rounded-sm hover:bg-amber-100/70 " : "") +
                      (focusStar && focusStar.name === s.name && focusStar.palace === p.name
                        ? "bg-amber-200/80 "
                        : "") +
                      // Narrow screens stack the pinyin under the Chinese name;
                      // from `sm` up there is room for one line per star.
                      // `w-full` claims a whole line inside the wrapping list,
                      // so only the misc chips share lines with each other.
                      "w-full flex flex-col sm:flex-row sm:items-baseline sm:gap-1 " +
                      (s.isMajor
                        // Bigger from `sm` up (+20%) at Bambang's request. On a
                        // phone the palace is ~93px wide and the pinyin already
                        // sits on its own line, so the small size stays there.
                        // Desktop Version gets another step up — the box is
                        // more than twice as wide and its vertical space sits
                        // mostly idle.
                        ? (desktop
                            ? "leading-tight text-neutral-700"
                            : "text-[10px] sm:text-[12px] leading-tight text-neutral-700")
                        : (desktop ? "leading-tight " : "text-[9px] sm:text-[10px] leading-tight ") +
                          // 煞星 in red. A star highlighted by an incoming Si Hua
                          // carries an inline white colour, which still wins.
                          (SHA_STARS.has(s.name) ? "text-red-600" : "text-neutral-400"))
                    }
                  >
                    <span className="flex items-baseline gap-1 shrink-0">
                      {/* The Si Hua arrows aim at THIS element, so the ref
                          belongs on the name, not on the full-width row. */}
                      <span
                        ref={(el) => {
                          if (el) starRefs.current.set(s.name, el);
                          else starRefs.current.delete(s.name);
                        }}
                        className={hlClass}
                        style={hlStyle}
                      >
                        {s.nameZh}
                      </span>
                      <span className="sm:hidden">
                        <BrightnessMark level={s.brightness} />
                      </span>
                      <span className="sm:hidden ml-auto flex items-baseline whitespace-nowrap">
                        {s.natalSiHua && (
                          <span
                            className="ml-1 text-[9px] font-medium"
                            style={{ color: HUA_COLOR[s.natalSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"] }}
                          >
                            {s.natalSiHua}
                          </span>
                        )}
                        {s.selfSiHua && (
                          <span
                            className="ml-1 text-[8px] font-medium rounded-sm border px-[2px] leading-[1.4]"
                            style={{
                              color: HUA_COLOR[s.selfSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"],
                              borderColor: HUA_COLOR[s.selfSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"],
                            }}
                            title={`自化 ${s.selfSiHua} — transformed by this palace's own stem`}
                          >
                            自{s.selfSiHua}
                          </span>
                        )}
                      </span>
                    </span>
                    <span
                      className={
                        "truncate " + hlClass + " " +
                        (hl
                          ? s.isMajor
                            ? desktop ? "" : "text-[9px] sm:text-[10px]"
                            : desktop ? "" : "text-[8px]"
                          : s.isMajor
                            ? (desktop ? "text-neutral-500" : "text-[9px] sm:text-[10px] text-neutral-500")
                            : (desktop ? "" : "text-[8px] ") +
                              (SHA_STARS.has(s.name) ? "text-red-500" : "text-neutral-400"))
                      }
                      style={hlStyle}
                    >
                      {s.name}
                    </span>
                    <span className="hidden sm:inline-flex shrink-0">
                      <BrightnessMark level={s.brightness} inheritSize={desktop} />
                    </span>
                    {/* Normally the Si Hua tags are pushed to the right edge of
                        the palace (ml-auto), which reads well in a 166px box. In
                        Desktop Version the box is 354px, so ml-auto strands the
                        tag half a palace away from the star it belongs to —
                        there they sit right after the name instead. */}
                    <span
                      className={
                        "hidden sm:flex shrink-0 items-baseline whitespace-nowrap " +
                        (desktop ? "ml-1.5" : "ml-auto")
                      }
                    >
                      {s.natalSiHua && (
                        <span
                          className={"ml-1 font-medium " + (desktop ? "" : "text-[9px]")}
                          style={{ color: HUA_COLOR[s.natalSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"] }}
                        >
                          {s.natalSiHua}
                        </span>
                      )}
                      {s.selfSiHua && (
                        <span
                          className={
                            "ml-1 font-medium rounded-sm border px-[2px] leading-[1.4] " +
                            (desktop ? "" : "text-[8px]")
                          }
                          style={{
                            color: HUA_COLOR[s.selfSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"],
                            borderColor: HUA_COLOR[s.selfSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"],
                          }}
                          title={`自化 ${s.selfSiHua} — transformed by this palace's own stem`}
                        >
                          自{s.selfSiHua}
                        </span>
                      )}
                      {/* 大限 / 流年 tags, after natal and 自化 so the reading
                          order is natal → self → decade → year. The map is
                          empty unless the Desktop Version layer is open. */}
                      {layerSiHua.get(s.name)?.map((t) => (
                        <span
                          key={t.layer + t.hua}
                          className="ml-1 font-medium"
                          style={{ color: LAYER_COLOR[t.layer] }}
                          title={
                            t.layer === "D"
                              ? `大限四化 ${t.huaZh} — dari stem palace 大限`
                              : `流年四化 ${t.huaZh} — dari ${liuNianStem === "year" ? "stem tahun (年干)" : "stem palace (宮干)"}`
                          }
                        >
                          {t.layer}
                          {t.hua}
                        </span>
                      ))}
                    </span>
                  </div>
                  );
                  })}

                {/* 博士十二神 / 長生十二神 close the star list. They are palace
                    attributes, not stars, so they sit just below it — and the
                    footer line beside the Da Xian label stays free for
                    whatever we add later. */}
                {/* The two twelve-god cycles ride along with the misc tier:
                    they are reading detail, not structure, so they stay hidden
                    until "Show Misc Star" is on. */}
                <div
                  className={
                    // w-full: these two are palace attributes, so they take
                    // their own line rather than flowing among the misc chips.
                    "mt-0.5 w-full flex-col gap-0 leading-tight text-neutral-400 " +
                    (desktop ? "" : "text-[8px] ") +
                    (showMisc ? "flex" : "hidden")
                  }
                >
                  {p.boShi && (
                    <span className="flex items-baseline gap-0.5" title="Bo Shi">
                      <span>{p.boShi.nameZh}</span>
                      <span>{p.boShi.name}</span>
                    </span>
                  )}
                  {p.changSheng && (
                    <span className="flex items-baseline gap-0.5" title="Chang Sheng">
                      <span>{p.changSheng.nameZh}</span>
                      <span>{p.changSheng.name}</span>
                    </span>
                  )}
                </div>
              </div>

              {/* ── palace footer ───────────────────────────────────────────
                  Mirrors the reference app: the two twelve-god cycles and the
                  Da Xian palace name sit on one line, then a 3x2 grid holding
                  ganzhi / Da Xian range / Liu Nian year, and below them the
                  branch / palace name / lunar age. */}
              <div
                className={
                  "mt-auto w-full shrink-0 pt-0.5 leading-tight text-neutral-400 " +
                  (desktop ? "text-[11px]" : "text-[8px]")
                }
              >
                <div className="flex items-baseline justify-between gap-1 whitespace-nowrap">
                  {/* 借宮 — label palace ini kalau palace yang diklik dijadikan
                      命宮. Diletakkan di ujung kiri baris yang sama dengan
                      label cincin D/A/M (yang tetap rata kanan), jadi keduanya
                      bersebelahan dan tidak saling menggeser. Palace jangkarnya
                      sendiri tidak diberi label (ia otomatis "1.Self"). */}
                  {interchange && interchange.anchor !== p.branch && interchange.map.get(p.branch) ? (
                    <span
                      className="shrink-0 rounded-sm bg-neutral-200 px-1 text-neutral-700"
                      title={`借宮: kalau ${palaceByBranch.get(interchange.anchor)?.name} dijadikan 命宮, palace ini menjadi ${interchange.map.get(p.branch)!.name}`}
                    >
                      {interchange.map.get(p.branch)!.n}.{interchange.map.get(p.branch)!.name}
                    </span>
                  ) : (
                    <span />
                  )}
                  <span className="flex shrink-0 flex-col items-end leading-tight">
                    {monthly?.names.get(p.branch) && (
                      <span className="text-teal-600" title="流月十二宮">
                        M{monthly.names.get(p.branch)}
                      </span>
                    )}
                    {annual?.names.get(p.branch) && (
                      <span className="text-violet-600" title="流年十二宮">
                        A{annual.names.get(p.branch)}
                      </span>
                    )}
                    {activeDecade?.names.get(p.branch) && (
                      <span
                        // Three stacked labels get tight on a phone, so in
                        // month mode the outermost ring (D) drops out below sm.
                        className={[
                          "text-rose-600",
                          monthly ? "hidden sm:inline" : "",
                        ].join(" ")}
                        title="大限十二宮"
                      >
                        D{activeDecade.names.get(p.branch)}
                      </span>
                    )}
                  </span>
                </div>

                <div className="mt-0.5 grid grid-cols-[auto_1fr_auto] items-center gap-x-1 border-t border-neutral-200 pt-0.5">
                  <span className="text-neutral-500">{p.stem}</span>
                  {/* Clicking the age range switches the whole chart to that
                      decade; stopPropagation so it does not also select the
                      palace. */}
                  <span
                    onClick={(e) => {
                      e.stopPropagation();
                      setDecadeStart(p.ageRange[0]);
                      setSelectedYear(null); // clicking a decade leaves year mode
                      setSelectedMonth(null); // ...and month mode with it
                    }}
                    title={`Da Xian ${p.ageRange[0]}-${p.ageRange[1]} — klik untuk pilih dekade ini`}
                    className={[
                      "cursor-pointer rounded px-0.5 text-center",
                      activeDecade?.start === p.ageRange[0]
                        ? "bg-amber-200 font-medium text-neutral-800"
                        : "hover:bg-neutral-200",
                    ].join(" ")}
                  >
                    {p.ageRange[0]}-{p.ageRange[1]}
                  </span>
                  {/* Decade mode: the Liu Nian year. Year mode: the lunar
                      month that 流年斗君 puts on this palace. "M" is the LUNAR
                      month, not January/February — hence the tooltip. */}
                  {(() => {
                    if (!annual) {
                      const y = activeDecade?.years.get(p.branch);
                      return (
                        <span className="text-right text-neutral-500">{y?.year ?? ""}</span>
                      );
                    }
                    const m = annual.months.get(p.branch);
                    if (!m) return <span />;
                    const isSelM = monthly?.month === m;
                    return (
                      <span
                        onClick={(e) => {
                          e.stopPropagation();
                          setSelectedMonth((cur) => (cur === m ? null : m));
                        }}
                        className={[
                          "cursor-pointer rounded px-0.5 text-right",
                          isSelM
                            ? "bg-teal-200 font-medium text-neutral-800"
                            : "text-neutral-500 hover:bg-neutral-200",
                        ].join(" ")}
                        title={`流月: bulan lunar ke-${m} tahun ${annual.year} (bukan bulan Masehi). Klik untuk pasang 流月十二宮.`}
                      >
                        M{m}
                      </span>
                    );
                  })()}

                  <span className="text-neutral-500">{p.branch}</span>
                  <span
                    className={
                      "truncate text-center text-neutral-700 " +
                      (desktop ? "text-[12px]" : "text-[9px]")
                    }
                  >
                    {p.nameZh.charAt(0)} {p.name}
                  </span>
                  {/* The year cell is clickable: it switches the chart into
                      year mode. The age moves into its tooltip there. */}
                  {(() => {
                    const cell = activeDecade?.years.get(p.branch);
                    if (!cell) return <span />;
                    const isSel = annual?.year === cell.year;
                    return (
                      <span
                        onClick={(e) => {
                          e.stopPropagation();
                          setSelectedYear((y) => (y === cell.year ? null : cell.year));
                          // A new year re-seeds 流年斗君, so the old month is void.
                          setSelectedMonth(null);
                        }}
                        title={`${cell.year} — usia ${cell.age} (lunar). Klik untuk lihat bulanannya.`}
                        className={[
                          "cursor-pointer rounded px-0.5 text-right",
                          isSel
                            ? "bg-amber-200 font-medium text-neutral-800"
                            : annual
                              ? "text-neutral-400 hover:bg-neutral-200"
                              : "text-neutral-500 hover:bg-neutral-200",
                        ].join(" ")}
                      >
                        {annual ? cell.year : cell.age}
                      </span>
                    );
                  })()}
                </div>
              </div>
            </button>
          ))}

          {/* center info panel spans the 2x2 middle block */}
          <div
            style={{ gridRow: "2 / span 2", gridColumn: "2 / span 2" }}
            className={
              "relative flex flex-col items-center overflow-y-auto text-center pb-5 bg-neutral-50 border border-neutral-200 " +
              (desktop ? "p-4 " : "p-2 ") +
              // Centred normally; top-aligned for a star reading, because a
              // centred flex child that overflows gets clipped at the TOP and
              // the first line becomes unreachable by scrolling.
              (selectedStar ? "justify-start" : "justify-center")
            }
          >
            {/* The centre block always shows the birth data. Hovering a palace
                must not wipe it out — the active palace's own details live in
                the panel below the grid instead. */}
            {!info || editing ? (
                <BirthForm
                  defaults={
                    info
                      ? { name: info.name, date: info.solarDate, time: info.solarTime, gender: info.gender }
                      : { name: "" } // no birth data yet: BirthForm fills in "now"
                  }
                  onGenerate={(next) => {
                    setInfo(next);
                    setEditing(false);
                    setIsNow(false);
                    // A new chart means new palaces; the old reading is stale.
                    setSelectedStar(null);
                  }}
                />
              ) : selectedStar ? (
                // A star was clicked. This is the one thing allowed to replace
                // the birth data, and only ever by an explicit click — the ✕
                // (or clicking that star again) brings the birth data back.
                <div className="w-full text-left">
                  <div className="flex items-start justify-between gap-2">
                    <div>
                      <div className="text-[13px] font-medium leading-tight text-neutral-800">
                        {selectedStar.nameZh} {selectedStar.name}
                      </div>
                      <div className="text-[10px] leading-tight text-neutral-500">
                        di palace {selectedStar.palace}
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedStar(null);
                        setFocusStar(null);
                      }}
                      title="Tutup, kembali ke data kelahiran"
                      className="shrink-0 rounded px-1 text-[12px] leading-none text-neutral-400 hover:bg-neutral-200 hover:text-neutral-700"
                    >
                      ✕
                    </button>
                  </div>
                  {/* Daftar pengirim, supaya panah di grid bisa dibaca sebagai
                      kalimat dan bukan cuma ditebak dari arahnya. */}
                  {focusStar && focusStar.name === selectedStar.name && incoming.length > 0 && (
                    <div className="mt-2 border-t border-neutral-200 pt-1.5">
                      <div className="text-[9px] uppercase tracking-wide text-neutral-400">
                        Si Hua yang masuk ke bintang ini
                      </div>
                      <ul className="mt-1 space-y-0.5">
                        {incoming.map((src, i) => (
                          <li key={i} className="flex items-center gap-1.5 text-[10px] leading-snug">
                            <span
                              className="inline-block h-2 w-2 shrink-0 rounded-full"
                              style={{ backgroundColor: HUA_COLOR[src.hua] }}
                            />
                            <span className="font-medium" style={{ color: HUA_COLOR[src.hua] }}>
                              {HUA_LABEL[src.hua]}
                            </span>
                            <span className="text-neutral-400">dari</span>
                            <span className="text-neutral-700">
                              {src.palace} ({src.stem})
                            </span>
                            {src.isSelf && (
                              <span className="text-neutral-400">— 自化</span>
                            )}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                  <p className="mt-1.5 text-[11px] leading-snug text-neutral-700">
                    {selectedStar.text}
                  </p>
                  <p className="mt-2 text-[9px] leading-snug text-neutral-400">
                    Makna dasar menurut palace. Belum memperhitungkan terang-gelap,
                    Si Hua, dan bintang pendamping.
                  </p>
                </div>
              ) : showBazi ? (
                <>
                  <BaziPanel info={info} compact />
                  <button
                    type="button"
                    onClick={() => setEditing(true)}
                    className="mt-1.5 text-[9px] text-neutral-400 underline hover:text-neutral-600"
                  >
                    Ubah data
                  </button>
                </>
              ) : (
                <>
                  {/* An auto-generated chart is labelled by what it is — the
                      chart for right now — rather than "(tanpa nama)", so a
                      visitor understands it and knows to enter their own data
                      below. */}
                  <div className="text-xs font-medium text-neutral-700 truncate max-w-full">
                    {isNow ? "Waktu saat ini" : info.name}
                  </div>
                  <div className="text-[10px] text-neutral-500 mt-1">{info.solarDate} · {info.solarTime}</div>
                  <div className="text-[10px] text-neutral-500">{info.lunarText}</div>
                  {/* Same lunar date in plain numerals — day / month / year. */}
                  <div
                    className="text-[10px] text-neutral-500"
                    title="Tanggal lunar: hari / bulan / tahun"
                  >
                    Lunar: {info.lunarDay} / {info.lunarMonth}
                    {info.isLeapMonth && <span className="text-amber-700"> (闰)</span>} / {info.lunarYear}
                  </div>
                  <div className="text-[10px] text-neutral-500">
                    {info.bazi.pillars.year.tg}{info.bazi.pillars.year.dz} · {info.zodiac} ·{" "}
                    {info.gender === "male" ? "Pria" : "Wanita"}
                  </div>
                  <div className="text-[9px] text-neutral-400 mt-2">Tap a palace to see its flying Si Hua</div>
                  <button
                    type="button"
                    onClick={() => setEditing(true)}
                    className="mt-1 text-[9px] text-neutral-400 underline hover:text-neutral-600"
                  >
                    Ubah data
                  </button>
                </>
            )}

            {/* Desktop Version only: the palace panel that normally flows
                below the grid is shown here as well, because in a full-screen
                layer there is nothing below the grid to scroll to. Exactly the
                same content as the panel underneath — no new design. */}
            {desktop && activePalace && flying && !editing && (
              <div className="mt-3 w-full border-t border-neutral-200 pt-2 text-left">
                <div className="mb-1 text-[12px] font-medium text-neutral-800">
                  {activePalace.nameZh} {activePalace.name} — flying Si Hua ({activePalace.stem})
                </div>
                <div className="mb-2 flex flex-wrap gap-x-2 gap-y-0.5 text-[11px]">
                  {activePalace.stars.map((s) => (
                    <span
                      key={s.name}
                      className={
                        "whitespace-nowrap " +
                        (s.isMajor
                          ? "text-neutral-800"
                          : s.tier === "misc"
                            ? "italic text-neutral-400"
                            : "text-neutral-600")
                      }
                    >
                      {s.nameZh} <span className="text-neutral-500">{s.name}</span>
                      {s.natalSiHua && (
                        <span
                          className="ml-1 font-medium"
                          style={{ color: HUA_COLOR[s.natalSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"] }}
                        >
                          {s.natalSiHua}
                        </span>
                      )}
                    </span>
                  ))}
                  {activePalace.stars.length === 0 && (
                    <span className="text-neutral-400">Palace kosong (無主星)</span>
                  )}
                </div>
                <ul className="space-y-0.5 text-[11px]">
                  {(["lu", "quan", "ke", "ji"] as const).map((k) => {
                    const target = flying[k];
                    const isSelf = target.palace === activePalace.name;
                    return (
                      <li key={k} className="flex flex-wrap items-center gap-x-1.5 text-neutral-700">
                        <span
                          className="inline-block h-2 w-2 shrink-0 rounded-full"
                          style={{ backgroundColor: HUA_COLOR[k] }}
                        />
                        <span className="font-medium whitespace-nowrap" style={{ color: HUA_COLOR[k] }}>
                          {HUA_LABEL[k]}
                        </span>
                        <span className="whitespace-nowrap">
                          {STAR_ZH_BY_NAME.get(target.star) ?? ""} {target.star}
                        </span>
                        <span className="text-neutral-400">→</span>
                        <span className="whitespace-nowrap">
                          {target.palace ?? "not placed"}
                          {isSelf && <span className="ml-1 text-neutral-400">(自化)</span>}
                        </span>
                      </li>
                    );
                  })}
                </ul>
              </div>
            )}

            {/* Bottom row of the centre block: it travels with the chart, so
                it stays visible in any screenshot of the grid. */}
            <div className="absolute inset-x-2 bottom-1 flex items-end gap-2">
              <a
                href="https://www.destinyreading.id"
                target="_blank"
                rel="noopener noreferrer"
                className={`${brandFont.className} text-[11px] leading-none tracking-[0.12em] text-neutral-500 transition-colors hover:text-amber-700`}
              >
                © www.destinyreading.id
              </a>
              {/* "Transit Chart →" used to live here as a doorway to a separate
                  decade view. It is gone: 大限 / 流年 / 流月 are now selected in
                  the palace footer itself, so a separate transit screen has
                  nothing left to do. */}
            </div>
          </div>
        </div>

        {/* SVG overlay for flying Si Hua lines. Drawn in PIXEL space (viewBox
            matches the measured box) so each line can end on its own star row.
            Falls back to the palace centre when the target star is not
            rendered — e.g. a minor star while "Show Minor Star" is off. */}
        {geom && (
          <svg
            viewBox={`0 0 ${geom.w} ${geom.h}`}
            className="absolute inset-0 w-full h-full pointer-events-none"
          >
            <defs>
              {(["lu", "quan", "ke", "ji"] as const).map((k) => (
                <marker
                  key={k}
                  id={`hua-arrow-${k}`}
                  viewBox="0 0 10 10"
                  refX="9"
                  refY="5"
                  markerWidth="5"
                  markerHeight="5"
                  orient="auto-start-reverse"
                >
                  <path d="M 0 1 L 10 5 L 0 9 z" fill={HUA_COLOR[k]} />
                </marker>
              ))}
            </defs>

            {/* Permanent arrows: a palace's OWN stem (宮干四化) sending a
                transformation into the palace directly opposite it. Only those
                are drawn — a transformation landing anywhere else is shown on
                hover, not permanently. */}
            {chart.palaces.map((src) => {
              const oppBranch =
                BRANCH_ORDER[(BRANCH_ORDER.indexOf(src.branch) + 6) % 12];
              const f = resolveFlyingSiHua(src.stem, (n) => starIndex.get(n) ?? null);
              const cellW = geom.w / 4;
              const cellH = geom.h / 4;
              return (["lu", "quan", "ke", "ji"] as const).map((k) => {
                const t = f[k];
                if (!t.palace) return null;
                if (branchByName.get(t.palace) !== oppBranch) return null;
                const opp = palaceByBranch.get(oppBranch);
                if (!opp) return null;
                // A short stub inside the source palace pointing toward the
                // opposite one — a full line across the grid would cut through
                // the centre block and crowd the hover lines.
                const c = {
                  x: (src.grid.col + 0.5) * cellW,
                  y: (src.grid.row + 0.5) * cellH,
                };
                const o = {
                  x: (opp.grid.col + 0.5) * cellW,
                  y: (opp.grid.row + 0.5) * cellH,
                };
                const dx = o.x - c.x;
                const dy = o.y - c.y;
                const len = Math.hypot(dx, dy) || 1;
                const n = { x: dx / len, y: dy / len };
                // Offset each hua slightly so two stubs from the same palace
                // do not sit on top of each other.
                const lane = (["lu", "quan", "ke", "ji"] as const).indexOf(k) - 1.5;
                const px = -n.y * lane * 5;
                const py = n.x * lane * 5;
                // Distance from the cell centre to its edge along n, so the
                // stub starts just OUTSIDE the palace box and points inward,
                // toward the opposite palace.
                const edge = Math.min(
                  Math.abs(n.x) < 1e-6 ? Infinity : (cellW / 2) / Math.abs(n.x),
                  Math.abs(n.y) < 1e-6 ? Infinity : (cellH / 2) / Math.abs(n.y)
                );
                const from = { x: c.x + n.x * (edge + 3) + px, y: c.y + n.y * (edge + 3) + py };
                const to = { x: c.x + n.x * (edge + 27) + px, y: c.y + n.y * (edge + 27) + py };
                return (
                  <line
                    key={`opp-${src.branch}-${k}`}
                    x1={from.x}
                    y1={from.y}
                    x2={to.x}
                    y2={to.y}
                    stroke={HUA_COLOR[k]}
                    strokeWidth={1.4}
                    markerEnd={`url(#hua-arrow-${k})`}
                  >
                    <title>{`${src.name} ${src.stem} → ${k.toUpperCase()} ${t.star} → ${opp.name} (seberang)`}</title>
                  </line>
                );
              });
            })}

            {/* 沖 clash: the palace struck by Hua Ji also afflicts the palace
                opposite it (branch + 6). Drawn as a dashed red line from the
                afflicted star to the centre of that opposite palace. */}
            {showClash && activePalace && flying && (() => {
              const jiTarget = flying.ji;
              if (!jiTarget.palace) return null;
              const hitBranch = branchByName.get(jiTarget.palace);
              if (!hitBranch) return null;
              const oppBranch =
                BRANCH_ORDER[(BRANCH_ORDER.indexOf(hitBranch) + 6) % 12];
              const oppPalace = palaceByBranch.get(oppBranch);
              if (!oppPalace) return null;

              const cellW = geom.w / 4;
              const cellH = geom.h / 4;
              const hitPalace = palaceByBranch.get(hitBranch)!;
              const star = geom.stars[jiTarget.star];
              const from = star
                ? { x: star.x, y: star.y }
                : {
                    x: (hitPalace.grid.col + 0.5) * cellW,
                    y: (hitPalace.grid.row + 0.5) * cellH,
                  };
              const to = {
                x: (oppPalace.grid.col + 0.5) * cellW,
                y: (oppPalace.grid.row + 0.5) * cellH,
              };
              return (
                <line
                  x1={from.x}
                  y1={from.y}
                  x2={to.x}
                  y2={to.y}
                  stroke={HUA_COLOR.ji}
                  strokeWidth={1.2}
                  strokeDasharray="5 4"
                  markerEnd="url(#hua-arrow-ji)"
                />
              );
            })()}

            {/* Mode bintang: setiap panah yang MASUK ke bintang terpilih.
                Pangkalnya di tengah palace pengirim, ujungnya di tepi terdekat
                nama bintang supaya kepala panah tidak menutupi namanya —
                aturan yang sama dengan garis keluar. 自化 digambar sebagai
                cincin putus-putus, bukan panah, karena pengirim dan penerima
                berada di kotak yang sama. */}
            {focusStar && incoming.length > 0 && (() => {
              const star = geom.stars[focusStar.name];
              const cellW = geom.w / 4;
              const cellH = geom.h / 4;
              return incoming.map((src, idx) => {
                const from = palaceByBranch.get(src.branch);
                if (!from) return null;
                const origin = {
                  x: (from.grid.col + 0.5) * cellW,
                  y: (from.grid.row + 0.5) * cellH,
                };
                if (src.isSelf) {
                  return star ? (
                    <ellipse
                      key={`in-self-${idx}`}
                      cx={star.x}
                      cy={star.y}
                      rx={(star.right - star.left) / 2 + 4}
                      ry={11}
                      fill="none"
                      stroke={HUA_COLOR[src.hua]}
                      strokeWidth={1.4}
                      strokeDasharray="4 3"
                    >
                      <title>{`自化 ${src.hua.toUpperCase()} — ${src.palace} ${src.stem}`}</title>
                    </ellipse>
                  ) : null;
                }
                const to = star
                  ? { x: origin.x <= star.x ? star.left - 4 : star.right + 4, y: star.y }
                  : origin;
                return (
                  <line
                    key={`in-${idx}`}
                    x1={origin.x}
                    y1={origin.y}
                    x2={to.x}
                    y2={to.y}
                    stroke={HUA_COLOR[src.hua]}
                    strokeWidth={1.6}
                    markerEnd={`url(#hua-arrow-${src.hua})`}
                  >
                    <title>{`${src.palace} ${src.stem} → ${src.hua.toUpperCase()} → ${focusStar.nameZh} ${focusStar.name}`}</title>
                  </line>
                );
              });
            })()}

            {activePalace && flying && (["lu", "quan", "ke", "ji"] as const).map((k) => {
              const target = flying[k];
              if (!target.palace) return null; // star not placed in this chart
              const targetBranch = branchByName.get(target.palace);
              if (!targetBranch) return null;
              const targetPalace = palaceByBranch.get(targetBranch)!;

              const cellW = geom.w / 4;
              const cellH = geom.h / 4;
              const from = {
                x: (activePalace.grid.col + 0.5) * cellW,
                y: (activePalace.grid.row + 0.5) * cellH,
              };
              const star = geom.stars[target.star];

              if (targetBranch === activePalace.branch) {
                // 自化 self-transformation: ring the star itself when we can
                // see it, otherwise ring the palace.
                return star ? (
                  <ellipse
                    key={k}
                    cx={star.x}
                    cy={star.y}
                    rx={(star.right - star.left) / 2 + 3}
                    ry={9}
                    fill="none"
                    stroke={HUA_COLOR[k]}
                    strokeWidth={1.2}
                    strokeDasharray="4 3"
                  />
                ) : (
                  <circle
                    key={k}
                    cx={from.x}
                    cy={from.y}
                    r={Math.min(cellW, cellH) * 0.35}
                    fill="none"
                    stroke={HUA_COLOR[k]}
                    strokeWidth={1.2}
                    strokeDasharray="4 3"
                  />
                );
              }

              // Land on the near edge of the star row so the arrowhead points
              // at the star instead of covering its name.
              const to = star
                ? { x: from.x <= star.x ? star.left - 3 : star.right + 3, y: star.y }
                : {
                    x: (targetPalace.grid.col + 0.5) * cellW,
                    y: (targetPalace.grid.row + 0.5) * cellH,
                  };

              return (
                <line
                  key={k}
                  x1={from.x}
                  y1={from.y}
                  x2={to.x}
                  y2={to.y}
                  stroke={HUA_COLOR[k]}
                  strokeWidth={1.2}
                  markerEnd={`url(#hua-arrow-${k})`}
                />
              );
            })}
          </svg>
        )}
      </div>

      {/* While the picture is being built the grid itself is off-screen, so
          without this the page would simply look empty for a second. */}
      {exportBusy && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-white/80">
          <div className="rounded border border-neutral-300 bg-white px-4 py-3 text-[12px] text-neutral-700 shadow-sm">
            Menyiapkan gambar…
          </div>
        </div>
      )}

      {exportErr && (
        <div className="mt-3 border border-rose-200 bg-rose-50 p-2 text-[11px] text-rose-700">
          {exportErr}
        </div>
      )}

      {/* The finished picture stays on the page. On a desktop the download has
          already happened and this is just confirmation; on an iPad, where
          WebKit routinely ignores a scripted download, this IS the way to keep
          it — press and hold, then "Save to Photos". */}
      {pngUrl && (
        <div className="mt-3 border border-neutral-200 p-2">
          <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-neutral-600">
            <span className="font-medium text-neutral-800">Gambar siap</span>
            <a href={pngUrl} download="zwds-chart.png" className="underline hover:text-neutral-900">
              Unduh lagi
            </a>
            <span className="text-neutral-400">
              Di iPad/iPhone: tekan-lama gambar di bawah, lalu Simpan ke Foto.
            </span>
            <button
              type="button"
              onClick={() => setPngUrl(null)}
              className="ml-auto rounded px-1 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700"
              title="Tutup"
            >
              ✕
            </button>
          </div>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={pngUrl} alt="Chart ZWDS" className="w-full border border-neutral-200" />
        </div>
      )}

      {/* Ba Zi (four pillars) — toggled by "Show Ba Zi" */}
      {showBazi && info && !desktop && <BaziPanel info={info} />}

      {/* detail panel — flows normally below the grid on every screen size,
          so it works identically on mobile without fixed positioning. In the
          full-screen layer the grid already fills the viewport, so the same
          panel is rendered inside the centre block instead. */}
      {!desktop && activePalace && flying && (
        <div className="mt-4 border border-neutral-200 p-4 text-sm">
          <div className="font-medium text-neutral-800 mb-2">
            {activePalace.nameZh} {activePalace.name} — flying Si Hua ({activePalace.stem})
          </div>

          {/* Every star in this palace, spelled out. In the grid the misc tier
              shows pinyin only to save height; this list is where the 漢字 and
              the full set live, and unlike a tooltip it works on a touch
              screen. */}
          <div className="mb-3 flex flex-wrap gap-x-3 gap-y-0.5 border-b border-neutral-200 pb-2 text-xs">
            {activePalace.stars.map((s) => (
              <span
                key={s.name}
                className={
                  "whitespace-nowrap " +
                  (s.isMajor
                    ? "text-neutral-800"
                    : s.tier === "misc"
                      ? "italic text-neutral-400"
                      : "text-neutral-600")
                }
              >
                {s.nameZh} <span className="text-neutral-500">{s.name}</span>
                {s.natalSiHua && (
                  <span
                    className="ml-1 font-medium"
                    style={{ color: HUA_COLOR[s.natalSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"] }}
                  >
                    {s.natalSiHua}
                  </span>
                )}
              </span>
            ))}
            {activePalace.stars.length === 0 && (
              <span className="text-neutral-400">Palace kosong (無主星)</span>
            )}
          </div>

          <ul className="space-y-1">
            {(["lu", "quan", "ke", "ji"] as const).map((k) => {
              const target = flying[k];
              const isSelf = target.palace === activePalace.name;
              return (
                <li key={k} className="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-neutral-700">
                  <span className="inline-flex items-center gap-2 shrink-0">
                    <span className="inline-block w-2.5 h-2.5 rounded-full shrink-0" style={{ backgroundColor: HUA_COLOR[k] }} />
                    <span className="font-medium whitespace-nowrap" style={{ color: HUA_COLOR[k] }}>{HUA_LABEL[k]}</span>
                  </span>
                  <span className="inline-flex items-center gap-2 whitespace-nowrap">
                    <span>
                      {STAR_ZH_BY_NAME.get(target.star) ?? ""}
                      <span className="ml-1">{target.star}</span>
                    </span>
                    <span className="text-neutral-400">→</span>
                    <span>
                      {target.palace ?? "not placed in chart"}
                      {isSelf && <span className="ml-1 text-neutral-400">(自化 self-transform)</span>}
                    </span>
                  </span>
                </li>
              );
            })}
          </ul>
          {showClash && flying.ji.palace && (() => {
            const hitBranch = branchByName.get(flying.ji.palace);
            if (!hitBranch) return null;
            const opp = palaceByBranch.get(
              BRANCH_ORDER[(BRANCH_ORDER.indexOf(hitBranch) + 6) % 12]
            );
            if (!opp) return null;
            return (
              <div className="mt-2 border-t border-neutral-200 pt-2 text-neutral-700">
                <span className="font-medium" style={{ color: HUA_COLOR.ji }}>沖 Clash</span>{" "}
                <span className="text-neutral-500">
                  {flying.ji.palace} (kena 忌) → {opp.name} {opp.nameZh}
                </span>
              </div>
            );
          })()}
        </div>
      )}
    </div>
  );

  // A full-screen layer on the same page keeps every piece of state alive;
  // `window.open` would not.
  if (desktop) {
    return (
      <div className="fixed inset-0 z-50 flex flex-col overflow-auto bg-white p-3">
        {body}
      </div>
    );
  }
  return body;
}
