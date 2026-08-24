import { Injectable, signal, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap } from 'rxjs';
import { TokenStorage } from './token.storage';
import {
  AuthResponse,
  LoginRequest,
  RegisterRequest,
  User,
} from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = '/api/v1/auth';

  // Reactive state
  private _user = signal<User | null>(this.tokenStorage.getUser<User>());

  readonly user   = this._user.asReadonly();
  readonly isAuth = computed(() => !!this._user());
  readonly role   = computed(() => this._user()?.role ?? null);
  readonly orgId  = computed(() => this._user()?.organization_id ?? null);

  constructor(
    private http: HttpClient,
    private router: Router,
    private tokenStorage: TokenStorage,
  ) {}

  login(payload: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.api}/login`, payload).pipe(
      tap(res => this.persist(res)),
    );
  }

  register(payload: RegisterRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.api}/register`, payload).pipe(
      tap(res => this.persist(res)),
    );
  }

  logout(): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/logout`, {}).pipe(
      tap(() => this.clear()),
    );
  }

  me(): Observable<User> {
    return this.http.get<User>(`${this.api}/me`).pipe(
      tap(user => {
        this._user.set(user);
        this.tokenStorage.setUser(user);
      }),
    );
  }

  forgotPassword(email: string): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/forgot-password`, { email });
  }

  resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(`${this.api}/reset-password`, payload);
  }

  logoutLocal(): void {
    this.clear();
    this.router.navigate(['/login']);
  }

  hasRole(...roles: string[]): boolean {
    return roles.includes(this._user()?.role ?? '');
  }

  isAdmin(): boolean {
    return this.hasRole('owner', 'admin');
  }

  private persist(res: AuthResponse): void {
    this.tokenStorage.setToken(res.token);
    this.tokenStorage.setUser(res.user);
    this._user.set(res.user);
  }

  private clear(): void {
    this.tokenStorage.clear();
    this._user.set(null);
  }
}
