import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Project, CreateProjectRequest } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class ProjectService extends ApiService {
  private readonly api = '/api/v1/projects';

  getAll(): Observable<Project[]> {
    return this.get<Project[]>(this.api);
  }

  getOne(id: number): Observable<Project> {
    return this.get<Project>(`${this.api}/${id}`);
  }

  create(payload: CreateProjectRequest): Observable<Project> {
    return this.post<Project>(this.api, payload);
  }

  update(id: number, payload: Partial<CreateProjectRequest>): Observable<Project> {
    return this.patch<Project>(`${this.api}/${id}`, payload);
  }

  remove(id: number): Observable<{ message: string }> {
    return this.delete<{ message: string }>(`${this.api}/${id}`);
  }
}
