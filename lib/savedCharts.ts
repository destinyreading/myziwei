// ============================================================
// Saved birth data, kept in the visitor's own browser.
//
// Deliberately shaped like a server row rather than like a browser blob:
// every entry has a stable `id` and an `updatedAt`, and the whole store is a
// flat array of self-contained records. When destinyreading.id takes this over
// (per-user rows in SQLite), the migration is "POST each record" — no
// reshaping, and `id` can carry straight across.
//
// Everything here is wrapped in try/catch. localStorage throws outright in a
// few situations (Safari private browsing, storage disabled by policy, quota
// exceeded), and a saved-chart list is never worth breaking the calculator for.
// ============================================================

export type SavedChart = {
  /** Stable across devices and across the move to a server. */
  id: string;
  name: string;
  /** YYYY-MM-DD, exactly what the date input holds. */
  date: string;
  /** HH:MM, exactly what the time input holds. */
  time: string;
  gender: "male" | "female";
  /** ISO string; also the sort key, newest first. */
  updatedAt: string;
};

const KEY = "zwds.savedCharts.v1";

/** Version marker so an exported file can be recognised on import. */
const EXPORT_KIND = "zwds.savedCharts";

function newId(): string {
  // crypto.randomUUID is missing on older iOS Safari, so fall back rather than
  // throwing — an id only has to be unique within one person's list.
  try {
    const c = globalThis.crypto as Crypto | undefined;
    if (c && typeof c.randomUUID === "function") return c.randomUUID();
  } catch {
    // fall through
  }
  return `c${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`;
}

function isEntry(v: unknown): v is SavedChart {
  if (!v || typeof v !== "object") return false;
  const e = v as Record<string, unknown>;
  return (
    typeof e.id === "string" &&
    typeof e.name === "string" &&
    typeof e.date === "string" &&
    typeof e.time === "string" &&
    (e.gender === "male" || e.gender === "female")
  );
}

function read(): SavedChart[] {
  try {
    const raw = window.localStorage.getItem(KEY);
    if (!raw) return [];
    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed.filter(isEntry).map((e) => ({
      ...e,
      updatedAt: typeof e.updatedAt === "string" ? e.updatedAt : new Date(0).toISOString(),
    }));
  } catch {
    return [];
  }
}

function write(list: SavedChart[]): boolean {
  try {
    window.localStorage.setItem(KEY, JSON.stringify(list));
    return true;
  } catch {
    return false;
  }
}

/** Newest first. Safe to call during render on the client only. */
export function listSaved(): SavedChart[] {
  return read().sort((a, b) => (a.updatedAt < b.updatedAt ? 1 : -1));
}

/**
 * Insert or update. Two entries with the same date + time + gender are treated
 * as the same chart and overwritten, so re-saving after a typo in the name does
 * not leave a duplicate behind.
 */
export function saveChart(input: {
  name: string;
  date: string;
  time: string;
  gender: "male" | "female";
}): { ok: boolean; entry: SavedChart } {
  const list = read();
  const match = list.find(
    (e) => e.date === input.date && e.time === input.time && e.gender === input.gender
  );
  const entry: SavedChart = {
    id: match ? match.id : newId(),
    name: input.name.trim() || "(tanpa nama)",
    date: input.date,
    time: input.time,
    gender: input.gender,
    updatedAt: new Date().toISOString(),
  };
  const next = match ? list.map((e) => (e.id === entry.id ? entry : e)) : list.concat(entry);
  return { ok: write(next), entry };
}

export function removeSaved(id: string): boolean {
  return write(read().filter((e) => e.id !== id));
}

/** The whole list as a JSON file body, for the Ekspor button. */
export function exportJson(): string {
  return JSON.stringify({ kind: EXPORT_KIND, version: 1, charts: listSaved() }, null, 2);
}

/**
 * Merge an exported file back in. Existing entries win only when they are
 * newer, so importing an old backup never silently reverts recent edits.
 * Returns how many entries were added or updated.
 */
export function importJson(text: string): { added: number; updated: number; error?: string } {
  let parsed: unknown;
  try {
    parsed = JSON.parse(text);
  } catch {
    return { added: 0, updated: 0, error: "Berkas ini bukan JSON yang sah." };
  }
  const body = parsed as { kind?: unknown; charts?: unknown };
  const incoming = Array.isArray(body?.charts) ? body.charts.filter(isEntry) : null;
  if (!incoming) {
    return { added: 0, updated: 0, error: "Berkas ini bukan hasil ekspor ZWDS." };
  }

  const list = read();
  const byId = new Map(list.map((e) => [e.id, e]));
  let added = 0;
  let updated = 0;
  incoming.forEach((e) => {
    const stamp = typeof e.updatedAt === "string" ? e.updatedAt : new Date(0).toISOString();
    const existing = byId.get(e.id);
    if (!existing) {
      byId.set(e.id, { ...e, updatedAt: stamp });
      added++;
    } else if (stamp > existing.updatedAt) {
      byId.set(e.id, { ...e, updatedAt: stamp });
      updated++;
    }
  });
  // Array.from, not [...map.values()] — this project's tsconfig targets a
  // version where spreading an iterator does not compile (see the project notes).
  write(Array.from(byId.values()));
  return { added, updated };
}
