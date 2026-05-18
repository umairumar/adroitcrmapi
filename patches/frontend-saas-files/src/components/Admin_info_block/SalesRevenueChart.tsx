import {
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Area,
  AreaChart,
  BarChart,
  Bar,
} from "recharts";

import upcomingFolder from "../../assets/images/updoming_f.png";
import Loss from "../../assets/images/loss_icon.svg";
import Profit from "../../assets/images/profit_icon.svg";
import dropdownIcon from "../../assets/images/dropdownIcon.svg";
import Lead_icon_1 from "../../assets/images/icon_1.svg";
import Lead_icon_2 from "../../assets/images/icon_2.svg";
import Lead_icon_3 from "../../assets/images/icon_3.svg";
import Lead_icon_4 from "../../assets/images/icon_4.svg";

import type { TooltipProps } from "recharts";
import type {
  DashboardAgentRow,
  DashboardLeadStats,
  DashboardPaymentStats,
  DashboardTrendPoint,
} from "../../types/dashboard";

interface CustomTooltipProps extends TooltipProps<number, string> {
  active?: boolean;
  payload?: { value: number }[];
  label?: string;
}

const CustomTooltip: React.FC<CustomTooltipProps> = ({
  active,
  payload,
  label,
}) => {
  if (active && payload && payload.length) {
    return (
      <div className="bg-white shadow-lg rounded-lg p-3 border border-gray-100">
        <p className="text-gray-800 font-semibold">{label}</p>
        <p className="text-gray-500 text-sm">
          Bookings:{" "}
          <span className="text-blue-600 font-medium">{payload[0].value}</span>
        </p>
      </div>
    );
  }
  return null;
};

type ChartProps = {
  leads?: DashboardLeadStats;
  trend?: DashboardTrendPoint[];
  agents?: DashboardAgentRow[];
  payments?: DashboardPaymentStats;
};

