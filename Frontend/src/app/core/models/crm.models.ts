export type DealStage = 'new' | 'contacted' | 'qualified' | 'quotation' | 'negotiation' | 'won' | 'lost';
export type DealStatus = 'open' | 'won' | 'lost';
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';
export type TaskType = 'call' | 'email' | 'meeting' | 'follow_up' | 'other';

export interface Deal {
  id: number;
  organization_id: number;
  lead_id?: number;
  contact_id?: number;
  title: string;
  value?: number;
  currency: string;
  stage: DealStage;
  status: DealStatus;
  assigned_to?: number;
  lost_reason?: string;
  expected_close_at?: string;
  won_at?: string;
  lost_at?: string;
  created_at: string;
  lead?: { id: number; name?: string };
  contact?: { id: number; name: string };
  assigned_user?: { id: number; name: string };
}

export interface Contact {
  id: number;
  organization_id: number;
  lead_id?: number;
  name: string;
  email?: string;
  phone?: string;
  company?: string;
  tags?: string[];
  created_at: string;
}

export interface Task {
  id: number;
  organization_id: number;
  lead_id?: number;
  deal_id?: number;
  assigned_user_id?: number;
  title: string;
  description?: string;
  type: TaskType;
  status: TaskStatus;
  due_at?: string;
  completed_at?: string;
  created_at: string;
  assigned_user?: { id: number; name: string };
}

export interface Note {
  id: number;
  organization_id: number;
  lead_id?: number;
  deal_id?: number;
  user_id?: number;
  body: string;
  created_at: string;
  user?: { id: number; name: string };
}
