import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Alert, AlertRule, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class AlertService extends ApiService {
  private readonly base = '/api/v1/alerts';
  private readonly rulesBase = '/api/v1/alert-rules';

  getAlerts(params?: Record<string, any>): Observable<Paginated<Alert>> {
    return this.getPaginated<Alert>(this.base, params);
  }

  markRead(id: number): Observable<Alert> {
    return this.patch<Alert>(`${this.base}/${id}/read`, {});
  }

  markAllRead(): Observable<{ message: string }> {
    return this.post(`${this.base}/read-all`, {});
  }

  getRules(): Observable<AlertRule[]> {
    return this.get<AlertRule[]>(this.rulesBase);
  }

  createRule(payload: Partial<AlertRule>): Observable<AlertRule> {
    return this.post<AlertRule>(this.rulesBase, payload);
  }

  updateRule(id: number, payload: Partial<AlertRule>): Observable<AlertRule> {
    return this.patch<AlertRule>(`${this.rulesBase}/${id}`, payload);
  }

  deleteRule(id: number): Observable<{ message: string }> {
    return this.delete(`${this.rulesBase}/${id}`);
  }
}
