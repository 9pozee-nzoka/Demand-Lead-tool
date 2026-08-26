import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { ApiService } from '../../core/services/api.service';
import { NotificationService } from '../../core/services/notification.service';
import { CurrencyKePipe } from '../../shared/pipes/currency-ke.pipe';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { StatCard } from '../../shared/components/stat-card/stat-card';

interface RevenueSummary {
  period: { from: string; to: string };
  total_revenue: number;
  won_deals: number;
  avg_deal_value: number;
  monthly: { month: string; revenue: number; deals: number }[];
}

interface FunnelReport {
  period: { from: string; to: string };
  opportunities: number;
  leads: number;
  qualified: number;
  deals: number;
  won_deals: number;
  revenue: number;
  conversion_rate: number;
  qualification_rate: number;
}

@Component({
  selector: 'app-reports',
  imports: [FormsModule, DecimalPipe, CurrencyKePipe, PageHeader, LoadingSpinner, StatCard],
  templateUrl: './reports.html',
  styleUrl: './reports.scss',
})
export class Reports implements OnInit {
  loading     = signal(true);
  downloading = signal<string | null>(null);
  revenue     = signal<RevenueSummary | null>(null);
  funnel      = signal<FunnelReport | null>(null);

  // Date range
  fromDate = signal(this.defaultFrom());
  toDate   = signal(new Date().toISOString().split('T')[0]);

  // Bar chart max for monthly revenue
  maxMonthlyRevenue = computed(() =>
    Math.max(...(this.revenue()?.monthly?.map(m => m.revenue) ?? [0]))
  );

  constructor(
    private api: ApiService,
    private notify: NotificationService,
  ) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    const params = `from=${this.fromDate()}&to=${this.toDate()}`;
    let done = 0;
    const finish = () => { if (++done === 2) this.loading.set(false); };

    this.api['get']<RevenueSummary>(`/api/v1/reports/revenue-summary?${params}`).subscribe({
      next: (r) => { this.revenue.set(r); finish(); },
      error: ()  => finish(),
    });

    this.api['get']<FunnelReport>(`/api/v1/reports/funnel?${params}`).subscribe({
      next: (r) => { this.funnel.set(r); finish(); },
      error: ()  => finish(),
    });
  }

  applyRange(): void { this.load(); }

  download(type: string): void {
    this.downloading.set(type);
    const params = `from=${this.fromDate()}&to=${this.toDate()}`;
    const url    = `/api/v1/reports/export/${type}.csv?${params}`;

    // Trigger browser download by fetching with auth token then creating a blob
    this.api['getBlob'](url).subscribe({
      next: (blob: Blob) => {
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = `${type}-${this.fromDate()}-${this.toDate()}.csv`;
        a.click();
        URL.revokeObjectURL(a.href);
        this.downloading.set(null);
        this.notify.success(`${type} export downloaded.`);
      },
      error: () => {
        this.downloading.set(null);
        this.notify.error('Export failed. Please try again.');
      },
    });
  }

  barWidth(value: number): number {
    const max = this.maxMonthlyRevenue();
    return max > 0 ? Math.round((value / max) * 100) : 0;
  }

  funnelPercent(value: number, base: number): number {
    return base > 0 ? Math.min(100, Math.round((value / base) * 100)) : 0;
  }

  private defaultFrom(): string {
    const d = new Date();
    d.setDate(d.getDate() - 30);
    return d.toISOString().split('T')[0];
  }
}
