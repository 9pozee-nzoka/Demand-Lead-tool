import { Component, OnInit, signal, computed } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DecimalPipe } from '@angular/common';
import { AdminApiService } from '../services/admin-api.service';
import { AdminLoadingSpinner } from '../shared/admin-loading-spinner';

interface Metrics {
  totals: {
    organizations: number;
    active_orgs:   number;
    users:         number;
    keywords:      number;
    opportunities: number;
    leads:         number;
    revenue:       number;
  };
  this_month: {
    new_orgs:  number;
    new_users: number;
    new_leads: number;
    revenue:   number;
  };
  monthly_signups: { month: string; count: number }[];
}

interface RecentOrg {
  id:         number;
  name:       string;
  status:     string;
  plan:       string;
  plan_slug:  string;
  owner:      string;
  country:    string;
  created_at: string;
}

@Component({
  selector: 'app-admin-dashboard',
  imports: [RouterLink, DecimalPipe, AdminLoadingSpinner],
  templateUrl: './admin-dashboard.html',
  styleUrl:    './admin-dashboard.scss',
})
export class AdminDashboard implements OnInit {
  loading       = signal(true);
  metrics       = signal<Metrics | null>(null);
  recentSignups = signal<RecentOrg[]>([]);

  maxMonthly = computed(() =>
    Math.max(...(this.metrics()?.monthly_signups?.map(m => m.count) ?? [1]))
  );

  constructor(private api: AdminApiService) {}

  ngOnInit(): void {
    let done = 0;
    const finish = () => { if (++done === 2) this.loading.set(false); };

    this.api.get<Metrics>('/api/admin/metrics').subscribe({
      next: (r) => { this.metrics.set(r); finish(); },
      error: ()  => finish(),
    });

    this.api.get<RecentOrg[]>('/api/admin/recent-signups').subscribe({
      next: (r) => { this.recentSignups.set(r); finish(); },
      error: ()  => finish(),
    });
  }

  barHeight(count: number): number {
    const max = this.maxMonthly();
    return max > 0 ? Math.round((count / max) * 100) : 0;
  }

  planBadgeColor(slug: string): string {
    const map: Record<string, string> = {
      free:       '#94a3b8',
      starter:    '#6366f1',
      growth:     '#10b981',
      enterprise: '#f59e0b',
    };
    return map[slug] ?? '#94a3b8';
  }

  statusDot(status: string): string {
    return status === 'active' ? '#22c55e' : '#ef4444';
  }
}
