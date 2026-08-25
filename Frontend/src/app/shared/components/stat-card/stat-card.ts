import { Component, input } from '@angular/core';

export type StatCardTrend = 'up' | 'down' | 'neutral';

@Component({
  selector: 'app-stat-card',
  imports: [],
  templateUrl: './stat-card.html',
  styleUrl: './stat-card.scss',
})
export class StatCard {
  label   = input.required<string>();
  value   = input.required<string | number>();
  icon    = input<string>('bar_chart');
  trend   = input<StatCardTrend>('neutral');
  change  = input<string>();   // e.g. "+12% vs last month"
  color   = input<'primary' | 'success' | 'warning' | 'danger'>('primary');
  loading = input(false);
}
