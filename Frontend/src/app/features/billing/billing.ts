import { Component, OnInit, signal, computed } from '@angular/core';
import { DecimalPipe, CurrencyPipe, DatePipe, KeyValuePipe, LowerCasePipe } from '@angular/common';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/auth/auth.service';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';
import { CurrencyKePipe } from '../../shared/pipes/currency-ke.pipe';

interface UsageMetric {
  current:   number;
  limit:     number;
  unlimited: boolean;
  percent:   number;
  exceeded:  boolean;
  remaining: number | null;
}

interface BillingSnapshot {
  period:   string;
  plan:     { name: string; slug: string; monthly_price: number; features: string[] };
  usage:    Record<string, UsageMetric>;
  at_limit: boolean;
  subscription: {
    id:         number;
    status:     string;
    renewal_at: string;
    plan:       any;
  } | null;
}

interface Plan {
  id:            number;
  name:          string;
  slug:          string;
  monthly_price: number;
  limits:        Record<string, number>;
  features:      string[];
}

const METRIC_META: Record<string, { label: string; icon: string }> = {
  keywords:        { label: 'Active Keywords',   icon: 'manage_search'   },
  projects:        { label: 'Projects',           icon: 'folder_open'     },
  users:           { label: 'Team Members',       icon: 'group'           },
  leads_per_month: { label: 'Leads This Month',   icon: 'person_search'   },
  ai_requests:     { label: 'AI Requests',        icon: 'smart_toy'       },
  alerts:          { label: 'Alerts Sent',        icon: 'notifications'   },
};

@Component({
  selector: 'app-billing',
  imports: [DecimalPipe, DatePipe, KeyValuePipe, LowerCasePipe, PageHeader, LoadingSpinner, Badge, CurrencyKePipe],
  templateUrl: './billing.html',
  styleUrl: './billing.scss',
})
export class Billing implements OnInit {
  loading      = signal(true);
  snapshot     = signal<BillingSnapshot | null>(null);
  plans        = signal<Plan[]>([]);
  metricMeta   = METRIC_META;

  usageEntries = computed(() =>
    Object.entries(this.snapshot()?.usage ?? {})
      .filter(([key]) => METRIC_META[key])
  );

  isOwner = computed(() => this.auth.user()?.role === 'owner');

  constructor(
    private api:  ApiService,
    private auth: AuthService,
  ) {}

  ngOnInit(): void {
    this.api['get']<BillingSnapshot>('/api/v1/billing').subscribe({
      next: (r) => { this.snapshot.set(r); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });

    this.api['get']<Plan[]>('/api/v1/billing/plans').subscribe({
      next: (r) => this.plans.set(r),
    });
  }

  barColor(metric: UsageMetric): string {
    if (metric.exceeded)    return '#ef4444';
    if (metric.percent >= 80) return '#f59e0b';
    return '#6366f1';
  }

  subStatusVariant(status: string): 'success' | 'warning' | 'neutral' {
    return status === 'active' ? 'success' : status === 'trialing' ? 'warning' : 'neutral';
  }

  isCurrentPlan(plan: Plan): boolean {
    return plan.slug === this.snapshot()?.plan.slug;
  }
}
