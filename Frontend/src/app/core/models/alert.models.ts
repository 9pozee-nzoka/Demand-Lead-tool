export type AlertChannel = 'sms' | 'email' | 'whatsapp' | 'push' | 'dashboard' | 'webhook';
export type AlertStatus = 'pending' | 'sent' | 'failed' | 'read';
export type AlertType = 'opportunity' | 'lead' | 'market_gap' | 'competitor' | 'system';

export interface Alert {
  id: number;
  organization_id: number;
  opportunity_id?: number;
  lead_id?: number;
  alert_rule_id?: number;
  type: AlertType;
  channel: AlertChannel;
  recipient?: string;
  message: string;
  payload?: Record<string, unknown>;
  status: AlertStatus;
  sent_at?: string;
  created_at: string;
  opportunity?: { id: number; title?: string; opportunity_score: number };
}

export interface AlertRule {
  id: number;
  organization_id: number;
  project_id?: number;
  name: string;
  minimum_score: number;
  minimum_growth: number;
  intent: 'any' | 'commercial' | 'transactional' | 'local';
  location?: string;
  cooldown: number;
  channels?: AlertChannel[];
  recipients?: string[];
  quiet_hours?: { start: string; end: string };
  status: 'active' | 'paused';
  created_at: string;
  project?: { id: number; name: string };
}
