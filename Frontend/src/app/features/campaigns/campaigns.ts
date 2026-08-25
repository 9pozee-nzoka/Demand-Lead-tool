import { Component } from '@angular/core';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { EmptyState } from '../../shared/components/empty-state/empty-state';

@Component({
  selector: 'app-campaigns',
  imports: [PageHeader, EmptyState],
  template: `
    <app-page-header title="Campaigns" subtitle="Campaign recommendations tied to demand opportunities" />
    <app-empty-state icon="campaign" title="No campaigns yet"
      message="The platform recommends campaigns from opportunities. It never automatically spends advertising budget." />
  `,
})
export class Campaigns {}
