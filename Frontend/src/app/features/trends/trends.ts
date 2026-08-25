import { Component, OnInit, signal, computed } from '@angular/core';
import { DecimalPipe, TitleCasePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { ApiService } from '../../core/services/api.service';
import { KeywordMeasurement } from '../../core/models/api.models';import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';

type Period = '7' | '14' | '30' | '90';

@Component({
  selector: 'app-trends',
  imports: [FormsModule, RouterLink, PageHeader, LoadingSpinner, EmptyState, Badge, DecimalPipe, TitleCasePipe],
  templateUrl: './trends.html',
  styleUrl: './trends.scss',
})
export class Trends extends ApiService implements OnInit {
  loading      = signal(true);
  rising       = signal<KeywordMeasurement[]>([]);
  allTrends    = signal<KeywordMeasurement[]>([]);
  period       = signal<Period>('7');
  activeTab    = signal<'rising' | 'all'>('rising');
  searchQ      = signal('');

  periods: { label: string; value: Period }[] = [
    { label: '7 days',  value: '7'  },
    { label: '14 days', value: '14' },
    { label: '30 days', value: '30' },
    { label: '90 days', value: '90' },
  ];

  filteredRising = computed(() => {
    const q = this.searchQ().toLowerCase();
    return q
      ? this.rising().filter(m => m.keyword?.keyword.toLowerCase().includes(q))
      : this.rising();
  });

  filteredAll = computed(() => {
    const q = this.searchQ().toLowerCase();
    return q
      ? this.allTrends().filter(m => m.keyword?.keyword.toLowerCase().includes(q))
      : this.allTrends();
  });

  constructor(http: HttpClient) { super(http); }

  ngOnInit(): void { this.loadAll(); }

  loadAll(): void {
    this.loading.set(true);
    const days = this.period();

    // Load rising + all trends in parallel
    const rising$ = this.get<KeywordMeasurement[]>(`/api/v1/trends/rising?days=${days}`);
    const all$    = this.get<{ data: KeywordMeasurement[] }>(`/api/v1/trends?days=${days}`);

    let risingDone = false;
    let allDone    = false;

    rising$.subscribe({
      next: (r) => {
        this.rising.set(Array.isArray(r) ? r : (r as any).data ?? []);
        risingDone = true;
        if (allDone) this.loading.set(false);
      },
      error: () => { risingDone = true; if (allDone) this.loading.set(false); },
    });

    all$.subscribe({
      next: (r) => {
        this.allTrends.set((r as any).data ?? (Array.isArray(r) ? r : []));
        allDone = true;
        if (risingDone) this.loading.set(false);
      },
      error: () => { allDone = true; if (risingDone) this.loading.set(false); },
    });
  }

  setPeriod(p: Period): void { this.period.set(p); this.loadAll(); }

  stateVariant(state: string): 'success' | 'danger' | 'warning' | 'primary' | 'neutral' {
    const map: Record<string, any> = {
      spike: 'danger', rapidly_rising: 'danger', rising: 'success',
      emerging: 'primary', peak: 'warning', stable: 'neutral', declining: 'neutral',
    };
    return map[state] ?? 'neutral';
  }

  stateIcon(state: string): string {
    const map: Record<string, string> = {
      spike: 'bolt', rapidly_rising: 'trending_up', rising: 'arrow_upward',
      emerging: 'show_chart', peak: 'stacked_line_chart',
      stable: 'remove', declining: 'arrow_downward',
    };
    return map[state] ?? 'radio_button_unchecked';
  }

  growthVariant(growth: number): 'success' | 'warning' | 'danger' | 'neutral' {
    if (growth >= 100) return 'danger';
    if (growth >= 50)  return 'warning';
    if (growth >= 20)  return 'success';
    return 'neutral';
  }

  intentVariant(intent: string): 'success' | 'primary' | 'warning' | 'neutral' {
    const map: Record<string, any> = {
      transactional: 'success', commercial: 'primary', local: 'warning',
    };
    return map[intent] ?? 'neutral';
  }
}
