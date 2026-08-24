import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Keyword, KeywordTrend } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class KeywordService extends ApiService {
  private readonly api = '/api/v1/keywords';

  getAll(projectId: number): Observable<Keyword[]> {
    return this.get<Keyword[]>(this.api, { project_id: projectId });
  }

  getOne(id: number): Observable<Keyword> {
    return this.get<Keyword>(`${this.api}/${id}`);
  }

  create(payload: { project_id: number; keyword: string; category?: string }): Observable<Keyword> {
    return this.post<Keyword>(this.api, payload);
  }

  bulkCreate(projectId: number, keywords: string[]): Observable<{ created: number; keywords: Keyword[] }> {
    return this.post(`${this.api}/bulk`, { project_id: projectId, keywords });
  }

  update(id: number, payload: Partial<Keyword>): Observable<Keyword> {
    return this.patch<Keyword>(`${this.api}/${id}`, payload);
  }

  remove(id: number): Observable<{ message: string }> {
    return this.delete<{ message: string }>(`${this.api}/${id}`);
  }

  getTrend(id: number): Observable<KeywordTrend> {
    return this.get<KeywordTrend>(`${this.api}/${id}/trend`);
  }

  getHistory(id: number, days = 90): Observable<any[]> {
    return this.get<any[]>(`${this.api}/${id}/history`, { days });
  }
}
