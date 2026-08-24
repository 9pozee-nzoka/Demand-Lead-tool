import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Opportunity, OpportunityFilters, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class OpportunityService extends ApiService {
  private readonly api = '/api/v1/opportunities';

  getAll(filters?: OpportunityFilters): Observable<Paginated<Opportunity>> {
    return this.getPaginated<Opportunity>(this.api, filters);
  }

  getOne(id: number): Observable<Opportunity> {
    return this.get<Opportunity>(`${this.api}/${id}`);
  }

  update(id: number, payload: { status: string }): Observable<Opportunity> {
    return this.patch<Opportunity>(`${this.api}/${id}`, payload);
  }

  action(id: number, action: string): Observable<{ message: string; action: string; opportunity: Opportunity }> {
    return this.post(`${this.api}/${id}/action`, { action });
  }

  dismiss(id: number): Observable<{ message: string }> {
    return this.post(`${this.api}/${id}/dismiss`, {});
  }
}
