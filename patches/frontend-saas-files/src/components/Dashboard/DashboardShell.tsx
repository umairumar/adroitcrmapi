import { Spinner } from "flowbite-react";
import InfoAdmin from "../Admin_info_block/infoAdmin";
import SalesRevenueChart from "../Admin_info_block/SalesRevenueChart";
import { useDashboard } from "../../hooks/useDashboard";

type Props = {
  showCharts?: boolean;
};

export default function DashboardShell({ showCharts = true }: Props) {
  const { data, loading, error, reload } = useDashboard();

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <Spinner size="xl" />
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="rounded-lg border border-red-200 bg-red-50 p-6 text-red-800">
        <p>{error || "Dashboard unavailable"}</p>
        <button
          type="button"
          className="mt-3 text-sm underline"
          onClick={() => reload()}
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <>
      <InfoAdmin folders={data.folders} payments={data.payments} />
      {showCharts && (
        <SalesRevenueChart
          leads={data.leads}
          trend={data.trend}
          agents={data.agents}
          payments={data.payments}
        />
      )}
    </>
  );
}
