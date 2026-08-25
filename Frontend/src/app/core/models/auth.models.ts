import { Organization } from './organization.models';

export interface LoginRequest {
  email: string;
  password: string;
  device?: string;
}

export interface RegisterRequest {
  organization_name: string;
  industry?: string;
  country?: string;
  timezone?: string;
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  message: string;
  token: string;
  token_type: string;
  user: User;
}

export type UserRole = 'owner' | 'admin' | 'analyst' | 'marketing' | 'sales' | 'viewer';
export type UserStatus = 'active' | 'inactive' | 'invited';

export interface User {
  id: number;
  organization_id: number;
  name: string;
  email: string;
  phone?: string;
  role: UserRole;
  status: UserStatus;
  two_factor_enabled: boolean;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  organization?: Organization;
}
