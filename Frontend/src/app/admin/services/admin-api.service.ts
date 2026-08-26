import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AdminTokenService } from './admin-token.service';

/** HTTP service for all /api/admin/* calls. Injects admin Bearer token, not tenant token. */
@Injectable({ providedIn: 'root' })
export class AdminApiService {
  constructor(
    private http:  HttpClient,
    private token: AdminTokenService,
  ) {}

  private headers(): HttpHeaders {
    const t = this.token.getToken();
    return t
      ? new HttpHeaders({ Authorization: `Bearer ${t}` })
      : new HttpHeaders();
  }

  get<T>(url: string, params?: Record<string, any>): Observable<T> {
    return this.http.get<T>(url, { headers: this.headers(), params });
  }

  post<T>(url: string, body: any): Observable<T> {
    return this.http.post<T>(url, body, { headers: this.headers() });
  }

  patch<T>(url: string, body: any): Observable<T> {
    return this.http.patch<T>(url, body, { headers: this.headers() });
  }

  delete<T>(url: string): Observable<T> {
    return this.http.delete<T>(url, { headers: this.headers() });
  }
}
