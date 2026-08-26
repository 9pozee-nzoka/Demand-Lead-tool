import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { tap } from 'rxjs/operators';
import { Observable } from 'rxjs';
import { AdminTokenService } from './admin-token.service';
import { AdminApiService } from './admin-api.service';

export interface AdminUser {
  id:             number;
  name:           string;
  email:          string;
  is_super_admin: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminAuthService {
  readonly user = signal<AdminUser | null>(null);

  constructor(
    private http:   HttpClient,
    private token:  AdminTokenService,
    private router: Router,
  ) {}

  /**
   * Login reuses the main /api/v1/auth/login endpoint — the same Sanctum token
   * is used, but stored separately under admin_token. Access is gated on
   * is_super_admin=true server-side on every admin API call.
   */
  login(email: string, password: string): Observable<any> {
    return this.http.post<any>('/api/v1/auth/login', { email, password }).pipe(
      tap((res) => {
        this.token.setToken(res.token);
        if (res.user?.is_super_admin) {
          this.user.set(res.user);
        } else {
          // Not a super-admin — clear token and throw
          this.token.clear();
          throw new Error('This account does not have super-admin access.');
        }
      }),
    );
  }

  logout(): void {
    this.token.clear();
    this.user.set(null);
    this.router.navigate(['/admin/login']);
  }

  loadCurrentUser(apiService: AdminApiService): Observable<AdminUser> {
    return apiService.get<AdminUser>('/api/admin/me').pipe(
      tap((u: AdminUser) => this.user.set(u)),
    );
  }

  isLoggedIn(): boolean {
    return this.token.hasToken();
  }

  initials(): string {
    const name = this.user()?.name ?? '';
    return name.split(' ').map((w: string) => w[0]).slice(0, 2).join('').toUpperCase();
  }
}