export default function SalesRevenueChart({
  leads,
  trend = [],
  agents = [],
  payments,
}: ChartProps) {
  const chartTrend = trend.length
    ? trend
    : [{ month: "", label: "No data", count: 0 }];
  const data = chartTrend.map((t) => ({
    month: t.label,
    totalSales: t.count,
    revenue: payments?.approved_amount
      ? Math.round(payments.approved_amount / Math.max(chartTrend.length, 1))
      : t.count,
  }));

  const BookingData = agents.map((a) => ({
    name: a.name,
    bookings: a.booked,
  }));

  const totalLeads = leads?.total || 1;
  const Progress1 = Math.min(100, Math.round(((leads?.by_status.new ?? 0) / totalLeads) * 100));
  const Progress2 = Math.min(100, Math.round(((leads?.by_status.open ?? 0) / totalLeads) * 100));
  const Progress3 = Math.min(100, Math.round(((leads?.by_status.booked ?? 0) / totalLeads) * 100));
  const Progress4 = Math.min(100, Math.round(((leads?.by_status.archive ?? 0) / totalLeads) * 100));
  return (
    <div className="">
      <div className="grid grid-cols-12 gap-5">
        <div className="bg-white p-6 rounded-2xl shadow-sm col-span-8">
          <div className="flex justify-between items-center mb-4">
            <div>
              <h2 className="justify-start text-stone-700 text-xl font-medium font-['Poppins']">
                Sales & Revenue Overview
              </h2>{" "}
              <p className="text-sm text-gray-400">January - October 2022</p>
            </div>
            <div className="flex gap-5 items-center justify-center">
              <div className="flex flex-col justify-center items-center">
                <div className="flex items-center gap-4 text-sm">
                  <span className="w-2.5 h-2.5 bg-black rounded-full"></span>
                  <span>Total Sales</span>
                  <img src={Profit} alt="Profit" />
                  <span className="justify-start text-green-800 text-xs font-semibold font-['Poppins']">
                    +11.2%
                  </span>
                </div>
                <div className="flex items-center gap-4 text-sm">
                  <span className="w-2.5 h-2.5 bg-blue-500 rounded-full"></span>
                  <span>Revenue</span>
                  <img src={Loss} alt="Loss" />
                  <span className="justify-start text-red-700 text-xs font-normal font-['Inter'] leading-none">
                    8.2%
                  </span>
                </div>
              </div>
              <div className="relative inline-block w-52">
                <select
                  className="w-full px-4 py-2 pr-8 bg-zinc-100 text-stone-700 text-sm font-medium rounded-lg shadow-sm border border-zinc-200 
               focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer 
               appearance-none bg-[url('data:image/svg+xml;base64,')] bg-no-repeat bg-right hidden-arrow"
                  defaultValue="Jan-Jun 2022"
                  style={{
                    appearance: "none",
                    WebkitAppearance: "none",
                    MozAppearance: "none",
                    backgroundImage: "none",
                  }}
                >
                  <option value="Jan-Jun 2022">January - June 2022</option>
                  <option value="Jul-Dec 2022">July - December 2022</option>
                  <option value="Jan-Jun 2023">January - June 2023</option>
                  <option value="Jul-Dec 2023">July - December 2023</option>
                </select>

                {/* Custom dropdown icon */}
                <div className="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                  <img
                    src={dropdownIcon}
                    alt="dropdown icon"
                    className="w-4 h-4 opacity-70"
                  />
                </div>
              </div>
            </div>
          </div>

          <ResponsiveContainer width="100%" height={300}>
            <AreaChart data={data}>
              <defs>
                <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.4} />
                  <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                </linearGradient>
              </defs>

              <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
              <XAxis dataKey="month" tick={{ fill: "#9ca3af" }} />
              <YAxis tick={{ fill: "#9ca3af" }} />
              <Tooltip
                contentStyle={{
                  backgroundColor: "white",
                  borderRadius: "8px",
                  border: "1px solid #e5e7eb",
                }}
                formatter={(value) => [`$${value.toLocaleString()}`, ""]}
              />

              <Area
                type="monotone"
                dataKey="revenue"
                stroke="#3b82f6"
                fill="url(#colorRevenue)"
                strokeWidth={3}
              />
              <Line
                type="monotone"
                dataKey="totalSales"
                stroke="#000"
                strokeWidth={2}
                dot={{ r: 4, fill: "white", stroke: "#000", strokeWidth: 2 }}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        <div className="col-span-4 h-full px-4 py-4 bg-white rounded-xl outline outline-1 outline-offset-[-1px] outline-neutral-200 p-4">
          <img src={upcomingFolder} className="w-full" alt="upcoming Folder" />
          <h2 className="justify-start text-stone-700 text-xl font-medium font-['Poppins'] mt-3">
            Upcoming travel Folders
          </h2>
          <p className="justify-start text-sky-500 text-xl font-medium font-['Poppins'] underline mt-3">
            30 Days
          </p>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-5 mt-5">
        <div className="p-6 bg-white rounded-xl outline outline-1 outline-offset-[-1px] outline-neutral-200">
          <h2 className="justify-start text-stone-700 text-xl font-medium font-['Poppins']">
            Leads Overview
          </h2>
          <div className="grid grid-cols-2 gap-4 mt-4">
            <div className="bg-white rounded-[10px] outline outline-1 outline-offset-[-1px] outline-neutral-200 shadow-               [0px_10px_32px_0px_rgba(0,0,0,0.08)] p-4">
              <div className="w-10 h-10 px-0.5 py-[3px] bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                <img
                  src={Lead_icon_1}
                  className="h-auto max-x-full"
                  alt="Lead icon 1"
                />
              </div>
              <h2 className="self-stretch justify-start text-Normal-Color text-base font-normal font-['Poppins'] mt-4">
                New Leads
              </h2>
              <div className="flex items-center justify-start gap-3 mt-3">
                <p className="justify-start text-black text-2xl font-semibold font-['Poppins'] leading-loose">
                  {(leads?.by_status.new ?? 0).toLocaleString()}
                </p>
                <div className="flex items-center justify-center gap-2 px-3 py-2 bg-green-100 rounded-lg w-fit">
                  <img src={Profit} alt="Profit" className="w-4 h-4" />
                  <span className="text-green-800 text-xs font-semibold font-['Poppins']">
                    +11.2%
                  </span>
                </div>
              </div>
              <div className="mt-5">
                <div className="flex justify-between items-center mb-1">
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    Progress
                  </p>
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    {Progress1}%
                  </p>
                </div>

                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                  <div
                    className="bg-blue-600 h-2.5 rounded-full"
                    style={{ width: `${Progress1}%` }}
                  ></div>
                </div>
              </div>

              <p className="justify-start text-Input-field-Text-Color text-[10px] font-normal font-['Inter'] leading-none mt-3">
                This Month
              </p>
            </div>
            <div className="bg-white rounded-[10px] outline outline-1 outline-offset-[-1px] outline-neutral-200 shadow-               [0px_10px_32px_0px_rgba(0,0,0,0.08)] p-4">
              <div className="w-10 h-10 px-0.5 py-[3px] bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                <img
                  src={Lead_icon_2}
                  className="h-auto max-x-full"
                  alt="Lead icon 1"
                />
              </div>
              <h2 className="self-stretch justify-start text-Normal-Color text-base font-normal font-['Poppins'] mt-4">
                Open Leads
              </h2>
              <div className="flex items-center justify-start gap-3 mt-3">
                <p className="justify-start text-black text-2xl font-semibold font-['Poppins'] leading-loose">
                  {(leads?.by_status.open ?? 0).toLocaleString()}
                </p>
                <div className="flex items-center justify-center gap-2 px-3 py-2 bg-green-100 rounded-lg w-fit">
                  <img src={Profit} alt="Profit" className="w-4 h-4" />
                  <span className="text-green-800 text-xs font-semibold font-['Poppins']">
                    +8.2%
                  </span>
                </div>
              </div>
              <div className="mt-5">
                <div className="flex justify-between items-center mb-1">
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    Progress
                  </p>
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    {Progress2}%
                  </p>
                </div>

                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                  <div
                    className="bg-blue-600 h-2.5 rounded-full"
                    style={{ width: `${Progress2}%` }}
                  ></div>
                </div>
              </div>

              <p className="justify-start text-Input-field-Text-Color text-[10px] font-normal font-['Inter'] leading-none mt-3">
                Active prospects
              </p>
            </div>
            <div className="bg-white rounded-[10px] outline outline-1 outline-offset-[-1px] outline-neutral-200 shadow-               [0px_10px_32px_0px_rgba(0,0,0,0.08)] p-4">
              <div className="w-10 h-10 px-0.5 py-[3px] bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                <img
                  src={Lead_icon_3}
                  className="h-auto max-x-full"
                  alt="Lead icon 1"
                />
              </div>
              <h2 className="self-stretch justify-start text-Normal-Color text-base font-normal font-['Poppins'] mt-4">
                Booked Leads
              </h2>
              <div className="flex items-center justify-start gap-3 mt-3">
                <p className="justify-start text-black text-2xl font-semibold font-['Poppins'] leading-loose">
                  {(leads?.by_status.booked ?? 0).toLocaleString()}
                </p>
              </div>
              <div className="mt-5">
                <div className="flex justify-between items-center mb-1">
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    Share
                  </p>
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    {Progress3}%
                  </p>
                </div>

                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                  <div
                    className="bg-blue-600 h-2.5 rounded-full"
                    style={{ width: `${Progress3}%` }}
                  ></div>
                </div>
              </div>

              <p className="justify-start text-Input-field-Text-Color text-[10px] font-normal font-['Inter'] leading-none mt-3">
                Converted
              </p>
            </div>
            <div className="bg-white rounded-[10px] outline outline-1 outline-offset-[-1px] outline-neutral-200 shadow-               [0px_10px_32px_0px_rgba(0,0,0,0.08)] p-4">
              <div className="w-10 h-10 px-0.5 py-[3px] bg-blue-100 rounded-lg flex flex-col items-center justify-center">
                <img
                  src={Lead_icon_4}
                  className="h-auto max-x-full"
                  alt="Lead icon 1"
                />
              </div>
              <h2 className="self-stretch justify-start text-Normal-Color text-base font-normal font-['Poppins'] mt-4">
                Archive Leads
              </h2>
              <div className="flex items-center justify-start gap-3 mt-3">
                <p className="justify-start text-black text-2xl font-semibold font-['Poppins'] leading-loose">
                  {(leads?.by_status.archive ?? 0).toLocaleString()}
                </p>
                <div className="flex items-center justify-center gap-2 px-3 py-2 bg-red-100 rounded-lg w-fit">
                  <img src={Loss} alt="Loss" className="w-4 h-4" />
                  <span className="text-red-700 text-xs font-semibold font-['Poppins']">
                    +8.2%
                  </span>
                </div>
              </div>
              <div className="mt-5">
                <div className="flex justify-between items-center mb-1">
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    Progress
                  </p>
                  <p className="text-Normal-Color text-[10px] font-normal font-['Inter'] leading-none">
                    {Progress4}%
                  </p>
                </div>

                <div className="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                  <div
                    className="bg-blue-600 h-2.5 rounded-full"
                    style={{ width: `${Progress4}%` }}
                  ></div>
                </div>
              </div>

              <p className="justify-start text-Input-field-Text-Color text-[10px] font-normal font-['Inter'] leading-none mt-3">
                Dormant
              </p>
            </div>
          </div>
        </div>
        <div className="p-6 bg-white rounded-xl outline outline-1 outline-offset-[-1px] outline-neutral-200">
          <div className="flex justify-between items-center">
            <h2 className="justify-start text-stone-700 text-xl font-medium font-['Poppins']">
              Agent Performance
            </h2>

            <div
              id="dropdownDefaultButton"
              data-dropdown-toggle="dropdown"
              className="flex items-center justify-center space-x-3 cursor-pointer"
            >
              <div>
                <div className="flex items-center justify-center">
                  <h2 className="ustify-start text-zinc-800/75 text-sm font-normal font-['Roboto']">
                    By Margin
                  </h2>
                  <svg
                    className="w-2.5 h-2.5 ms-3"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 10 6"
                  >
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="m1 1 4 4 4-4"
                    />
                  </svg>
                </div>
                <div
                  id="dropdown"
                  className="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700"
                >
                  <ul
                    className="py-2 text-sm text-gray-700 dark:text-gray-200"
                    aria-labelledby="dropdownDefaultButton"
                  >
                    <li>
                      <a
                        href="#"
                        className="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        Dashboard
                      </a>
                    </li>
                    <li>
                      <a
                        href="#"
                        className="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        Settings
                      </a>
                    </li>
                    <li>
                      <a
                        href="#"
                        className="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        Earnings
                      </a>
                    </li>
                    <li>
                      <a
                        href="#"
                        className="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                      >
                        Sign out
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div className="relative inline-block w-52">
              <select
                className="w-full px-4 py-2 pr-8 bg-zinc-100 text-stone-700 text-sm font-medium rounded-lg shadow-sm border border-zinc-200 
               focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer 
               appearance-none bg-[url('data:image/svg+xml;base64,')] bg-no-repeat bg-right hidden-arrow"
                defaultValue="Jan-Jun 2022"
                style={{
                  appearance: "none",
                  WebkitAppearance: "none",
                  MozAppearance: "none",
                  backgroundImage: "none",
                }}
              >
                <option value="Jan-Jun 2022">January - June 2022</option>
                <option value="Jul-Dec 2022">July - December 2022</option>
                <option value="Jan-Jun 2023">January - June 2023</option>
                <option value="Jul-Dec 2023">July - December 2023</option>
              </select>

              {/* Custom dropdown icon */}
              <div className="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                <img
                  src={dropdownIcon}
                  alt="dropdown icon"
                  className="w-4 h-4 opacity-70"
                />
              </div>
            </div>
          </div>
          <div className="mt-6 bg-white p-6 rounded-2xl shadow-md">
            <ResponsiveContainer width="100%" height={435}>
              <BarChart data={BookingData}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                <XAxis
                  dataKey="name"
                  tick={{ fill: "#333", fontSize: 12 }}
                  axisLine={false}
                  tickLine={false}
                />
                <YAxis
                  axisLine={false}
                  tickLine={false}
                  tick={{ fill: "#666", fontSize: 12 }}
                />
                <Tooltip
                  content={<CustomTooltip />}
                  cursor={{ fill: "rgba(59,130,246,0.1)" }}
                />
                <Bar dataKey="bookings" fill="#3b82f6" radius={[8, 8, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  );
}
