import { Component } from '@angular/core';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-market-gaps',
  imports: [PageHeader, EmptyState],
  template: `
    <app-page-header title="Market Gaps" subtitle="Rising demand with low competition or low business visibility" />
    <app-empty-state icon="target" title="Market gap analysis coming soon"
      message="This feature requires Sprint 6 trend engine data. Gaps are detected automatically when demand rises and competition remains low." />
  `,
})
export class MarketGaps {}
