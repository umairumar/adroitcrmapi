// import React from "react";
// import { Calendar } from "lucide-react";
import TravelStartIcon from "../../assets/images/Travel_Start_Date.svg";
import TravelEndIcon from "../../assets/images/Travel_End_Data.svg";
import EditIcon from "../../assets/images/edit_icon.svg";
import ViewIcon from "../../assets/images/view_icon.svg";
import CompleteIcon from "../../assets/images/Complete_Icon.svg";
import LeadIcon from "../../assets/images/Lead_icon.svg";
import PendingIcon from "../../assets/images/Pending_icon.svg";
import PaymentIcon from "../../assets/images/Payment_icon.svg";
import ClockIcon from "../../assets/images/clock_icon.svg"


const bookings = [
  {
    id: "#4510",
    customer: "Ahmed Khan",
    route: "LHR → JED",
    travelers: "4 Travelers",
    leadType: "Hajj & Umrah",
    travelDate1: "20-08-2025",
    travelDate2: "26-08-2025",
    status: "Confirmed",
    amount: "£8,500",
  },
  {
    id: "#4510",
    customer: "Ahmed Khan",
    route: "MAN → MED",
    travelers: "4 Travelers",
    leadType: "Umrah",
    travelDate1: "20-08-2025",
    travelDate2: "26-08-2025",
    status: "Processing",
    amount: "£8,500",
  },
  {
    id: "#4510",
    customer: "Ahmed Khan",
    route: "BHX → MED",
    travelers: "4 Travelers",
    leadType: "Hajj",
    travelDate1: "20-08-2025",
    travelDate2: "30-08-2025",
    status: "Pending",
    amount: "£8,500",
  },
  {
    id: "#4510",
    customer: "Ahmed Khan",
    route: "LHR → MAN",
    travelers: "4 Travelers",
    leadType: "Hajj & Umrah",
    travelDate1: "20-08-2025",
    travelDate2: "04-09-2025",
    status: "Confirmed",
    amount: "£8,500",
  },
];

type StatusType = "Confirmed" | "Processing" | "Pending";

const statusClasses = {
  Confirmed: "items-center text-green-800 text-xs font-medium font-['Poppins'] leading-6",
  Processing: "text-sky-500 text-sm font-medium font-['Poppins'] leading-4 p-2",
  Pending: "text-yellow-700 text-sm font-medium font-['Poppins'] leading-4 p-2",
};

const statusClasses2 = {
  Confirmed: "bg-green-100",
  Processing: "bg-blue-100",
  Pending: "bg-yellow-100",
};


