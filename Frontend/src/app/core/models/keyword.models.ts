export type KeywordIntent = 'informational' | 'commercial' | 'transactional' | 'local' | 'unknown';
export type KeywordPriority = 'low' | 'medium' | 'high';
export type KeywordStatus = 'active' | 'paused' | 'archived';
export type TrendState = 'emerging' | 'rising' | 'rapidly_rising' | 'spike' | 'peak' | 'stable' | 'declining' | 'normal';

export interface KeywordLocation {
  id: number;
  keyword_id: number;
  country: string;
  region?: string;
  city?: string;
  county?: string;
  lat?: number;
  lng?: number;
  type: 'country' | 'region' | 'city' | 'county';
}

export interface KeywordMeasurement {
  id: number;
  keyword_id: number;
  source: string;
  date: string;
  interest: number;
  volume?: number;
  growth?: number;
  competition?: number;
  cpc?: number;
  geo?: string;
}

export interface Keyword {
  id: number;
  project_id: number;
  keyword: string;
  normalized_keyword: string;
  category?: string;
  intent: KeywordIntent;
  priority: KeywordPriority;
  status: KeywordStatus;
  created_at: string;
  locations?: KeywordLocation[];
  measurements?: KeywordMeasurement[];
}

export interface KeywordTrend {
  keyword: string;
  trend_state: TrendState;
  measurements: KeywordMeasurement[];
}
