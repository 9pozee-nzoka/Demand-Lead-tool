import { Component, OnInit, signal } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { AdminAuthService } from '../services/admin-auth.service';
import { AdminApiService } from '../services/admin-api.service';

const NAV = [
  { label: 'Dashboard',     icon: 'dashboard',          route: '/admin/dashboard' },
  { label: 'Organizations', icon: 'business',            route: '/admin/organizations' },
  { label: 'Audit Log',     icon: 'manage_search',       route: '/admin/audit-log' },
];

@Component({
  selector: 'app-admin-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <div class="admin-shell">
      <!-- ── Sidebar ── -->
      <aside class="admin-sidebar">
        <div class="admin-sidebar__brand">
          <span class="material-symbols-rounded admin-sidebar__logo">admin_panel_settings</span>
          <div>
            <div class="admin-sidebar__name">DemandLead</div>
            <div class="admin-sidebar__tag">Admin Panel</div>
          </div>
        </div>

        <nav class="admin-sidebar__nav">
          @for (item of nav; track item.route) {
            <a [routerLink]="item.route" routerLinkActive="admin-sidebar__item--active"
              class="admin-sidebar__item">
              <span class="material-symbols-rounded">{{ item.icon }}</span>
              {{ item.label }}
            </a>
          }
        </nav>

        <div class="admin-sidebar__footer">
          <div class="admin-sidebar__avatar">{{ auth.initials() }}</div>
          <div class="admin-sidebar__user-info">
            <span class="admin-sidebar__user-name">{{ auth.user()?.name }}</span>
            <span class="admin-sidebar__user-badge">Super Admin</span>
          </div>
          <button class="admin-sidebar__logout" (click)="auth.logout()" title="Sign out">
            <span class="material-symbols-rounded">logout</span>
          </button>
        </div>
      </aside>

      <!-- ── Main ── -->
      <main class="admin-main">
        <router-outlet />
      </main>
    </div>
  `,
  styles: [`
    .admin-shell {
      display: flex; min-height: 100vh;
      font-family: 'Inter', system-ui, sans-serif;
      background: #f8fafc;
    }

    /* ── Sidebar ── */
    .admin-sidebar {
      width: 220px; background: #0f172a; display: flex; flex-direction: column;
      position: sticky; top: 0; height: 100vh; flex-shrink: 0; overflow: hidden;
    }
    .admin-sidebar__brand {
      display: flex; align-items: center; gap: 10px; padding: 18px 16px;
      border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .admin-sidebar__logo {
      font-size: 24px; color: #818cf8;
      font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 48;
      flex-shrink: 0;
    }
    .admin-sidebar__name { font-size: 14px; font-weight: 800; color: #f1f5f9; }
    .admin-sidebar__tag  { font-size: 10px; font-weight: 600; color: #6366f1; text-transform: uppercase; letter-spacing: 0.7px; }

    .admin-sidebar__nav {
      flex: 1; padding: 12px 8px; display: flex; flex-direction: column; gap: 2px; overflow-y: auto;
    }
    .admin-sidebar__item {
      display: flex; align-items: center; gap: 10px; padding: 9px 12px;
      border-radius: 8px; color: #94a3b8; text-decoration: none;
      font-size: 13.5px; font-weight: 500; transition: background .15s, color .15s;
      .material-symbols-rounded { font-size: 19px; }
      &:hover { background: rgba(255,255,255,.06); color: #e2e8f0; }
      &--active {
        background: rgba(99,102,241,.2); color: #a5b4fc;
        .material-symbols-rounded { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
      }
    }

    .admin-sidebar__footer {
      display: flex; align-items: center; gap: 10px; padding: 14px 16px;
      border-top: 1px solid rgba(255,255,255,.06);
    }
    .admin-sidebar__avatar {
      width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff; font-size: 11px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
    }
    .admin-sidebar__user-info { flex: 1; display: flex; flex-direction: column; gap: 1px; min-width: 0; }
    .admin-sidebar__user-name { font-size: 12.5px; font-weight: 600; color: #e2e8f0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .admin-sidebar__user-badge { font-size: 10px; font-weight: 600; color: #6366f1; text-transform: uppercase; letter-spacing: 0.5px; }
    .admin-sidebar__logout {
      background: none; border: none; cursor: pointer; color: #475569; padding: 4px;
      display: flex; border-radius: 6px; transition: background .15s, color .15s;
      .material-symbols-rounded { font-size: 18px; }
      &:hover { background: rgba(255,255,255,.08); color: #94a3b8; }
    }

    /* ── Main content ── */
    .admin-main {
      flex: 1; min-width: 0; overflow-y: auto; padding: 28px;
      @media (max-width: 768px) { padding: 16px; }
    }
  `],
})
export class AdminShell implements OnInit {
  nav = NAV;

  constructor(
    public  auth: AdminAuthService,
    private api:  AdminApiService,
  ) {}

  ngOnInit(): void {
    if (!this.auth.user()) {
      this.auth.loadCurrentUser(this.api).subscribe({
        error: () => this.auth.logout(),
      });
    }
  }
}
