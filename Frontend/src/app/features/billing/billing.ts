import { Component, OnInit, signal } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { PageHeader } from '../../shared/components/page-header/page-header';
import { LoadingSpinner } from '../../shared/components/loading-spinner/loading-spinner';

@Component({
  selector: 'app-billing',
  imports: [PageHeader, LoadingSpinner],
  template: `
    <app-page-header title="Billing" subtitle="Manage your subscription and usage" />
    @if (loading()) {
      <app-loading-spinner [full]="true" />
    } @else {
      <div class="card" style="padding:32px;max-width:520px;">
        <h3 style="margin:0 0 8px;font-size:17px;font-weight:700;">Current plan</h3>
        <p style="color:var(--color-text-secondary);font-size:14px;">
          Full billing management coming in Sprint 11. Configure your plan via the backend API
          or contact your account manager.
        </p>
      </div>
    }
  `,
})
export class Billing implements OnInit {
  loading = signal(true);
  constructor(private api: ApiService) {}
  ngOnInit(): void {
    this.api['get']('/api/v1/billing').subscribe({ next: () => this.loading.set(false), error: () => this.loading.set(false) });
  }
}
