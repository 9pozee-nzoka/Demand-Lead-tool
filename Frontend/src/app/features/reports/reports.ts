import { Component } from '@angular/core';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-reports',
  imports: [PageHeader, EmptyState],
  template: `
    <app-page-header title="Reports" subtitle="Scheduled and on-demand reports for your organisation" />
    <app-empty-state icon="bar_chart_4_bars" title="Reports coming in Sprint 12"
      message="ROI attribution, lead outcome reports, demand forecasting and revenue dashboards." />
  `,
})
export class Reports {}
