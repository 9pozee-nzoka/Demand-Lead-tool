import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { DashboardStats, FunnelStats, RoiStats } from '../models/api.models';

@Injectable({ providedIn: 'root' })
export class DashboardService extends ApiService {
  private readonly api = '/api/v1/dashboard';

  getStats(): Observable<DashboardStats> {
    return this.get<DashboardStats>(this.api);
  }

  getFunnel(): Observable<FunnelStats> {
    return this.get<FunnelStats>(`${this.api}/funnel`);
  }

  getRoi(): Observable<RoiStats> {
    return this.get<RoiStats>(`${this.api}/roi`);
  }
}
