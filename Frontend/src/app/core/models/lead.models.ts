export type LeadIntent = 'informational' | 'commercial' | 'transactional' | 'local' | 'unknown';
export type LeadStatus = 'new' | 'contacted' | 'qualified' | 'quotation' | 'negotiation' | 'won' | 'lost' | 'unqualified';
export type LeadScoreLabel = 'hot' | 'warm' | 'potential' | 'low';

export interface Lead {
  id: number;
  organization_id: number;
  project_id: number;
  opportunity_id?: number;
  campaign_id?: number;
  name?: string;
  email?: string;
  phone?: string;
  location?: string;
  company?: string;
  source: string;
  intent: LeadIntent;
  lead_score: number;
  score_label: LeadScoreLabel;
  status: LeadStatus;
  assigned_to?: number;
  qualification_summary?: string;
  qualification_data?: Record<string, unknown>;
  qualified_at?: string;
  contacted_at?: string;
  created_at: string;
  updated_at: string;
  assigned_user?: { id: number; name: string };
  opportunity?: { id: number; title?: string; opportunity_score: number };
  campaign?: { id: number; name: string };
  events?: LeadEvent[];
}

export interface LeadEvent {
  id: number;
  lead_id: number;
  type: string;
  metadata?: Record<string, unknown>;
  occurred_at: string;
}

export interface LeadFilters {
  status?: LeadStatus;
  score_label?: LeadScoreLabel;
  project_id?: number;
}
