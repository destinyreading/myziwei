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
  /**
   * 早子時 dipakai? true = kelahiran 23:00–23:59 dihitung sebagai HARI
   * BERIKUTNYA (pilar hari Ba Zi dan hari lunar sama-sama maju satu).
   * false = 夜子時, harinya tetap — ini konvensi Bambang dan default kita.
   */
  earlyZi: boolean;
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

  // 早子時: kalau 23:00–23:59 dihitung hari berikutnya, yang maju bukan hanya
  // pilar hari Ba Zi (itu ditangani calculateBazi lewat `earlyZi`) tapi juga
  // HARI LUNAR — dan hari lunar itulah yang menempatkan 紫微, jadi kalau ia
  // tidak ikut maju, Ba Zi dan ZWDS di halaman yang sama akan memakai dua
  // konvensi yang berbeda. Cabang jamnya tetap 子 pada kedua aturan, jadi
  // 命宮 tidak bergeser — yang bergeser keempat belas bintang utama.
  const rollDay = !!input.earlyZi && hh >= 23;
  const dayFor = new Date(Date.UTC(y, m - 1, d));
  if (rollDay) dayFor.setUTCDate(dayFor.getUTCDate() + 1);
  const lunar = Solar.fromYmdHms(
    dayFor.getUTCFullYear(),
    dayFor.getUTCMonth() + 1,
    dayFor.getUTCDate(),
    rollDay ? 0 : hh,
    rollDay ? 30 : mm,
    0
  ).getLunar();
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
    earlyZi: !!input.earlyZi,
    // getMonthInChinese() SUDAH menyertakan awalan 闰 untuk bulan kabisat,
    // jadi menambahkannya lagi menghasilkan "闰闰九月".
    lunarText: `${lunar.getYearInChinese()}年 ${lunar.getMonthInChinese()}月${lunar.getDayInChinese()}`,
    zodiac: lunar.getYearShengXiao(),
    bazi,
  };
}
