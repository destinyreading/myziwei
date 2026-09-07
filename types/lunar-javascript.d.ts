// Minimal ambient declaration for `lunar-javascript`.
// The library is JS-first; we only declare the small surface we use so the
// rest of the project stays fully typed. Extend as needed.
declare module "lunar-javascript" {
  export const Solar: {
    fromYmdHms(
      year: number,
      month: number,
      day: number,
      hour: number,
      minute: number,
      second: number
    ): any;
    fromDate(date: Date): any;
  };
  export const Lunar: {
    fromDate(date: Date): any;
  };
}
