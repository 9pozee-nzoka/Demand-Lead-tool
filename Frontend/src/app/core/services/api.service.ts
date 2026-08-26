import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Paginated } from '../models/api.models';

/** Base API service with helper methods for all feature services */
@Injectable({ providedIn: 'root' })
export class ApiService {
  constructor(protected http: HttpClient) {}

  protected get<T>(url: string, params?: Record<string, any>): Observable<T> {
    return this.http.get<T>(url, { params: this.buildParams(params) });
  }

  protected post<T>(url: string, body: any): Observable<T> {
    return this.http.post<T>(url, body);
  }

  protected put<T>(url: string, body: any): Observable<T> {
    return this.http.put<T>(url, body);
  }

  protected patch<T>(url: string, body: any): Observable<T> {
    return this.http.patch<T>(url, body);
  }

  protected delete<T>(url: string): Observable<T> {
    return this.http.delete<T>(url);
  }

  protected getPaginated<T>(url: string, params?: Record<string, any>): Observable<Paginated<T>> {
    return this.http.get<Paginated<T>>(url, { params: this.buildParams(params) });
  }

  protected getBlob(url: string): Observable<Blob> {
    return this.http.get(url, { responseType: 'blob' });
  }

  private buildParams(obj?: Record<string, any>): HttpParams | undefined {
    if (!obj) return undefined;
    let params = new HttpParams();
    Object.keys(obj).forEach(key => {
      if (obj[key] !== null && obj[key] !== undefined) {
        params = params.set(key, String(obj[key]));
      }
    });
    return params;
  }
}
