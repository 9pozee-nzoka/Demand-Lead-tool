import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe, TitleCasePipe, DatePipe } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { OpportunityService } from '../../core/services/opportunity.service';
import { NotificationService } from '../../core/services/notification.service';
import { Opportunity, OpportunityStatus, Paginated } from '../../core/models/api.models';import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';
import { Badge } from '../../shared/components/badge/badge';

type FilterValue = 'all' | OpportunityStatus;
type ActionType  = 'create_landing_page' | 'create_campaign' | 'send_alert' | 'notify_sales';

@Component({
  selector: 'app-opportunities',
  imports: [FormsModule, PageHeader, EmptyState, LoadingSpinner, Badge,
            DecimalPipe, TitleCasePipe, DatePipe],
  templateUrl: './opportunities.html',
  styleUrl: './opportunities.scss',
})
export class Opportunities implements OnInit {
  loading     = signal(true);
  acting      = signal(false);
  page        = signal<Paginated<Opportunity> | null>(null);
  filter      = signal<FilterValue>('all');
  minScore    = signal(0);
  actionOpp   = signal<Opportunity | null>(null); // which opp has action modal open
  expandedId  = signal<number | null>(null);

  items = computed(() => this.page()?.data ?? []);

  readonly actions: { value: ActionType; label: string; icon: string; desc: string }[] = [
    { value: 'create_landing_page', label: 'Create Landing Page', icon: 'web',
      desc: 'Generate an AI-powered landing page targeting this keyword and location.' },
    { value: 'create_campaign',     label: 'Create Campaign',     icon: 'campaign',
      desc: 'Set up a new marketing campaign for this demand opportunity.' },
    { value: 'send_alert',          label: 'Send Alert',          icon: 'notifications',
      desc: 'Fire an immediate alert to your configured channels.' },
    { value: 'notify_sales',        label: 'Notify Sales Team',   icon: 'group',
      desc: 'Notify your sales team about this high-value opportunity.' },
  ];

  constructor(
    private oppService: OpportunityService,
    private notify: NotificationService,
    private route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    const minScore = this.route.snapshot.queryParamMap.get('min_score');
    if (minScore) this.minScore.set(+minScore);
    this.load();
  }

  load(): void {
    this.loading.set(true);
    const status   = this.filter() !== 'all' ? (this.filter() as OpportunityStatus) : undefined;
    const minScore = this.minScore() > 0 ? this.minScore() : undefined;
    this.oppService.getAll({ status, min_score: minScore }).subscribe({
      next: (p) => { this.page.set(p); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  setFilter(f: string): void { this.filter.set(f as FilterValue); this.load(); }

  setMinScore(score: number): void { this.minScore.set(score); this.load(); }

  toggleExpand(id: number): void {
    this.expandedId.update(cur => cur === id ? null : id);
  }

  dismiss(id: number, event: Event): void {
    event.stopPropagation();
    this.oppService.dismiss(id).subscribe({
      next: () => {
        this.page.update(p => p ? { ...p, data: p.data.filter(o => o.id !== id) } : p);
        this.notify.info('Opportunity dismissed.');
      },
    });
  }

  openAction(opp: Opportunity, event: Event): void {
    event.stopPropagation();
    this.actionOpp.set(opp);
  }

  runAction(type: ActionType): void {
    const opp = this.actionOpp();
    if (!opp || this.acting()) return;
    this.acting.set(true);
    this.oppService.action(opp.id, type).subscribe({
      next: (res) => {
        this.page.update(p => p
          ? { ...p, data: p.data.map(o => o.id === opp.id ? res.opportunity : o) }
          : p);
        this.notify.success(`Action "${type.replace(/_/g,' ')}" queued.`);
        this.actionOpp.set(null);
        this.acting.set(false);
      },
      error: () => this.acting.set(false),
    });
  }

  scoreColor(score: number): 'success' | 'primary' | 'warning' | 'neutral' {
    if (score >= 80) return 'success';
    if (score >= 60) return 'primary';
    if (score >= 40) return 'warning';
    return 'neutral';
  }

  trendIcon(state: string): string {
    const map: Record<string, string> = {
      spike: 'bolt', rapidly_rising: 'trending_up', rising: 'arrow_upward',
      emerging: 'show_chart', stable: 'remove', declining: 'arrow_downward',
    };
    return map[state] ?? 'help';
  }

  statusVariant(status: string): 'warning' | 'success' | 'neutral' {
    if (status === 'detected') return 'warning';
    if (status === 'actioned') return 'success';
    return 'neutral';
  }
}
