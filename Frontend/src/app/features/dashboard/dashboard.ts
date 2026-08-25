import { Component, OnInit, signal, computed } from '@angular/core';
import { DecimalPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { DashboardService } from '../../core/services/dashboard.service';
import { DashboardStats, FunnelStats, RoiStats } from '../../core/models/api.models';
import { StatCard } from '../../shared/components/stat-card/stat-card';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { CurrencyKePipe } from '../../shared/pipes/currency-ke.pipe';
import { Badge } from '../../shared/components/badge/badge';

@Component({
  selector: 'app-dashboard',
  imports: [StatCard, PageHeader, LoadingSpinner, CurrencyKePipe, RouterLink, DecimalPipe, Badge],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss',
})
export class Dashboard implements OnInit {
  loading = signal(true);
  stats   = signal<DashboardStats | null>(null);
  funnel  = signal<FunnelStats | null>(null);
  roi     = signal<RoiStats | null>(null);

  topOpportunities = computed(() => this.stats()?.top_opportunities ?? []);
  trendingKeywords = computed(() => this.stats()?.trending_keywords ?? []);

  constructor(private dashboardService: DashboardService) {}

  ngOnInit(): void {
    forkJoin({
      stats:  this.dashboardService.getStats(),
      funnel: this.dashboardService.getFunnel(),
      roi:    this.dashboardService.getRoi(),
    }).subscribe({
      next: (res) => {
        this.stats.set(res.stats);
        this.funnel.set(res.funnel);
        this.roi.set(res.roi);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  get conversionRate(): string {
    const f = this.funnel();
    if (!f || !f.leads) return '0%';
    return ((f.won_deals / f.leads) * 100).toFixed(1) + '%';
  }

  get qualificationRate(): string {
    const f = this.funnel();
    if (!f || !f.leads) return '0%';
    return ((f.qualified / f.leads) * 100).toFixed(1) + '%';
  }

  funnelPercent(value: number, total: number): number {
    if (!total) return 0;
    return Math.min(100, Math.round((value / total) * 100));
  }

  scoreBg(label: string): string {
    const map: Record<string, string> = {
      'VERY HIGH': 'success', 'HIGH': 'primary', 'MODERATE': 'warning', 'LOW': 'neutral',
    };
    return map[label] ?? 'neutral';
  }

  trendIcon(state: string): string {
    const map: Record<string, string> = {
      spike: 'bolt', rapidly_rising: 'trending_up', rising: 'arrow_upward',
      emerging: 'show_chart', stable: 'remove', declining: 'arrow_downward',
    };
    return map[state] ?? 'radio_button_unchecked';
  }

  growthBadge(growth: number): 'success' | 'warning' | 'danger' | 'neutral' {
    if (growth >= 100) return 'danger';
    if (growth >= 50)  return 'warning';
    if (growth >= 20)  return 'success';
    return 'neutral';
  }
}
