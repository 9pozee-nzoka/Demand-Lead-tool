import { Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DatePipe, JsonPipe } from '@angular/common';
import { AdminApiService } from '../services/admin-api.service';
import { AdminLoadingSpinner } from '../shared/admin-loading-spinner';

interface AuditEntry {
  id:              number;
  action:          string;
  metadata?:       Record<string, any>;
  ip_address?:     string;
  created_at:      string;
  user?:           { id: number; name: string; email: string };
  organization?:   { id: number; name: string };
}

interface Paginated { data: AuditEntry[]; current_page: number; last_page: number; total: number; }

@Component({
  selector: 'app-admin-audit-log',
  imports: [FormsModule, DatePipe, JsonPipe, AdminLoadingSpinner],
  template: `
    <div class="admin-page-header">
      <div>
        <h1 class="admin-page-title">Audit Log</h1>
        <p class="admin-page-sub">All system actions across all organisations</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="audit-toolbar">
      <input [ngModel]="searchAction()" (ngModelChange)="searchAction.set($event)"
        (keydown.enter)="load()" placeholder="Filter by action…" class="audit-search" />
      <button class="refresh-btn" (click)="load()">
        <span class="material-symbols-rounded">refresh</span>
      </button>
    </div>

    @if (loading()) {
      <app-admin-loading-spinner />
    } @else {
      <div class="audit-table-wrap">
        <table class="audit-table">
          <thead>
            <tr>
              <th>Action</th>
              <th>User</th>
              <th>Organisation</th>
              <th>IP</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            @for (entry of page()?.data ?? []; track entry.id) {
              <tr (click)="toggleMeta(entry.id)">
                <td>
                  <span class="action-tag" [class]="actionClass(entry.action)">
                    {{ entry.action }}
                  </span>
                </td>
                <td class="cell-user">
                  @if (entry.user) {
                    <div class="cell-user__name">{{ entry.user.name }}</div>
                    <div class="cell-user__email">{{ entry.user.email }}</div>
                  } @else { <span class="text-muted">System</span> }
                </td>
                <td class="text-muted">{{ entry.organization?.name ?? '—' }}</td>
                <td class="text-mono">{{ entry.ip_address ?? '—' }}</td>
                <td class="text-muted">{{ entry.created_at | date:'MMM d, HH:mm:ss' }}</td>
              </tr>
              @if (expandedId() === entry.id && entry.metadata) {
                <tr class="meta-row">
                  <td colspan="5">
                    <pre class="meta-json">{{ entry.metadata | json }}</pre>
                  </td>
                </tr>
              }
            }
          </tbody>
        </table>
      </div>

      @if ((page()?.last_page ?? 1) > 1) {
        <div class="pag-row">
          <button class="pag-btn" [disabled]="page()!.current_page <= 1" (click)="prev()">
            <span class="material-symbols-rounded">chevron_left</span>
          </button>
          <span>{{ page()!.current_page }} / {{ page()!.last_page }}</span>
          <button class="pag-btn" [disabled]="page()!.current_page >= page()!.last_page" (click)="next()">
            <span class="material-symbols-rounded">chevron_right</span>
          </button>
        </div>
      }
    }
  `,
  styles: [`
    .admin-page-header { margin-bottom: 20px; }
    .admin-page-title  { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 3px; }
    .admin-page-sub    { font-size: 13.5px; color: #64748b; margin: 0; }
    .audit-toolbar     { display: flex; gap: 10px; margin-bottom: 16px; }
    .audit-search {
      flex: 1; padding: 8px 14px; border: 1px solid #e2e8f0; border-radius: 8px;
      font-size: 13.5px; font-family: inherit; outline: none;
      &:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.15); }
    }
    .refresh-btn {
      width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px;
      background: #fff; display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: #64748b;
      &:hover { background: #f1f5f9; }
      .material-symbols-rounded { font-size: 19px; }
    }
    .audit-table-wrap { overflow-x: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; }
    .audit-table {
      width: 100%; border-collapse: collapse; font-size: 13px;
      th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; border-bottom: 1px solid #e2e8f0; background: #f8fafc; white-space: nowrap; }
      td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
      tbody tr { cursor: pointer; transition: background .1s; &:hover { background: #f8fafc; } }
    }
    .action-tag {
      font-family: monospace; font-size: 12px; font-weight: 600; padding: 2px 8px;
      border-radius: 5px; background: #f1f5f9; color: #374151;
      &--admin   { background: #fef2f2; color: #dc2626; }
      &--user    { background: #eff6ff; color: #2563eb; }
      &--org     { background: #faf5ff; color: #9333ea; }
      &--deal    { background: #f0fdf4; color: #16a34a; }
      &--integration { background: #fffbeb; color: #d97706; }
    }
    .cell-user { display: flex; flex-direction: column; gap: 1px; }
    .cell-user__name  { font-weight: 600; color: #0f172a; }
    .cell-user__email { font-size: 11.5px; color: #94a3b8; }
    .text-muted { font-size: 12.5px; color: #94a3b8; }
    .text-mono  { font-family: monospace; font-size: 12px; color: #64748b; }
    .meta-row td { background: #0f172a; padding: 0; }
    .meta-json {
      margin: 0; padding: 12px 16px; font-size: 11.5px; color: #a5f3fc;
      font-family: monospace; white-space: pre-wrap; word-break: break-all;
    }
    .pag-row { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 14px; font-size: 13px; color: #64748b; }
    .pag-btn { width: 30px; height: 30px; border: 1px solid #e2e8f0; border-radius: 7px; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; &:disabled { opacity: .4; cursor: not-allowed; } .material-symbols-rounded { font-size: 17px; } }
  `],
})
export class AdminAuditLog implements OnInit {
  loading      = signal(true);
  page         = signal<Paginated | null>(null);
  searchAction = signal('');
  expandedId   = signal<number | null>(null);

  constructor(private api: AdminApiService) {}

  ngOnInit(): void { this.load(); }

  load(pageNum = 1): void {
    this.loading.set(true);
    const params: Record<string, any> = { page: pageNum };
    if (this.searchAction()) params['action'] = this.searchAction();

    this.api.get<Paginated>('/api/admin/audit-log', params).subscribe({
      next: (r) => { this.page.set(r); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  toggleMeta(id: number): void {
    this.expandedId.update(cur => cur === id ? null : id);
  }

  prev(): void { const c = this.page()?.current_page ?? 1; if (c > 1) this.load(c - 1); }
  next(): void {
    const p = this.page();
    if (p && p.current_page < p.last_page) this.load(p.current_page + 1);
  }

  actionClass(action: string): string {
    if (action.startsWith('admin.'))       return 'action-tag--admin';
    if (action.startsWith('user.'))        return 'action-tag--user';
    if (action.startsWith('organization')) return 'action-tag--org';
    if (action.startsWith('deal.'))        return 'action-tag--deal';
    if (action.startsWith('integration.')) return 'action-tag--integration';
    return '';
  }
}
