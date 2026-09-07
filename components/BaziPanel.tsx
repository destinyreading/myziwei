"use client";

import type { BirthInfo } from "../lib/birthInfo";
import type { BaziPillarDetail } from "../lib/bazi/calculate";

// Four-pillar (八字) display. Rendered below the grid when "Show Ba Zi" is on,
// and in a condensed form inside the center block.
//
// Column order follows Bambang's own calculator: 时 日 月 年 (hour first).

const EL_BG = ["#e8f3e8", "#fdeaea", "#f7f0e2", "#f0f2f5", "#e6eff5"]; // 木火土金水
const EL_FG = ["#2f6b3a", "#a33232", "#8a6a1f", "#4a5568", "#2b5a7a"];

function pillarsInOrder(info: BirthInfo): BaziPillarDetail[] {
  const p = info.bazi.pillars;
  return [p.hour, p.day, p.month, p.year].filter(Boolean) as BaziPillarDetail[];
}

export default function BaziPanel({ info, compact = false }: { info: BirthInfo; compact?: boolean }) {
  const pillars = pillarsInOrder(info);

  if (compact) {
    return (
      <div className="w-full">
        <div className="truncate text-[10px] font-medium text-neutral-700">{info.name}</div>
        <div className="text-[9px] text-neutral-500">{info.solarDate} · {info.solarTime}</div>
        <div className="truncate text-[9px] text-neutral-500">{info.lunarText}</div>
        <div className="mt-1.5 grid grid-cols-4 gap-0.5">
          {pillars.map((p) => (
            <div key={p.label} className="rounded border border-neutral-200 bg-white py-1">
              <div className="text-[8px] text-neutral-400">{p.label.charAt(0)}</div>
              <div className="text-[13px] leading-tight" style={{ color: EL_FG[p.elTg] }}>{p.tg}</div>
              <div className="text-[13px] leading-tight" style={{ color: EL_FG[p.elDz] }}>{p.dz}</div>
            </div>
          ))}
        </div>
        <div className="mt-1 text-[8px] text-neutral-400">
          日主 {info.bazi.dayMaster.tg} ({info.bazi.dayMaster.elName})
        </div>
      </div>
    );
  }

  return (
    <div className="mt-4 border border-neutral-200 p-4">
      <div className="mb-2 flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <span className="text-sm font-medium text-neutral-800">八字 Ba Zi — {info.name}</span>
        <span className="text-[11px] text-neutral-500">
          {info.solarDate} {info.solarTime} · {info.gender === "male" ? "Pria" : "Wanita"}
        </span>
      </div>

      <div className="mb-3 space-y-0.5 text-[11px] text-neutral-600">
        <div>
          Lunar: {info.lunarText}
          {info.isLeapMonth && <span className="ml-1 text-amber-700">(bulan kabisat)</span>}
          <span className="mx-2 text-neutral-300">|</span>
          Shio: {info.zodiac}
        </div>
        <div>
          日主 Day Master: <span className="text-neutral-900">{info.bazi.dayMaster.tg}</span>{" "}
          {info.bazi.dayMaster.elZh} {info.bazi.dayMaster.elName}
          <span className="mx-2 text-neutral-300">|</span>
          空亡 hari: {info.bazi.kongWang.day} · tahun: {info.bazi.kongWang.year}
        </div>
        <div>
          胎元 {info.bazi.taiYuan.tg}{info.bazi.taiYuan.dz}
          {info.bazi.mingGong && <> <span className="mx-1 text-neutral-300">|</span> 命宫 {info.bazi.mingGong.tg}{info.bazi.mingGong.dz}</>}
          {info.bazi.shenGong && <> <span className="mx-1 text-neutral-300">|</span> 身宫 {info.bazi.shenGong.tg}{info.bazi.shenGong.dz}</>}
        </div>
        {info.bazi.liChunNote && <div className="text-neutral-400">{info.bazi.liChunNote}</div>}
      </div>

      <div className="overflow-x-auto">
        <table className="w-full min-w-[26rem] text-left text-[11px]">
          <thead>
            <tr className="text-neutral-400">
              <th className="py-1 pr-2 font-normal"></th>
              {pillars.map((p) => (
                <th key={p.label} className="py-1 pr-2 font-normal">{p.label}</th>
              ))}
            </tr>
          </thead>
          <tbody className="align-top text-neutral-700">
            <tr>
              <td className="py-1 pr-2 text-neutral-400">十神</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  <span className="text-neutral-800">{p.ss.name}</span>
                  <div className="text-[9px] text-neutral-400">{p.ss.py}</div>
                </td>
              ))}
            </tr>
            <tr>
              <td className="py-1 pr-2 text-neutral-400">干 Stem</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  <span
                    className="inline-block rounded px-1.5 py-0.5 text-base"
                    style={{ backgroundColor: EL_BG[p.elTg], color: EL_FG[p.elTg] }}
                  >
                    {p.tg}
                  </span>
                  <div className="text-[9px] text-neutral-400">{p.elTgZh} {p.elTgName}</div>
                </td>
              ))}
            </tr>
            <tr>
              <td className="py-1 pr-2 text-neutral-400">支 Branch</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  <span
                    className="inline-block rounded px-1.5 py-0.5 text-base"
                    style={{ backgroundColor: EL_BG[p.elDz], color: EL_FG[p.elDz] }}
                  >
                    {p.dz}
                  </span>
                  <div className="text-[9px] text-neutral-400">{p.elDzZh} {p.elDzName}</div>
                </td>
              ))}
            </tr>
            <tr>
              <td className="py-1 pr-2 text-neutral-400">藏干</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  {p.hidden.map((h) => (
                    <div key={h.tgIdx} className="whitespace-nowrap">
                      <span style={{ color: EL_FG[h.el] }}>{h.tg}</span>
                      <span className="ml-1 text-[9px] text-neutral-400">{h.ss}</span>
                    </div>
                  ))}
                </td>
              ))}
            </tr>
            <tr>
              <td className="py-1 pr-2 text-neutral-400">长生</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  <span className="text-neutral-800">{p.csDm.name}</span>
                  <div className="text-[9px] text-neutral-400">{p.csDm.en}</div>
                </td>
              ))}
            </tr>
            <tr>
              <td className="py-1 pr-2 text-neutral-400">纳音</td>
              {pillars.map((p) => (
                <td key={p.label} className="py-1 pr-2">
                  <span className="text-neutral-800">{p.naYin.name}</span>
                  <div className="text-[9px] text-neutral-400">{p.naYin.id}</div>
                </td>
              ))}
            </tr>
          </tbody>
        </table>
      </div>

      <p className="mt-3 text-[10px] leading-relaxed text-neutral-400">
        Pilar dihitung oleh mesin Ba Zi Anda sendiri (port dari
        <code className="mx-1">reference/bazical/api/calculate_core.php</code>), memakai tabel Jie Qi
        1901–2100 dan konvensi 夜子时. Grid palace di atas masih memakai data contoh — belum dihitung
        dari input ini sampai <code>calculateChart()</code> selesai dibuat.
      </p>
    </div>
  );
}
