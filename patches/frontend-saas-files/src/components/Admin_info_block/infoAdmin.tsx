import bookedFolder from "../../assets/images/booked_folder.svg";
import invoiceFolder from "../../assets/images/invoice_folder.svg";
import requetedFolder from "../../assets/images/request_folder.svg";
import type {
  DashboardFolderStats,
  DashboardPaymentStats,
} from "../../types/dashboard";

type Props = {
  folders?: DashboardFolderStats;
  payments?: DashboardPaymentStats;
};

const infoAdmin = ({ folders, payments }: Props) => {
  const byInvoice = folders?.by_invoice_status ?? {};
  const invoiceKeys = Object.keys(byInvoice);
  const bookedCount = folders?.total ?? 0;
  const invoiceCount =
    invoiceKeys.length > 0
      ? Number(byInvoice[invoiceKeys[0]] ?? 0)
      : payments?.approved_count ?? 0;
  const requestCount =
    invoiceKeys.length > 1
      ? Number(byInvoice[invoiceKeys[1]] ?? 0)
      : payments?.pending_count ?? 0;

  return (
    <div>
      <h2 className="self-stretch justify-start text-black text-2xl font-medium font-['Poppins'] leading-tight">
        Folders
      </h2>

      <div className="grid grid-cols-3 mt-5 gap-5">
        <div className="bg-gradient-to-r from-stone-300 to-neutral-400 rounded-xl border border-neutral-200 p-6">
          <div className="flex justify-between items-center">
            <div className="w-14 h-14 bg-white/10 rounded-lg flex items-center justify-center">
              <img src={bookedFolder} alt="booked Folder" />
            </div>
            <div>
              <h2 className="text-stone-700 text-xl font-normal font-['Poppins']">
                Total Folders
              </h2>
              <p className="text-zinc-900 text-2xl font-semibold font-['Poppins'] mt-1">
                {bookedCount.toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-gradient-to-r from-amber-100 to-orange-200 rounded-xl border border-neutral-200 p-6">
          <div className="flex justify-between items-center">
            <div className="w-14 h-14 bg-white/10 rounded-lg flex items-center justify-center">
              <img src={invoiceFolder} alt="invoice Folder" />
            </div>
            <div>
              <h2 className="text-stone-700 text-xl font-normal font-['Poppins']">
                Approved Payments
              </h2>
              <p className="text-zinc-900 text-2xl font-semibold font-['Poppins'] mt-1">
                {invoiceCount.toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-gradient-to-r from-emerald-200 to-teal-300 rounded-xl border border-neutral-200 p-6">
          <div className="flex justify-between items-center">
            <div className="w-14 h-14 bg-white/10 rounded-lg flex items-center justify-center">
              <img src={requetedFolder} alt="request Folder" />
            </div>
            <div>
              <h2 className="text-stone-700 text-xl font-normal font-['Poppins']">
                Pending Payments
              </h2>
              <p className="text-zinc-900 text-2xl font-semibold font-['Poppins'] mt-1">
                {requestCount.toLocaleString()}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default infoAdmin;
