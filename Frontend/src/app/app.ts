import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

/**
 * Root application shell.
 * Routing does all the heavy lifting — this component just provides
 * the <router-outlet> entry point. The Shell layout component wraps
 * all authenticated routes; public auth pages render without it.
 */
@Component({
  selector: 'app-root',
  imports: [RouterOutlet],
  template: `<router-outlet />`,
  styles: [`
    :host { display: block; }
  `],
})
export class App {}
