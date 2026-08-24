export type ProjectStatus = 'active' | 'paused' | 'archived';

export interface Project {
  id: number;
  organization_id: number;
  name: string;
  industry?: string;
  country?: string;
  default_location?: string;
  status: ProjectStatus;
  created_at: string;
  updated_at: string;
  keywords_count?: number;
  opportunities_count?: number;
  leads_count?: number;
}

export interface CreateProjectRequest {
  name: string;
  industry?: string;
  country?: string;
  default_location?: string;
}
