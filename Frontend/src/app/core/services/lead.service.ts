import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Lead, LeadFilters, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class LeadService extends ApiService {
  private readonly api = '/api/v1/leads';

  getAll(filters?: LeadFilters): Observable<Paginated<Lead>> {
    return this.getPaginated<Lead>(this.api, filters);
  }

  getOne(id: number): Observable<Lead> {
    return this.get<Lead>(`${this.api}/${id}`);
  }

  create(payload: Partial<Lead>): Observable<Lead> {
    return this.post<Lead>(this.api, payload);
  }

  update(id: number, payload: Partial<Lead>): Observable<Lead> {
    return this.patch<Lead>(`${this.api}/${id}`, payload);
  }

  qualify(id: number, payload: { qualification_summary: string; lead_score?: number }): Observable<Lead> {
    return this.post<Lead>(`${this.api}/${id}/qualify`, payload);
  }

  assign(id: number, userId: number): Observable<Lead> {
    return this.post<Lead>(`${this.api}/${id}/assign`, { user_id: userId });
  }

  convert(id: number, payload?: { deal_title?: string; deal_value?: number }): Observable<{ lead: Lead; deal: any }> {
    return this.post(`${this.api}/${id}/convert`, payload || {});
  }
}
