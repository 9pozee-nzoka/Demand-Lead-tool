import { TrendState } from './keyword.models';

export type OpportunityStatus = 'detected' | 'reviewed' | 'actioned' | 'converting' | 'won' | 'dismissed' | 'expired';
export type OpportunityScoreLabel = 'LOW' | 'MODERATE' | 'HIGH' | 'VERY HIGH';

export interface Opportunity {
  id: number;
  project_id: number;
  keyword_id?: number;
  cluster_id?: number;
  location_id?: number;
  growth_score: number;
  intent_score: number;
  geo_score: number;
  volume_score: number;
  competition_score: number;
  historical_score: number;
  opportunity_score: number;
  title?: string;
  explanation?: string;
  recommended_actions?: string[];
  trend_state: TrendState;
  status: OpportunityStatus;
  detected_at?: string;
  expires_at?: string;
  created_at: string;
  keyword?: { id: number; keyword: string; intent: string };
  location?: { id: number; city?: string; region?: string; country: string };
  project?: { id: number; name: string };
}

export interface OpportunityFilters {
  status?: OpportunityStatus;
  min_score?: number;
  project_id?: number;
}
