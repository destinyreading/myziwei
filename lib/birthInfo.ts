import { Solar } from "lunar-javascript";
import { calculateBazi, type BaziResult } from "./bazi/calculate";

// ============================================================
// Solar birth data -> lunar date + Ba Zi (four pillars).
//
// Two engines, on purpose:
//  - Ba Zi comes from lib/bazi/*, a TypeScript port of Bambang's own PHP
//    calculator (verified output). It is the authority for the pillars.
//  - The LUNAR CALENDAR (month, day, leap-month flag) comes from
//    `lunar-javascript`, because the PHP calculator is pure Ba Zi and has no
//    lunar conversion. ZWDS palace placement and Wu Xing Ju need exactly
//    those lunar numbers.
//
// Keep this module as the single entry point so calculateChart() later
// consumes the same output.
// ============================================================

export interface BirthInput {
  name: string;
  /** "YYYY-MM-DD" (solar / Gregorian) */
  date: string;
  /** "HH:MM", 24h */
  time: string;
  gender: "male" | "female";
  /** 早子时: treat 23:00-23:59 as the next day. Default false (夜子时). */
  earlyZi?: boolean;
}

export interface BirthInfo {
  name: string;
  gender: "male" | "female";
  solarDate: string;      // "1975-07-17"
  solarTime: string;      // "06:23"
  lunarYear: number;
  lunarMonth: number;     // 1-12 (absolute value; see isLeapMonth)
  lunarDay: number;
  isLeapMonth: boolean;
  lunarText: string;      // "一九七五年 六月初九"
  zodiac: string;         // 生肖
  bazi: BaziResult;
}

export function computeBirthInfo(input: BirthInput): BirthInfo {
  const [y, m, d] = input.date.split("-").map(Number);
  const [hh, mm] = input.time.split(":").map(Number);

  if (!y || !m || !d || Number.isNaN(hh) || Number.isNaN(mm)) {
    throw new Error("Tanggal atau jam lahir tidak valid.");
  }

  const bazi = calculateBazi({
    year: y, month: m, day: d, hour: hh, minute: mm, earlyZi: input.earlyZi,
  });

  const lunar = Solar.fromYmdHms(y, m, d, hh, mm, 0).getLunar();
  const rawMonth: number = lunar.getMonth(); // negative => leap month

  return {
    // No name given — the chart is the one generated for the moment the page
    // was opened, so label it as that rather than "(tanpa nama)".
    name: input.name.trim() || "Waktu saat ini",
    gender: input.gender,
    solarDate: input.date,
    solarTime: input.time,
    lunarYear: lunar.getYear(),
    lunarMonth: Math.abs(rawMonth),
    lunarDay: lunar.getDay(),
    isLeapMonth: rawMonth < 0,
    // getMonthInChinese() SUDAH menyertakan awalan 闰 untuk bulan kabisat,
    // jadi menambahkannya lagi menghasilkan "闰闰九月".
    lunarText: `${lunar.getYearInChinese()}年 ${lunar.getMonthInChinese()}月${lunar.getDayInChinese()}`,
    zodiac: lunar.getYearShengXiao(),
    bazi,
  };
}
