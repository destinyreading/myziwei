"use client";

import { useEffect, useState } from "react";
import { computeBirthInfo, type BirthInfo } from "../lib/birthInfo";

/** Local "now" as the two strings the date/time inputs expect. */
function nowFields() {
  const d = new Date();
  const p = (n: number) => String(n).padStart(2, "0");
  return {
    date: `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`,
    time: `${p(d.getHours())}:${p(d.getMinutes())}`,
  };
}

// Compact input form that lives inside the 2x2 center block of the chart grid.
// Space is tight, so labels are inline-small and everything is a single column.
export default function BirthForm({
  defaults,
  onGenerate,
}: {
  defaults?: { name?: string; date?: string; time?: string; gender?: "male" | "female" };
  onGenerate: (info: BirthInfo) => void;
}) {
  const [name, setName] = useState(defaults?.name ?? "");
  const [date, setDate] = useState(defaults?.date ?? "");
  const [time, setTime] = useState(defaults?.time ?? "");
  const [gender, setGender] = useState<"male" | "female">(defaults?.gender ?? "male");
  const [error, setError] = useState<string | null>(null);

  // With no birth data supplied, start from the moment the page is opened.
  // Done in an effect rather than in useState: `new Date()` during the server
  // render would not match the client's clock and React would flag a
  // hydration mismatch.
  useEffect(() => {
    if (defaults?.date || defaults?.time) return;
    const now = nowFields();
    setDate((d) => d || now.date);
    setTime((t) => t || now.time);
    // Only on mount: re-running would overwrite what the user is typing.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    try {
      onGenerate(computeBirthInfo({ name, date, time, gender }));
    } catch (err) {
      setError(err instanceof Error ? err.message : "Gagal menghitung.");
    }
  }

  const field =
    "w-full border border-neutral-300 rounded px-1.5 py-1 text-[11px] bg-white " +
    "focus:outline-none focus:ring-1 focus:ring-amber-500";

  return (
    <form onSubmit={handleSubmit} className="w-full max-w-[15rem] space-y-1.5 text-left">
      <div>
        <label className="block text-[9px] uppercase tracking-wide text-neutral-500">Nama</label>
        <input
          className={field}
          value={name}
          onChange={(e) => setName(e.target.value)}
          placeholder="Nama"
        />
      </div>

      <div className="flex gap-1.5">
        <div className="flex-1">
          <label className="block text-[9px] uppercase tracking-wide text-neutral-500">Tgl lahir</label>
          <input type="date" className={field} value={date} onChange={(e) => setDate(e.target.value)} required />
        </div>
        <div className="w-[5.5rem]">
          <label className="block text-[9px] uppercase tracking-wide text-neutral-500">Jam</label>
          <input type="time" className={field} value={time} onChange={(e) => setTime(e.target.value)} required />
        </div>
      </div>

      <div>
        <label className="block text-[9px] uppercase tracking-wide text-neutral-500">Gender</label>
        <div className="flex gap-1">
          {(["male", "female"] as const).map((g) => (
            <button
              key={g}
              type="button"
              onClick={() => setGender(g)}
              className={[
                "flex-1 rounded border px-1 py-1 text-[10px] capitalize transition-colors",
                gender === g
                  ? "border-amber-500 bg-amber-50 text-amber-800"
                  : "border-neutral-300 bg-white text-neutral-600 hover:bg-neutral-50",
              ].join(" ")}
            >
              {g === "male" ? "Pria" : "Wanita"}
            </button>
          ))}
        </div>
      </div>

      <button
        type="submit"
        className="w-full rounded bg-neutral-800 px-2 py-1.5 text-[11px] font-medium text-white hover:bg-neutral-700"
      >
        Generate
      </button>

      {error && <div className="text-[10px] text-rose-600">{error}</div>}
    </form>
  );
}
