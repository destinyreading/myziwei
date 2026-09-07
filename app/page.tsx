import ZwdsChart from "../components/ZwdsChart";
import exampleChart from "../data/exampleChart.json";
import type { ZwdsChart as ZwdsChartData } from "../types/chart";

export default function ChartPage() {
  return (
    <main className="p-4 md:p-8 max-w-3xl mx-auto">
      <ZwdsChart chart={exampleChart as ZwdsChartData} />
    </main>
  );
}
