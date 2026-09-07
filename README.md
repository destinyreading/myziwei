# Zi Wei Dou Shu chart — starter kit

A minimal, working slice of the calculator you're planning: real chart data,
typed end to end, rendered as the classic 4x4 palace grid with hover/tap
"flying Si Hua" lines. Built to be dropped into a Next.js + Tailwind project.

## Files

```
types/chart.ts          Data model — Palace, StarPlacement, ZwdsChart, SiHuaSet
data/siHuaTable.ts       The 10-stem x 4-transformation lookup table + resolver
data/exampleChart.json   A real, hand-verified chart (Bambang Santosa, 17 Jul 1975)
components/ZwdsChart.tsx The component: grid + SVG overlay + detail panel
app-example/page.tsx     How to wire it into a Next.js App Router route
```

## Try it

1. Copy `types/`, `data/`, `components/` into a Next.js + TypeScript + Tailwind
   project (`npx create-next-app@latest --typescript --tailwind`).
2. Copy `app-example/page.tsx` to `app/chart/page.tsx`.
3. `npm run dev`, visit `/chart`.
4. Click any palace. Colored lines show where that palace's own stem sends
   its four transformations (祿/權/科/忌). A dashed ring means the
   transformation landed back on its own palace (自化, self-transformation) —
   watch Life palace's 化科 and Wealth palace's 化忌 in the sample chart,
   both self-transform, matching the reading from our conversation.

## What's real vs. what's a stand-in

**Real and reusable as-is:**
- The type model (`types/chart.ts`)
- The Si Hua transformation table and flying-star resolver (`data/siHuaTable.ts`)
- The example chart data (double-checked against zwds-calculator.com)
- The rendering/interaction logic (grid placement, SVG line math, hover vs.
  tap handling, self-transformation detection)

**Stand-in, needs building:**
- There's no lunar calendar converter or palace/star placement *calculator*
  here — `exampleChart.json` is a fixture, not computed output. That's the
  next piece to build: a function `calculateChart(birthDate, birthTime,
  gender) -> ZwdsChart` that produces this same JSON shape. This is the
  riskiest part (leap months, Jieqi timing) — reach for a battle-tested lunar
  conversion library rather than reimplementing the astronomy.
- Only natal Si Hua is in the fixture. Da Xian (decade) and Liu Nian (annual)
  Si Hua use the identical `resolveFlyingSiHua()` function — just pass that
  period's stem instead of a palace's stem.
- Minor-star placement rules (Wen Chang/Wen Qu by hour, Zuo Fu/You Bi by
  month, etc.) aren't implemented as calculations — they're just data in the
  fixture. Each follows a fixed offset rule from a reference branch, similar
  in shape to the Si Hua table.

## Mobile behavior

The detail panel flows in normal document order below the grid rather than
using a fixed-position bottom sheet — this sidesteps iOS Safari's fixed
positioning/viewport-resize quirks and needs no extra dependency. Touch
targets on each palace cell are ≥44px (`min-h-11`). If you want the slide-up
sheet feel later, wrap the same detail content in a drawer component (e.g.
`vaul`) without changing any of the calculation logic above it.
