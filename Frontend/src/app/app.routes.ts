import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { Shell } from './layout/shell/shell';
import { ErrorPage } from './shared/components/error-page/error-page';

export const routes: Routes = [

  // ── Super-admin panel (completely separate — lazy-loaded) ───────────────
  {
    path: 'admin',
    loadChildren: () => import('./admin/admin.routes').then(m => m.adminRoutes),
  },

  // ── Public auth routes (no shell) ──────────────────────────────────────
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then(m => m.Login),
    title: 'Sign in — DemandLead',
  },
  {
    path: 'register',
    loadComponent: () => import('./features/auth/register/register').then(m => m.Register),
    title: 'Create account — DemandLead',
  },
  {
    path: 'forgot-password',
    loadComponent: () => import('./features/auth/forgot-password/forgot-password').then(m => m.ForgotPassword),
    title: 'Reset password — DemandLead',
  },

  // ── Public landing page capture (no shell, no auth) ─────────────────────
  {
    path: 'capture/:slug',
    loadComponent: () => import('./features/capture/capture-page').then(m => m.CapturePage),
    // Title set dynamically from page data in the component
  },

  // ── Authenticated routes (inside shell) ────────────────────────────────
  {
    path: '',
    component: Shell,
    canActivate: [authGuard],
    children: [

      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },

      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard').then(m => m.Dashboard),
        title: 'Dashboard — DemandLead',
      },

      // Projects
      {
        path: 'projects',
        loadComponent: () => import('./features/projects/projects').then(m => m.Projects),
        title: 'Projects — DemandLead',
      },

      // Keywords
      {
        path: 'keywords',
        loadComponent: () => import('./features/keywords/keywords').then(m => m.Keywords),
        title: 'Keywords — DemandLead',
      },

      // Trends
      {
        path: 'trends',
        loadComponent: () => import('./features/trends/trends').then(m => m.Trends),
        title: 'Trends — DemandLead',
      },

      // Opportunities
      {
        path: 'opportunities',
        loadComponent: () => import('./features/opportunities/opportunities').then(m => m.Opportunities),
        title: 'Opportunities — DemandLead',
      },

      // Market Gaps
      {
        path: 'market-gaps',
        loadComponent: () => import('./features/market-gaps/market-gaps').then(m => m.MarketGaps),
        title: 'Market Gaps — DemandLead',
      },

      // Competitors
      {
        path: 'competitors',
        loadComponent: () => import('./features/competitors/competitors').then(m => m.Competitors),
        title: 'Competitors — DemandLead',
      },

      // Landing Pages
      {
        path: 'landing-pages',
        loadComponent: () => import('./features/landing-pages/landing-pages').then(m => m.LandingPages),
        title: 'Landing Pages — DemandLead',
      },

      // Campaigns
      {
        path: 'campaigns',
        loadComponent: () => import('./features/campaigns/campaigns').then(m => m.Campaigns),
        title: 'Campaigns — DemandLead',
      },

      // Leads
      {
        path: 'leads',
        loadComponent: () => import('./features/leads/leads').then(m => m.Leads),
        title: 'Leads — DemandLead',
      },

      // CRM
      {
        path: 'crm',
        loadComponent: () => import('./features/crm/crm').then(m => m.Crm),
        title: 'CRM — DemandLead',
      },

      // Alerts
      {
        path: 'alerts',
        loadComponent: () => import('./features/alerts/alerts').then(m => m.AlertsPage),
        title: 'Alerts — DemandLead',
      },

      // Reports
      {
        path: 'reports',
        loadComponent: () => import('./features/reports/reports').then(m => m.Reports),
        title: 'Reports — DemandLead',
      },

      // Integrations
      {
        path: 'integrations',
        loadComponent: () => import('./features/integrations/integrations').then(m => m.Integrations),
        title: 'Integrations — DemandLead',
      },

      // Team — admin/owner only
      {
        path: 'team',
        canActivate: [roleGuard],
        data: { roles: ['owner', 'admin'] },
        loadComponent: () => import('./features/team/team').then(m => m.Team),
        title: 'Team — DemandLead',
      },

      // Billing — owner only
      {
        path: 'billing',
        canActivate: [roleGuard],
        data: { roles: ['owner'] },
        loadComponent: () => import('./features/billing/billing').then(m => m.Billing),
        title: 'Billing — DemandLead',
      },

    ],
  },

  // ── Error pages ────────────────────────────────────────────────────────
  {
    path: '403',
    component: ErrorPage,
    data: { code: 403 },
    title: 'Access denied — DemandLead',
  },
  {
    path: '404',
    component: ErrorPage,
    data: { code: 404 },
    title: 'Not found — DemandLead',
  },
  {
    path: '500',
    component: ErrorPage,
    data: { code: 500 },
    title: 'Server error — DemandLead',
  },

  // ── Fallback ───────────────────────────────────────────────────────────
  { path: '**', component: ErrorPage, data: { code: 404 }, title: 'Not found — DemandLead' },
];
