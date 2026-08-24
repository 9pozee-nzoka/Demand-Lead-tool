/** Generic paginated response from Laravel */
export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

/** Standard API error shape */
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

/** Re-export all models for convenience */
export * from './auth.models';
export * from './organization.models';
export * from './project.models';
export * from './keyword.models';
export * from './opportunity.models';
export * from './lead.models';
export * from './crm.models';
export * from './alert.models';
export * from './dashboard.models';
