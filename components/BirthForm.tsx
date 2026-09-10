"use client";

import { useEffect, useRef, useState } from "react";
import { computeBirthInfo, type BirthInfo } from "../lib/birthInfo";
import {
  exportJson,
  importJson,
  listSaved,
  removeSaved,
  saveChart,
  type SavedChart,
} from "../lib/savedCharts";

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

  // Saved birth data. Read on mount, never during render: localStorage does not
  // exist on the server, and reading it while rendering would make the first
  // client paint disagree with the server's and trip a hydration mismatch.
  const [saved, setSaved] = useState<SavedChart[]>([]);
  const [pickedId, setPickedId] = useState("");
  const [note, setNote] = useState<string | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setSaved(listSaved());
  }, []);

  function flash(msg: string) {
    setNote(msg);
    window.setTimeout(() => setNote(null), 2200);
  }

  function handlePick(id: string) {
    setPickedId(id);
    const entry = saved.find((e) => e.id === id);
    if (!entry) return;
    setName(entry.name === "(tanpa nama)" ? "" : entry.name);
    setDate(entry.date);
    setTime(entry.time);
    setGender(entry.gender);
    setError(null);
  }

  function handleSave() {
    if (!date || !time) {
      setError("Isi tanggal dan jam dulu sebelum menyimpan.");
      return;
    }
    const { ok, entry } = saveChart({ name, date, time, gender });
    setSaved(listSaved());
    setPickedId(entry.id);
    flash(ok ? "Tersimpan." : "Browser menolak menyimpan (mode privat?).");
  }

  function handleDelete() {
    if (!pickedId) return;
    removeSaved(pickedId);
    setSaved(listSaved());
    setPickedId("");
    flash("Dihapus.");
  }

  function handleExport() {
    try {
      const blob = new Blob([exportJson()], { type: "application/json" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "zwds-data-lahir.json";
      a.click();
      window.setTimeout(() => URL.revokeObjectURL(url), 4000);
    } catch {
      flash("Gagal membuat berkas.");
    }
  }

  function handleImportFile(file: File) {
    const reader = new FileReader();
    reader.onload = () => {
      const res = importJson(String(reader.result ?? ""));
      setSaved(listSaved());
      flash(res.error ?? `Masuk: ${res.added} baru, ${res.updated} diperbarui.`);
    };
    reader.onerror = () => flash("Gagal membaca berkas.");
    reader.readAsText(file);
  }

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

  const tinyBtn =
    "rounded border border-neutral-300 bg-white px-1.5 py-0.5 text-[9px] " +
    "text-neutral-600 hover:bg-neutral-50 disabled:opacity-40 disabled:hover:bg-white";

  return (
    <form onSubmit={handleSubmit} className="w-full max-w-[15rem] space-y-1.5 text-left">
      {/* Saved data comes first: the whole point is to avoid retyping, so it
          has to be visible before the empty fields are. Hidden entirely when
          nothing is saved yet, so a first-time visitor sees the plain form. */}
      {saved.length > 0 && (
        <div>
          <label className="block text-[9px] uppercase tracking-wide text-neutral-500">
            Data tersimpan
          </label>
          <div className="flex gap-1">
            <select
              className={field + " flex-1"}
              value={pickedId}
              onChange={(e) => handlePick(e.target.value)}
            >
              <option value="">— pilih —</option>
              {saved.map((e) => (
                <option key={e.id} value={e.id}>
                  {e.name} · {e.date} {e.time}
                </option>
              ))}
            </select>
            <button
              type="button"
              onClick={handleDelete}
              disabled={!pickedId}
              title="Hapus data yang dipilih"
              className={tinyBtn}
            >
              Hapus
            </button>
          </div>
        </div>
      )}

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

      <div className="flex gap-1">
        <button
          type="submit"
          className="flex-1 rounded bg-neutral-800 px-2 py-1.5 text-[11px] font-medium text-white hover:bg-neutral-700"
        >
          Generate
        </button>
        <button
          type="button"
          onClick={handleSave}
          title="Simpan data ini di browser ini"
          className="rounded border border-neutral-300 bg-white px-2 py-1.5 text-[11px] text-neutral-700 hover:bg-neutral-50"
        >
          Simpan
        </button>
      </div>

      {/* Data lives in THIS browser only, so say so plainly and give a way out.
          Export/import is what makes it portable between a PC and an iPad. */}
      <div className="flex items-center gap-2 text-[9px] text-neutral-400">
        <button type="button" onClick={handleExport} className="underline hover:text-neutral-600">
          Ekspor
        </button>
        <button
          type="button"
          onClick={() => fileRef.current?.click()}
          className="underline hover:text-neutral-600"
        >
          Impor
        </button>
        <span className="ml-auto">tersimpan di browser ini</span>
        <input
          ref={fileRef}
          type="file"
          accept="application/json,.json"
          className="hidden"
          onChange={(e) => {
            const f = e.target.files?.[0];
            if (f) handleImportFile(f);
            e.target.value = "";
          }}
        />
      </div>

      {note && <div className="text-[10px] text-emerald-700">{note}</div>}
      {error && <div className="text-[10px] text-rose-600">{error}</div>}
    </form>
  );
}
