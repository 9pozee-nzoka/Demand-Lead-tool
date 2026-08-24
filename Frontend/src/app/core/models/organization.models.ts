export type OrgStatus = 'active' | 'suspended' | 'cancelled';

export interface Organization {
  id: number;
  name: string;
  slug: string;
  industry?: string;
  country?: string;
  timezone: string;
  plan_id?: number;
  status: OrgStatus;
  created_at: string;
  updated_at: string;
  plan?: Plan;
  active_subscription?: Subscription;
}

export interface Plan {
  id: number;
  name: string;
  slug: string;
  monthly_price: number;
  limits: Record<string, number>;
  features: string[];
  is_active: boolean;
}

export interface Subscription {
  id: number;
  organization_id: number;
  plan_id: number;
  provider: string;
  external_id?: string;
  status: 'active' | 'trialing' | 'past_due' | 'cancelled';
  renewal_at?: string;
}
