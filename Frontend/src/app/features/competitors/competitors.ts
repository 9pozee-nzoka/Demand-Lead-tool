import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe, TitleCasePipe } from '@angular/common';
import { ApiService } from '../../core/services/api.service';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';

interface CompetitorSignal {
  keyword: string;
  intent: string;
  project: string;
  competition: number;
  interest: number;
  growth: number;
  geo: string;
  date: string;
  competitor_level: string;
}

@Component({
  selector: 'app-competitors',
  imports: [FormsModule, DecimalPipe, TitleCasePipe, PageHeader, LoadingSpinner, EmptyState, Badge],
  templateUrl: './competitors.html',
  styleUrl: './competitors.scss',
})
export class Competitors implements OnInit {
  loading       = signal(true);
  signals       = signal<CompetitorSignal[]>([]);
  minCompetition = signal(0.5);

  constructor(private api: ApiService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api['get']<{ data: CompetitorSignal[] }>(
      `/api/v1/intelligence/competitors?min_competition=${this.minCompetition()}`
    ).subscribe({
      next: (r) => { this.signals.set(r.data); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  setFilter(v: number): void { this.minCompetition.set(v); this.load(); }

  levelVariant(level: string): 'danger' | 'warning' | 'primary' | 'neutral' {
    const map: Record<string, any> = {
      very_high: 'danger', high: 'warning', medium: 'primary', low: 'neutral',
    };
    return map[level] ?? 'neutral';
  }

  intentVariant(intent: string): 'success' | 'primary' | 'warning' | 'neutral' {
    const map: Record<string, any> = {
      transactional: 'success', commercial: 'primary', local: 'warning',
    };
    return map[intent] ?? 'neutral';
  }

  competitionBar(v: number): number { return Math.round(v * 100); }
}
