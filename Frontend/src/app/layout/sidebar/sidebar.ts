import { Component, computed, input, output } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../core/auth/auth.service';

interface NavItem {
  label: string;
  icon: string;
  route: string;
  roles?: string[];
  section?: string;
}

const NAV: NavItem[] = [
  { label: 'Dashboard',     icon: 'grid_view',       route: '/dashboard',     section: 'main' },
  { label: 'Projects',      icon: 'folder_open',     route: '/projects',      section: 'demand' },
  { label: 'Keywords',      icon: 'manage_search',   route: '/keywords',      section: 'demand' },
  { label: 'Trends',        icon: 'trending_up',     route: '/trends',        section: 'demand' },
  { label: 'Opportunities', icon: 'bolt',            route: '/opportunities', section: 'demand' },
  { label: 'Market Gaps',   icon: 'track_changes',   route: '/market-gaps',   section: 'demand' },
  { label: 'Competitors',   icon: 'visibility',      route: '/competitors',   section: 'demand' },
  { label: 'Landing Pages', icon: 'web',             route: '/landing-pages', section: 'convert' },
  { label: 'Campaigns',     icon: 'campaign',        route: '/campaigns',     section: 'convert' },
  { label: 'Leads',         icon: 'person_search',   route: '/leads',         section: 'convert' },
  { label: 'CRM',           icon: 'business_center', route: '/crm',           section: 'convert' },
  { label: 'Alerts',        icon: 'notifications',   route: '/alerts',        section: 'convert' },
  { label: 'Reports',       icon: 'bar_chart',       route: '/reports',       section: 'system' },
  { label: 'Integrations',  icon: 'extension',       route: '/integrations',  section: 'system' },
  { label: 'Team',          icon: 'group',           route: '/team',          section: 'system', roles: ['owner','admin'] },
  { label: 'Billing',       icon: 'credit_card',     route: '/billing',       section: 'system', roles: ['owner'] },
];

const SECTIONS = [
  { key: 'main',    label: '' },
  { key: 'demand',  label: 'Intelligence' },
  { key: 'convert', label: 'Convert' },
  { key: 'system',  label: 'Settings' },
];

@Component({
  selector: 'app-sidebar',
  imports: [RouterLink, RouterLinkActive],
  templateUrl: './sidebar.html',
  styleUrl: './sidebar.scss',
})
export class Sidebar {
  collapsed   = input(false);
  closeMobile = output<void>();

  sections = SECTIONS;

  constructor(public auth: AuthService) {}

  itemsForSection = (key: string) => computed(() =>
    NAV.filter(i => i.section === key && (!i.roles || this.auth.hasRole(...i.roles)))
  );

  onLinkClick(): void { this.closeMobile.emit(); }

  initials = computed(() => {
    const name = this.auth.user()?.name ?? '';
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
  });
}
