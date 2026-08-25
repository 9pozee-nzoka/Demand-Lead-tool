import { Component } from '@angular/core';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-integrations',
  imports: [PageHeader, EmptyState],
  template: `
    <app-page-header title="Integrations" subtitle="Connect permitted data sources and external platforms" />
    <app-empty-state icon="extension" title="No integrations configured"
      message="Connect Google Search Console, Google Trends, or customer analytics to start collecting demand signals." />
  `,
})
export class Integrations {}
