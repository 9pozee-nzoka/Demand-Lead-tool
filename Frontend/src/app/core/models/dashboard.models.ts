export interface DashboardStats {
  opportunities: {
    total: number;
    high_score: number;
  };
  leads: {
    total: number;
    this_month: number;
    hot: number;
    unassigned: number;
  };
  deals: {
    open: number;
    won: number;
    revenue: number;
  };
}

export interface FunnelStats {
  opportunities: number;
  leads: number;
  qualified: number;
  deals: number;
  won_deals: number;
  revenue: number;
}

export interface RoiStats {
  period: string;
  revenue: number;
  leads_generated: number;
  ai_requests: number;
  alerts_sent: number;
}
