import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/services/api.service';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { Badge } from '../../shared/components/badge/badge';

interface DemandCluster {
  id: number;
  name: string;
  category?: string;
  intent: string;
  score: number;
  status: string;
  project_name?: string;
  keywords_count: number;
  keywords: { id: number; keyword: string; intent: string; priority: string }[];
}

@Component({
  selector: 'app-market-gaps',
  imports: [FormsModule, DecimalPipe, RouterLink, PageHeader, LoadingSpinner, EmptyState, Badge],
  templateUrl: './market-gaps.html',
  styleUrl: './market-gaps.scss',
})
export class MarketGaps implements OnInit {
  loading   = signal(true);
  reclust   = signal(false);
  gaps      = signal<DemandCluster[]>([]);
  viewMode  = signal<'gaps' | 'clusters'>('gaps');
  clusters  = signal<DemandCluster[]>([]);

  gapCount     = computed(() => this.gaps().length);
  clusterCount = computed(() => this.clusters().length);

  constructor(private api: ApiService) {}

  ngOnInit(): void { this.loadBoth(); }

  loadBoth(): void {
    this.loading.set(true);
    let done = 0;
    const finish = () => { if (++done === 2) this.loading.set(false); };

    this.api['get']<{ data: DemandCluster[] }>('/api/v1/intelligence/market-gaps').subscribe({
      next: (r) => { this.gaps.set(r.data); finish(); },
      error: ()  => finish(),
    });

    this.api['get']<{ data: DemandCluster[] }>('/api/v1/intelligence/clusters').subscribe({
      next: (r) => { this.clusters.set(r.data); finish(); },
      error: ()  => finish(),
    });
  }

  recluster(): void {
    this.reclust.set(true);
    // Re-cluster all projects one by one using available projects
    this.api['post']('/api/v1/intelligence/cluster', {}).subscribe({
      next: () => { this.reclust.set(false); this.loadBoth(); },
      error: () => this.reclust.set(false),
    });
  }

  intentVariant(intent: string): 'success' | 'primary' | 'warning' | 'neutral' {
    const map: Record<string, any> = {
      transactional: 'success', commercial: 'primary', local: 'warning',
    };
    return map[intent] ?? 'neutral';
  }

  scoreColor(score: number): string {
    if (score >= 50) return 'var(--color-success)';
    if (score >= 20) return 'var(--color-warning)';
    return '#94a3b8';
  }
}
