export type LandingPageStatus   = 'draft' | 'published' | 'archived';
export type LandingPageTemplate = 'minimal' | 'hero' | 'form_only' | 'split' | 'video';

export interface LandingPage {
  id: number;
  project_id: number;
  opportunity_id?: number;
  slug: string;
  title: string;
  content?: string;
  template: LandingPageTemplate;
  meta?: {
    description?: string;
    keywords?: string;
    og_image?: string;
  };
  status: LandingPageStatus;
  published_at?: string;
  created_at: string;
  leads_count?: number;
  project?: { id: number; name: string };
  opportunity?: { id: number; opportunity_score: number; trend_state: string };
}

export interface LandingPageFilters {
  project_id?: number;
  status?: LandingPageStatus;
}
