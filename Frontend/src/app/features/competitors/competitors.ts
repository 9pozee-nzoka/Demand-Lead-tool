import { Component } from '@angular/core';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-competitors',
  imports: [PageHeader, EmptyState],
  template: `
    <app-page-header title="Competitor Intelligence" subtitle="Monitor permitted public competitor signals" />
    <app-empty-state icon="visibility" title="Competitor monitoring coming in Phase 2"
      message="Tracks public landing pages, offers, content changes and visibility. No scraping of private data." />
  `,
})
export class Competitors {}
