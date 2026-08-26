import { Component, OnInit, signal, computed } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DecimalPipe, DatePipe } from '@angular/common';
import { AdminApiService } from '../services/admin-api.service';
import { AdminAuthService } from '../services/admin-auth.service';
import { AdminLoadingSpinner } from '../shared/admin-loading-spinner';

interface OrgListItem {
  id:              number;
  name:            string;
  slug:            string;
  status:          string;
  country?:        string;
  industry?:       string;
  created_at:      string;
  users_count:     number;
  projects_count:  number;
  plan?:           { name: string; slug: string; monthly_price: number };
  active_subscription?: { status: string; renewal_at: string };
}

interface OrgDetail {
  organization: OrgListItem & { users: any[] };
  stats:        Record<string, number>;
  usage:        { period: string; plan: any; usage: Record<string, any>; at_limit: boolean };
  audit_log:    { id: number; action: string; metadata: any; created_at: string; user?: any }[];
}

interface Paginated { data: OrgListItem[]; current_page: number; last_page: number; total: number; }

@Component({
  selector: 'app-admin-organizations',
  imports: [FormsModule, DecimalPipe, DatePipe, AdminLoadingSpinner],
  templateUrl: './admin-organizations.html',
  styleUrl:    './admin-organizations.scss',
})
export class AdminOrganizations implements OnInit {
  loading     = signal(true);
  actioning   = signal(false);
  page        = signal<Paginated | null>(null);
  activeOrg   = signal<OrgDetail | null>(null);
  drawerOpen  = signal(false);
  search      = signal('');
  statusFilter = signal('');

  suspendReason = signal('');
  confirmSuspend = signal(false);

  items = computed(() => this.page()?.data ?? []);

  constructor(
    private api:  AdminApiService,
    public  auth: AdminAuthService,
  ) {}

  ngOnInit(): void { this.load(); }

  load(pageNum = 1): void {
    this.loading.set(true);
    const params: Record<string, any> = { page: pageNum };
    if (this.search())       params['search'] = this.search();
    if (this.statusFilter()) params['status']  = this.statusFilter();

    this.api.get<Paginated>('/api/admin/organizations', params).subscribe({
      next: (r) => { this.page.set(r); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  applySearch(): void { this.load(1); }

  openOrg(id: number): void {
    this.drawerOpen.set(true);
    this.activeOrg.set(null);
    this.confirmSuspend.set(false);
    this.api.get<OrgDetail>(`/api/admin/organizations/${id}`).subscribe({
      next: (r) => this.activeOrg.set(r),
    });
  }

  closeDrawer(): void { this.drawerOpen.set(false); this.activeOrg.set(null); }

  setStatus(status: 'active' | 'suspended'): void {
    const org = this.activeOrg()?.organization;
    if (!org || this.actioning()) return;
    this.actioning.set(true);

    this.api.patch(`/api/admin/organizations/${org.id}/status`, {
      status,
      reason: this.suspendReason(),
    }).subscribe({
      next: (res: any) => {
        // Update both the drawer and the list
        this.activeOrg.update(d => d ? { ...d, organization: { ...d.organization, status } } : d);
        this.page.update(p => p ? {
          ...p,
          data: p.data.map(o => o.id === org.id ? { ...o, status } : o),
        } : p);
        this.actioning.set(false);
        this.confirmSuspend.set(false);
        this.suspendReason.set('');
      },
      error: () => this.actioning.set(false),
    });
  }

  impersonate(id: number): void {
    if (this.actioning()) return;
    this.actioning.set(true);
    this.api.post<any>(`/api/admin/organizations/${id}/impersonate`, {}).subscribe({
      next: (res) => {
        this.actioning.set(false);
        // Open the tenant app in a new tab with the impersonation token
        const url = `${window.location.origin}/login?impersonate_token=${res.token}`;
        window.open(url, '_blank');
      },
      error: () => this.actioning.set(false),
    });
  }

  usageEntries(usage: Record<string, any>): [string, any][] {
    return Object.entries(usage ?? {}).slice(0, 4);
  }

  metricLabel(key: string): string {
    const map: Record<string, string> = {
      keywords: 'Keywords', projects: 'Projects', users: 'Users',
      leads_per_month: 'Leads/mo', ai_requests: 'AI Requests', alerts: 'Alerts',
    };
    return map[key] ?? key;
  }

  planColor(slug: string): string {
    const map: Record<string, string> = {
      free: '#94a3b8', starter: '#6366f1', growth: '#10b981', enterprise: '#f59e0b',
    };
    return map[slug] ?? '#94a3b8';
  }

  statusClass(status: string): string {
    return status === 'active' ? 'status--active' : 'status--suspended';
  }

  prevPage(): void { const cur = this.page()?.current_page ?? 1; if (cur > 1) this.load(cur - 1); }
  nextPage(): void {
    const p = this.page();
    if (p && p.current_page < p.last_page) this.load(p.current_page + 1);
  }
}
