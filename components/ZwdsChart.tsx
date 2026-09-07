"use client";

import { useMemo, useState } from "react";
import type { EarthlyBranch, Palace, ZwdsChart as ZwdsChartData } from "../types/chart";
import { resolveFlyingSiHua } from "../data/siHuaTable";
import BirthForm from "./BirthForm";
import BaziPanel from "./BaziPanel";
import type { BirthInfo } from "../lib/birthInfo";

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

const HUA_LABEL: Record<"lu" | "quan" | "ke" | "ji", string> = {
  lu: "化祿 Lu",
  quan: "化權 Quan",
  ke: "化科 Ke",
  ji: "化忌 Ji",
};

function centerOf(row: number, col: number) {
  return { x: ((col + 0.5) / 4) * 100, y: ((row + 0.5) / 4) * 100 };
}

// Renders a 1-5 dot brightness scale. ZWDS convention: 1 = brightest.
function BrightnessDots({ level }: { level: 1 | 2 | 3 | 4 | 5 | null }) {
  if (level === null) return null;
  const filled = 6 - level; // brightness 1 -> 5 dots filled, 5 -> 1 dot filled
  return (
    <span className="inline-flex gap-[1px] align-middle ml-1" aria-label={`brightness ${level}`}>
      {Array.from({ length: 5 }, (_, i) => (
        <span
          key={i}
          className="inline-block w-[3px] h-[3px] rounded-full"
          style={{ backgroundColor: i < filled ? "#b8935a" : "#d8d0c0" }}
        />
      ))}
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

export default function ZwdsChart({ chart }: { chart: ZwdsChartData }) {
  const [hovered, setHovered] = useState<EarthlyBranch | null>(null);
  const [selected, setSelected] = useState<EarthlyBranch | null>(null);
  const active = hovered ?? selected;

  // Birth input + derived lunar/Ba Zi data. `info === null` means the center
  // block shows the input form; generating fills it in.
  const [info, setInfo] = useState<BirthInfo | null>(null);
  const [editing, setEditing] = useState(true);
  const [showBazi, setShowBazi] = useState(false);
  const [showMinor, setShowMinor] = useState(false);

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

  // name -> branch, needed to draw lines (we key palaces by branch on the grid)
  const branchByName = useMemo(() => {
    const map = new Map<string, EarthlyBranch>();
    chart.palaces.forEach((p) => map.set(p.name, p.branch));
    return map;
  }, [chart]);

  const activePalace = active ? palaceByBranch.get(active) ?? null : null;

  const flying = useMemo(() => {
    if (!activePalace) return null;
    return resolveFlyingSiHua(activePalace.stem, (starName) => {
      const palaceName = starIndex.get(starName);
      return palaceName ?? null;
    });
  }, [activePalace, starIndex]);

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
        </div>
      </div>

      {/* grid + svg overlay */}
      <div className="relative w-full aspect-square border border-neutral-300">
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
                {p.stars.filter((s) => s.isMajor || showMinor).map((s) => (
                  <div
                    key={s.name}
                    className={
                      s.isMajor
                        ? "text-[10px] leading-tight text-neutral-700 truncate"
                        : "text-[9px] leading-tight text-neutral-400 truncate"
                    }
                  >
                    {s.nameZh}
                    <BrightnessDots level={s.brightness} />
                    {s.natalSiHua && (
                      <span
                        className="ml-1 text-[9px] font-medium"
                        style={{ color: HUA_COLOR[s.natalSiHua.toLowerCase() as "lu" | "quan" | "ke" | "ji"] }}
                      >
                        {s.natalSiHua}
                      </span>
                    )}
                  </div>
                ))}
              </div>
            </button>
          ))}

          {/* center info panel spans the 2x2 middle block */}
          <div
            style={{ gridRow: "2 / span 2", gridColumn: "2 / span 2" }}
            className="flex flex-col items-center justify-center overflow-y-auto text-center p-2 bg-neutral-50 border border-neutral-200"
          >
            {!activePalace ? (
              !info || editing ? (
                <BirthForm
                  defaults={
                    info
                      ? { name: info.name, date: info.solarDate, time: info.solarTime, gender: info.gender }
                      : { name: chart.meta.name, date: chart.meta.solarDate, time: chart.meta.solarTime, gender: chart.meta.gender }
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
              )
            ) : (
              <>
                <div className="text-sm font-medium text-neutral-800">{activePalace.nameZh} {activePalace.name}</div>
                <div className="text-[10px] text-neutral-500">stem {activePalace.stem}</div>
              </>
            )}
          </div>
        </div>

        {/* SVG overlay for flying Si Hua lines — coordinate math matches the
            4x4 grid exactly because the grid has no gaps (border-only). */}
        {activePalace && flying && (
          <svg
            viewBox="0 0 100 100"
            preserveAspectRatio="none"
            className="absolute inset-0 w-full h-full pointer-events-none"
          >
            {(["lu", "quan", "ke", "ji"] as const).map((k) => {
              const target = flying[k];
              if (!target.palace) return null; // star not placed in this chart
              const targetBranch = branchByName.get(target.palace);
              if (!targetBranch) return null;
              const targetPalace = palaceByBranch.get(targetBranch)!;
              const from = centerOf(activePalace.grid.row, activePalace.grid.col);

              if (targetBranch === activePalace.branch) {
                // self-transformation (自化) — dashed ring around the source cell
                return (
                  <circle
                    key={k}
                    cx={from.x}
                    cy={from.y}
                    r={9}
                    fill="none"
                    stroke={HUA_COLOR[k]}
                    strokeWidth={0.6}
                    strokeDasharray="2 1.5"
                    vectorEffect="non-scaling-stroke"
                  />
                );
              }

              const to = centerOf(targetPalace.grid.row, targetPalace.grid.col);
              return (
                <line
                  key={k}
                  x1={from.x} y1={from.y}
                  x2={to.x} y2={to.y}
                  stroke={HUA_COLOR[k]}
                  strokeWidth={0.6}
                  vectorEffect="non-scaling-stroke"
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
                    <span>{target.star}</span>
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
        </div>
      )}
    </div>
  );
}
