import { Component, output, signal, computed } from '@angular/core';
import { Router, RouterLink, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';
import { AuthService } from '../../core/auth/auth.service';
import { NotificationService } from '../../core/services/notification.service';

@Component({
  selector: 'app-header',
  imports: [],
  templateUrl: './header.html',
  styleUrl: './header.scss',
})
export class Header {
  toggleSidebar = output<void>();
  userMenuOpen  = signal(false);
  pageTitle     = signal('Dashboard');

  private readonly titles: Record<string, string> = {
    '/dashboard':    'Dashboard',
    '/projects':     'Projects',
    '/keywords':     'Keywords',
    '/trends':       'Trends',
    '/opportunities':'Opportunities',
    '/market-gaps':  'Market Gaps',
    '/competitors':  'Competitors',
    '/landing-pages':'Landing Pages',
    '/campaigns':    'Campaigns',
    '/leads':        'Leads',
    '/crm':          'CRM Pipeline',
    '/alerts':       'Alerts',
    '/reports':      'Reports',
    '/integrations': 'Integrations',
    '/team':         'Team',
    '/billing':      'Billing',
  };

  initials = computed(() => {
    const name = this.auth.user()?.name ?? '';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
  });

  constructor(
    public auth: AuthService,
    private router: Router,
    private notify: NotificationService,
  ) {
    this.router.events
      .pipe(filter((e): e is NavigationEnd => e instanceof NavigationEnd))
      .subscribe((e) => {
        const base = '/' + (e.urlAfterRedirects.split('/')[1] ?? '');
        this.pageTitle.set(this.titles[base] ?? 'DemandLead');
      });
  }

  logout(): void {
    this.auth.logout().subscribe({
      next:  () => { this.notify.success('Signed out.'); this.router.navigate(['/login']); },
      error: () => this.auth.logoutLocal(),
    });
    this.userMenuOpen.set(false);
  }
}
