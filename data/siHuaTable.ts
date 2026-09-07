import type { HeavenlyStem, SiHuaSet } from "../types/chart";

// ============================================================
// The Four Transformations (四化) per Heavenly Stem.
//
// This single table serves TWO purposes:
//  1. Natal Si Hua — apply to the chart's BIRTH YEAR stem once.
//  2. Flying Palace Si Hua — apply to any PALACE's own stem to see
//     where that palace's influence "flies" to on hover/tap.
//
// Values are star names (English key form — match StarPlacement.name).
// This is the widely-used standard table; some schools vary Ren's
// Hua Ke star (Zuo Fu vs Tian Fu) — noted below.
// ============================================================

export const SI_HUA_TABLE: Record<HeavenlyStem, SiHuaSet> = {
  Jia:  { lu: "Lian Zhen",  quan: "Po Jun",    ke: "Wu Qu",     ji: "Tai Yang" },
  Yi:   { lu: "Tian Ji",    quan: "Tian Liang", ke: "Zi Wei",    ji: "Tai Yin" },
  Bing: { lu: "Tian Tong",  quan: "Tian Ji",   ke: "Wen Chang", ji: "Lian Zhen" },
  Ding: { lu: "Tai Yin",    quan: "Tian Tong", ke: "Tian Ji",   ji: "Ju Men" },
  Wu:   { lu: "Tan Lang",   quan: "Tai Yin",   ke: "You Bi",    ji: "Tian Ji" },
  Ji:   { lu: "Wu Qu",      quan: "Tan Lang",  ke: "Tian Liang", ji: "Wen Qu" },
  Geng: { lu: "Tai Yang",   quan: "Wu Qu",     ke: "Tai Yin",   ji: "Tian Tong" },
  Xin:  { lu: "Ju Men",     quan: "Tai Yang",  ke: "Wen Qu",    ji: "Wen Chang" },
  Ren:  { lu: "Tian Liang", quan: "Zi Wei",    ke: "Zuo Fu",    ji: "Wu Qu" }, // Ke: Zuo Fu (some schools: Tian Fu)
  Gui:  { lu: "Po Jun",     quan: "Ju Men",    ke: "Tai Yin",   ji: "Tan Lang" },
};

/**
 * Resolve where each of a stem's four transformed stars currently sits,
 * by searching the chart's palace star lists.
 *
 * Returns null for a star that isn't placed anywhere in this chart's
 * major+minor star set (can happen for rarely-tracked minor stars).
 */
export function resolveFlyingSiHua(
  stem: HeavenlyStem,
  findPalaceOfStar: (starName: string) => string | null
) {
  const set = SI_HUA_TABLE[stem];
  return {
    lu:    { star: set.lu,    palace: findPalaceOfStar(set.lu) },
    quan:  { star: set.quan,  palace: findPalaceOfStar(set.quan) },
    ke:    { star: set.ke,    palace: findPalaceOfStar(set.ke) },
    ji:    { star: set.ji,    palace: findPalaceOfStar(set.ji) },
  };
}
