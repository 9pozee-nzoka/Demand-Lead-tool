import { Routes } from '@angular/router';
import { adminAuthGuard } from './guards/admin-auth.guard';
import { AdminShell } from './shell/admin-shell';

export const adminRoutes: Routes = [
  // ── Admin login (public) ─────────────────────────────────────────────────
  {
    path: 'login',
    loadComponent: () => import('./auth/admin-login').then(m => m.AdminLogin),
    title: 'Admin Login — DemandLead',
  },

  // ── Protected admin shell ────────────────────────────────────────────────
  {
    path: '',
    component: AdminShell,
    canActivate: [adminAuthGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      {
        path: 'dashboard',
        loadComponent: () => import('./dashboard/admin-dashboard').then(m => m.AdminDashboard),
        title: 'Dashboard — DemandLead Admin',
      },
      {
        path: 'organizations',
        loadComponent: () => import('./organizations/admin-organizations').then(m => m.AdminOrganizations),
        title: 'Organizations — DemandLead Admin',
      },
      {
        path: 'audit-log',
        loadComponent: () => import('./audit-log/admin-audit-log').then(m => m.AdminAuditLog),
        title: 'Audit Log — DemandLead Admin',
      },
    ],
  },
];
