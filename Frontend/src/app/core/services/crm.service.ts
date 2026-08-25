import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Deal, Task, Note, Paginated } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class CrmService extends ApiService {
  // ── Deals ──────────────────────────────────────────────────────────────────
  getDeals(params?: Record<string, any>): Observable<Paginated<Deal>> {
    return this.getPaginated<Deal>('/api/v1/deals', params);
  }
  getDeal(id: number): Observable<Deal> {
    return this.get<Deal>(`/api/v1/deals/${id}`);
  }
  createDeal(payload: Partial<Deal>): Observable<Deal> {
    return this.post<Deal>('/api/v1/deals', payload);
  }
  updateDeal(id: number, payload: Partial<Deal>): Observable<Deal> {
    return this.patch<Deal>(`/api/v1/deals/${id}`, payload);
  }
  deleteDeal(id: number): Observable<{ message: string }> {
    return this.delete(`/api/v1/deals/${id}`);
  }

  // ── Tasks ──────────────────────────────────────────────────────────────────
  getTasks(params?: Record<string, any>): Observable<Paginated<Task>> {
    return this.getPaginated<Task>('/api/v1/tasks', params);
  }
  createTask(payload: Partial<Task>): Observable<Task> {
    return this.post<Task>('/api/v1/tasks', payload);
  }
  updateTask(id: number, payload: Partial<Task>): Observable<Task> {
    return this.patch<Task>(`/api/v1/tasks/${id}`, payload);
  }
  deleteTask(id: number): Observable<{ message: string }> {
    return this.delete(`/api/v1/tasks/${id}`);
  }

  // ── Notes ──────────────────────────────────────────────────────────────────
  getNotes(params?: Record<string, any>): Observable<Paginated<Note>> {
    return this.getPaginated<Note>('/api/v1/notes', params);
  }
  createNote(payload: { lead_id?: number; deal_id?: number; body: string }): Observable<Note> {
    return this.post<Note>('/api/v1/notes', payload);
  }
  deleteNote(id: number): Observable<{ message: string }> {
    return this.delete(`/api/v1/notes/${id}`);
  }
}
