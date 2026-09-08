"use client";

import { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";
import { Cormorant_Garamond } from "next/font/google";
import type { EarthlyBranch, Palace, ZwdsChart as ZwdsChartData } from "../types/chart";
import { resolveFlyingSiHua } from "../data/siHuaTable";
import BirthForm from "./BirthForm";
import BaziPanel from "./BaziPanel";
import type { BirthInfo } from "../lib/birthInfo";
import { calculateChart } from "../lib/calculateChart";

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

// Self-hosted at build time by next/font — no runtime request to Google.
const brandFont = Cormorant_Garamond({ subsets: ["latin"], weight: ["500", "600"], display: "swap" });

const HUA_LABEL: Record<"lu" | "quan" | "ke" | "ji", string> = {
  lu: "化祿 Lu",
  quan: "化權 Quan",
  ke: "化科 Ke",
  ji: "化忌 Ji",
};

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

function BrightnessMark({ level }: { level: 1 | 2 | 3 | 4 | 5 | null }) {
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
      <span className="text-[9px] leading-none text-neutral-500">{level}</span>
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
  const active = hovered ?? selected;

  // Birth input + derived lunar/Ba Zi data. `info === null` means the center
  // block shows the input form; generating fills it in.
  const [info, setInfo] = useState<BirthInfo | null>(null);
  const [editing, setEditing] = useState(true);
  const [showBazi, setShowBazi] = useState(false);
  const [showMinor, setShowMinor] = useState(false);
  const [showClash, setShowClash] = useState(false);

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
  const gridRef = useRef<HTMLDivElement>(null);
  const starRefs = useRef(new Map<string, HTMLElement>());
  const [geom, setGeom] = useState<{
    w: number;
    h: number;
    stars: Record<string, { x: number; y: number; left: number; right: number }>;
  } | null>(null);

  const measure = useCallback(() => {
    const el = gridRef.current;
    if (!el) return;
    const box = el.getBoundingClientRect();
    if (!box.width || !box.height) return;
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

  // Measure only when something that moves the stars changes. Running this
  // after EVERY render is what caused an update loop: setGeom re-renders, the
  // effect fires again, forever. Size changes are covered by the observer
  // below instead.
  useLayoutEffect(() => {
    measure();
  }, [measure, chart, showMinor, showBazi, editing, info, activePalace?.branch]);

  useEffect(() => {
    const el = gridRef.current;
    if (!el || typeof ResizeObserver === "undefined") return;
    const ro = new ResizeObserver(measure);
    ro.observe(el);
    return () => ro.disconnect();
  }, [measure]);

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

  function handleSelect(branch: EarthlyBranch) {
    setSelected((prev) => (prev === branch ? null : branch));
  }

  return (
    <div className="w-full max-w-2xl mx-auto select-none">
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
            active={showClash}
            onClick={() => setShowClash((v) => !v)}
            title="沖: the palace struck by Hua Ji also afflicts its opposite palace"
          >
            Show Clash 沖
          </ToggleButton>
        </div>
      </div>

      {/* grid + svg overlay */}
      {/* Square from `sm` up. On narrow screens the pinyin sits on its own
          line, so a square grid clips the 4th/5th star out of a busy palace —
          give it a taller box instead, taller again once minor stars are on
          (a busy palace then holds 5 two-line entries). The Si Hua overlay is
          measured in pixels, so a non-square grid is fine. */}
      <div ref={gridRef} className={`relative w-full ${showMinor ? "h-[46rem]" : "h-[34rem]"} sm:h-auto sm:aspect-square border border-neutral-300`}>
        <div className="absolute inset-0 grid grid-cols-4 grid-rows-4">
          {chart.palaces.map((p) => (
            <button
              key={p.branch}
              type="button"
              onClick={() => handleSelect(p.branch)}
              onMouseEnter={() => setHovered(p.branch)}
              onMouseLeave={() => setHovered(null)}
              style={{ gridRow: p.grid.row + 1, gridColumn: p.grid.col + 1 }}
              className={[
                "flex flex-col items-start justify-start p-1 text-left border border-neutral-200",
                "min-h-11 overflow-hidden transition-colors",
                active === p.branch ? "bg-amber-50" : "bg-white hover:bg-neutral-50",
                p.isBodyPalace ? "ring-1 ring-inset ring-neutral-400" : "",
              ].join(" ")}
            >
              <div className="flex items-baseline gap-1 w-full">
                <span className="text-sm font-medium text-neutral-800">{p.nameZh.charAt(0)}</span>
                <span className="text-[10px] text-neutral-400 truncate">{p.name}</span>
              </div>
              <div className="text-[9px] text-neutral-400">
                {p.branch} · {p.ageRange[0]}-{p.ageRange[1]}
              </div>
              <div className="mt-1 space-y-0.5 w-full">
                {/* One star = one line. The pinyin is the only part allowed to
                    shrink/truncate; brightness and the Si Hua tag must never
                    wrap onto their own line, or a narrow palace turns into a
                    ladder and pushes later stars out of the box. */}
                {p.stars.filter((s) => s.isMajor || showMinor).map((s) => {
                  // Highlighted when the ACTIVE palace sends a transformation
                  // to this star: filled with that Si Hua's colour, white text.
                  const hl = huaOfStar.get(s.name);
                  const hlStyle = hl
                    ? { backgroundColor: HUA_COLOR[hl], color: "#fff" }
                    : undefined;
                  const hlClass = hl ? "rounded-sm px-[3px]" : "";
                  return (
                  <div
                    key={s.name}
                    className={
                      // Narrow screens stack the pinyin under the Chinese name;
                      // from `sm` up there is room for one line per star.
                      "flex flex-col sm:flex-row sm:items-baseline sm:gap-1 " +
                      (s.isMajor
                        ? "text-[10px] leading-tight text-neutral-700"
                        : "text-[9px] leading-tight text-neutral-400")
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
                          ? s.isMajor ? "text-[9px]" : "text-[8px]"
                          : s.isMajor ? "text-[9px] text-neutral-500" : "text-[8px] text-neutral-400")
                      }
                      style={hlStyle}
                    >
                      {s.name}
                    </span>
                    <span className="hidden sm:inline-flex shrink-0">
                      <BrightnessMark level={s.brightness} />
                    </span>
                    <span className="hidden sm:flex ml-auto shrink-0 items-baseline whitespace-nowrap">
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
                  </div>
                  );
                })}
              </div>
            </button>
          ))}

          {/* center info panel spans the 2x2 middle block */}
          <div
            style={{ gridRow: "2 / span 2", gridColumn: "2 / span 2" }}
            className="relative flex flex-col items-center justify-center overflow-y-auto text-center p-2 pb-5 bg-neutral-50 border border-neutral-200"
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
                  }}
                />
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
                  <div className="text-xs font-medium text-neutral-700 truncate max-w-full">{info.name}</div>
                  <div className="text-[10px] text-neutral-500 mt-1">{info.solarDate} · {info.solarTime}</div>
                  <div className="text-[10px] text-neutral-500">{info.lunarText}</div>
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

            {/* Bottom row of the centre block: it travels with the chart, so
                it stays visible in any screenshot of the grid. */}
            <div className="absolute inset-x-2 bottom-1 flex items-end justify-between gap-2">
              <a
                href="https://www.destinyreading.id"
                target="_blank"
                rel="noopener noreferrer"
                className={`${brandFont.className} text-[11px] leading-none tracking-[0.12em] text-neutral-500 transition-colors hover:text-amber-700`}
              >
                © www.destinyreading.id
              </a>
              <button
                type="button"
                disabled
                title="Transit chart (大限 / 流年) — belum dibuat"
                className="text-[10px] leading-none text-neutral-300 cursor-not-allowed"
              >
                Transit Chart →
              </button>
            </div>
          </div>
        </div>

        {/* SVG overlay for flying Si Hua lines. Drawn in PIXEL space (viewBox
            matches the measured box) so each line can end on its own star row.
            Falls back to the palace centre when the target star is not
            rendered — e.g. a minor star while "Show Minor Star" is off. */}
        {activePalace && flying && geom && (
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

            {/* 沖 clash: the palace struck by Hua Ji also afflicts the palace
                opposite it (branch + 6). Drawn as a dashed red line from the
                afflicted star to the centre of that opposite palace. */}
            {showClash && (() => {
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

            {(["lu", "quan", "ke", "ji"] as const).map((k) => {
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

      {/* Ba Zi (four pillars) — toggled by "Show Ba Zi" */}
      {showBazi && info && <BaziPanel info={info} />}

      {/* detail panel — flows normally below the grid on every screen size,
          so it works identically on mobile without fixed positioning. */}
      {activePalace && flying && (
        <div className="mt-4 border border-neutral-200 p-4 text-sm">
          <div className="font-medium text-neutral-800 mb-2">
            {activePalace.nameZh} {activePalace.name} — flying Si Hua ({activePalace.stem})
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
}
