import "./Component.css";
import Navbar from "../../components/Navbar/Navbar";
import DashboardShell from "../../components/Dashboard/DashboardShell";

const Dashboard = () => {
  return (
    <div className="p-8 space-y-12">
      <Navbar />
      <DashboardShell showCharts={false} />
    </div>
  );
};

export default Dashboard;
