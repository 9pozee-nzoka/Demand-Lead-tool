import { Injectable } from '@angular/core';

const KEY = 'admin_token';

/** Separate token storage for the super-admin panel — never mixed with tenant tokens. */
@Injectable({ providedIn: 'root' })
export class AdminTokenService {
  getToken(): string | null    { return localStorage.getItem(KEY); }
  setToken(t: string): void    { localStorage.setItem(KEY, t); }
  clear(): void                { localStorage.removeItem(KEY); }
  hasToken(): boolean          { return !!this.getToken(); }
}
