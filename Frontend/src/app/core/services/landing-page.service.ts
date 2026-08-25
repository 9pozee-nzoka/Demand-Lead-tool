import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { LandingPage, LandingPageFilters, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class LandingPageService extends ApiService {
  private readonly base = '/api/v1/landing-pages';

  getAll(filters?: LandingPageFilters): Observable<Paginated<LandingPage>> {
    return this.getPaginated<LandingPage>(this.base, filters as Record<string, any>);
  }

  getOne(id: number): Observable<LandingPage> {
    return this.get<LandingPage>(`${this.base}/${id}`);
  }

  create(payload: Partial<LandingPage>): Observable<LandingPage> {
    return this.post<LandingPage>(this.base, payload);
  }

  update(id: number, payload: Partial<LandingPage>): Observable<LandingPage> {
    return this.patch<LandingPage>(`${this.base}/${id}`, payload);
  }

  remove(id: number): Observable<{ message: string }> {
    return this.delete(`${this.base}/${id}`);
  }

  publish(id: number): Observable<LandingPage> {
    return this.patch<LandingPage>(`${this.base}/${id}`, { status: 'published' });
  }

  unpublish(id: number): Observable<LandingPage> {
    return this.patch<LandingPage>(`${this.base}/${id}`, { status: 'draft' });
  }
}