const RecentBooking = () => {
  return (
    <>


      <div className="bg-white rounded-xl shadow-sm border">
        <h2 className="text-xl font-semibold mb-4 p-6">Recent Bookings</h2>

        <div className="relative overflow-x-auto mt-5 border border-gray-200 rounded-lg shadow-sm">
          <table className="w-full text-sm text-left border-collapse border border-neutral-200 rounded-lg">
            <thead className="text-Input-Lable-Color text-base font-medium font-['Inter'] leading-6">
              <tr className="bg-neutral-100">
                {/* <th scope="col" className="px-6 py-4 border border-neutral-200">Category</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Payment Heads</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Passengers</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Hotels</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Transport</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Others</th>
                  <th scope="col" className="px-6 py-4 border border-neutral-200">Total</th> */}
                <th className="px-6 py-4 border border-neutral-200">Customer</th>
                <th className="px-6 py-4 border border-neutral-200">Travel Route</th>
                <th className="px-6 py-4 border border-neutral-200">Lead Type</th>
                <th className="px-6 py-4 border border-neutral-200">Travel Date</th>
                <th className="px-6 py-4 border border-neutral-200">Status</th>
                <th className="px-6 py-4 border border-neutral-200">Amount</th>
                <th className="px-6 py-4 border border-neutral-200">Action</th>
              </tr>
            </thead>


            <tbody>
              {
                bookings.map((data, index) => (
                  <tr key={index} className="bg-white">
                    <td className="px-6 py-4 border border-neutral-200 self-stretch text-Input-Lable-Color text-sm font-medium font-['Poppins'] leading-6">
                      {/* {data.id} <br/>
                        {data.customer} */}
                      <p>{data.id}</p>
                      <p>{data.customer}</p>
                    </td>
                    <td className="px-6 py-4 border border-neutral-200 text-Normal-Color text-sm font-normal font-['Poppins'] leading-6">
                      {/* {data.route} <br/>
                      {data.travelers} */}
                      <p>{data.route}</p>
                      <p className="text-Input-field-Text-Color text-sm font-normal font-['Poppins'] leading-6">{data.travelers}</p>
                    </td>

                    <td className="px-6 py-4 border border-neutral-200 text-Normal-Color text-sm font-normal font-['Poppins'] leading-6">
                      {data.leadType}
                    </td>

                    <td className="px-6 py-4 border border-neutral-200 text-Normal-Color text-sm font-normal font-['Poppins'] leading-6">
                      <p className="flex items-center gap-2">
                        <img src={TravelStartIcon} alt="Travel Start" />
                        <span>{data.travelDate1}</span>
                      </p>
                      <p className="flex items-center gap-2 mt-2">
                        <img src={TravelEndIcon} alt="Travel Start" />
                        <span>{data.travelDate2}</span>
                      </p>
                    </td>

                    <td className={`px-6 py-4 border border-neutral-200`}>
                      <p className={`
                        h-7 px-2.5 py-2 rounded-md inline-flex justify-center 
                       ${statusClasses[data.status as StatusType]} 
                        ${statusClasses2[data.status as StatusType]}
                                   `}>
                        {data.status}
                      </p>
                    </td>

                    <td className="px-6 py-4 border border-neutral-200 text-Normal-Color text-sm font-normal font-['Poppins'] leading-6">
                      {data.amount}
                    </td>

                    <td className="px-6 py-4 border  border-neutral-200">
                      <div className="flex justify-center items-center gap-3">

                        <img
                          className="p-2 bg-white rounded-lg outline outline-1 outline-offset-[-1px] outline-gray-300 h-10 w-10 cursor-pointer"
                          src={EditIcon}
                          alt="Edit"
                        />

                        <img
                          className="p-2 bg-white rounded-lg outline outline-1 outline-offset-[-1px] outline-gray-300 h-10 w-10 cursor-pointer"
                          src={ViewIcon}
                          alt="View"
                        />

                      </div>
                    </td>

                  </tr>
                ))
              }
            </tbody>
          </table>
        </div>

      </div>

      <div className="bg-stone-50  rounded-xl shadow-sm border p-6">
        <div className="flex justify-between items-center">
          <h2 className="text-stone-700 text-2xl font-medium font-['Poppins']">Reminder</h2>
          <p className="text-sky-500 text-xs font-medium font-['Inter']">See All</p>
        </div>

        <div className="grid grid-cols-2 gap-5 mt-8">
          <div className="p-4 bg-white rounded-xl border border-neutral-200 flex justify-between items-center">
            <div className="flex items-center gap-5">
              <div className="bg-green-100 rounded p-2">
                <img src={CompleteIcon} alt="CompleteIcon" />
              </div>

              <div>
                <h2 className="text-stone-700 text-base font-medium font-['Poppins'] leading-5">
                  Customer Document Completed
                </h2>

                <p className="text-Input-field-Text-Color text-sm font-normal font-['Inter'] leading-5 mt-1">
                  Customer:
                  <span className="text-black text-sm font-medium font-['Inter'] leading-5">
                    Fatima Ahmed
                  </span>
                </p>

                <p className="text-Input-field-Text-Color text-xs font-normal font-['Inter'] leading-5 flex gap-2 items-center mt-1">
                  <img src={ClockIcon} alt="ClockIcon" /> Today 11:45 AM
                </p>
              </div>
            </div>

            <div className="p-2 bg-green-100 rounded-md inline-flex justify-center items-center">
              <p className="text-green-800 text-xs font-medium font-['Poppins'] leading-4">
                Completed
              </p>
            </div>
          </div>

          <div className="p-4 bg-white rounded-xl border border-neutral-200 flex justify-between items-center">
            <div className="flex items-center gap-5">
              <div className="bg-blue-100 rounded p-2">
                <img src={LeadIcon} alt="LeadIcon" />
              </div>

              <div>
                <h2 className="text-stone-700 text-base font-medium font-['Poppins'] leading-5">
                  Customer Document Completed
                </h2>

                <p className="text-Input-field-Text-Color text-sm font-normal font-['Inter'] leading-5 mt-1">
                  Customer:
                  <span className="text-black text-sm font-medium font-['Inter'] leading-5">
                    Fatima Ahmed
                  </span>
                </p>

                <p className="text-Input-field-Text-Color text-xs font-normal font-['Inter'] leading-5 flex gap-2 items-center mt-1">
                  <img src={ClockIcon} alt="ClockIcon" /> Today 11:45 AM
                </p>
              </div>
            </div>

            <div className="p-2 bg-blue-100 rounded-md inline-flex justify-center items-center">
              <p className="text-sky-500 text-xs font-medium font-['Poppins'] leading-4">
                New
              </p>
            </div>
          </div>

           <div className="p-4 bg-white rounded-xl border border-neutral-200 flex justify-between items-center">
            <div className="flex items-center gap-5">
              <div className="bg-amber-100 rounded p-2">
                <img src={PendingIcon} alt="PendingIcon" />
              </div>

              <div>
                <h2 className="text-stone-700 text-base font-medium font-['Poppins'] leading-5">
                  Visa Application submitted
                </h2>

                <p className="text-Input-field-Text-Color text-sm font-normal font-['Inter'] leading-5 mt-1">
                  Customer:
                  <span className="text-black text-sm font-medium font-['Inter'] leading-5">
                    Fatima Ahmed
                  </span>
                </p>

                <p className="text-Input-field-Text-Color text-xs font-normal font-['Inter'] leading-5 flex gap-2 items-center mt-1">
                  <img src={ClockIcon} alt="ClockIcon" /> Today 11:45 AM
                </p>
              </div>
            </div>

            <div className="p-2 bg-amber-100 rounded-md inline-flex justify-center items-center">
              <p className="text-amber-800 text-xs font-medium font-['Poppins'] leading-4">
                Pending
              </p>
            </div>
          </div>

              <div className="p-4 bg-white rounded-xl border border-neutral-200 flex justify-between items-center">
            <div className="flex items-center gap-5">
              <div className="bg-green-100 rounded p-2">
                <img src={PaymentIcon} alt="PaymentIcon" />
              </div>

              <div>
                <h2 className="text-stone-700 text-base font-medium font-['Poppins'] leading-5">
                 Payment Reminder for travel booking
                </h2>

                <p className="text-Input-field-Text-Color text-sm font-normal font-['Inter'] leading-5 mt-1">
                  Customer:
                  <span className="text-black text-sm font-medium font-['Inter'] leading-5">
                    Fatima Ahmed
                  </span>
                </p>

                <p className="text-Input-field-Text-Color text-xs font-normal font-['Inter'] leading-5 flex gap-2 items-center mt-1">
                  <img src={ClockIcon} alt="ClockIcon" /> Today 11:45 AM
                </p>
              </div>
            </div>

            <div className="p-2 bg-green-100 rounded-md inline-flex justify-center items-center">
              <p className="text-green-800 text-xs font-medium font-['Poppins'] leading-4">
                Completed
              </p>
            </div>
          </div>

        </div>

      </div>

    </>
  );
};

export default RecentBooking;
