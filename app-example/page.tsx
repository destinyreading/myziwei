import ZwdsChart from "../components/ZwdsChart";
import exampleChart from "../data/exampleChart.json";
import type { ZwdsChart as ZwdsChartData } from "../types/chart";

// Next.js App Router page — drop this file at app/chart/page.tsx in your project.
// The JSON import is cast to ZwdsChartData; once you build a real calculation
// engine, replace this with the output of that engine instead of the fixture.

export default function ChartPage() {
  return (
    <main className="p-4 md:p-8">
      <ZwdsChart chart={exampleChart as ZwdsChartData} />
    </main>
  );
}
