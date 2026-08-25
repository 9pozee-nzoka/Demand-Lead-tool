import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Sidebar } from '../sidebar/sidebar';
import { Header } from '../header/header';
import { Notifications } from '../notifications/notifications';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, Sidebar, Header, Notifications],
  templateUrl: './shell.html',
  styleUrl: './shell.scss',
})
export class Shell {
  sidebarCollapsed  = signal(false);
  mobileSidebarOpen = signal(false);

  toggleSidebar(): void {
    if (window.innerWidth < 768) {
      this.mobileSidebarOpen.update(v => !v);
    } else {
      this.sidebarCollapsed.update(v => !v);
    }
  }
}
