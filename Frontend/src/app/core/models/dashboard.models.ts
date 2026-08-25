export interface DashboardStats {
  opportunities: { total: number; high_score: number };
  leads: { total: number; this_month: number; hot: number; unassigned: number };
  deals: { open: number; won: number; revenue: number };
  top_opportunities: TopOpportunity[];
  trending_keywords: TrendingKeyword[];
}

export interface TopOpportunity {
  id: number;
  keyword?: string;
  intent?: string;
  location?: string;
  project?: string;
  opportunity_score: number;
  trend_state: string;
  score_label: string;
  status: string;
}

export interface TrendingKeyword {
  keyword_id: number;
  keyword?: string;
  intent?: string;
  growth: number;
  interest: number;
  geo?: string;
  date: string;
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
