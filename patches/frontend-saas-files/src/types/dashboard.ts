export interface DashboardLeadStats {
  total: number;
  by_status: {
    new: number;
    open: number;
    booked: number;
    not_booked: number;
    archive: number;
  };
  today: number;
  this_week: number;
  this_month: number;
  conversion_rate: number;
}

export interface DashboardFolderStats {
  total: number;
  total_sell: number;
  total_cost: number;
  total_commission: number;
  total_remaining: number;
  by_invoice_status: Record<string, number>;
}

export interface DashboardPaymentStats {
  total_amount: number;
  approved_amount: number;
  pending_amount: number;
  total_count: number;
  approved_count: number;
  pending_count: number;
}

export interface DashboardTrendPoint {
  month: string;
  label: string;
  count: number;
}

export interface DashboardAgentRow {
  agent_id: number;
  name: string;
  email: string;
  total: number;
  booked: number;
  conversion: number;
}

export interface DashboardData {
  leads: DashboardLeadStats;
  folders: DashboardFolderStats;
  payments: DashboardPaymentStats;
  trend: DashboardTrendPoint[];
  agents: DashboardAgentRow[];
  recent: Array<Record<string, unknown>>;
  pipeline: {
    funnel: unknown;
    sla_breaches_count: number;
  } | null;
}

export interface DashboardResponse {
  status: boolean;
  data: DashboardData;
}
