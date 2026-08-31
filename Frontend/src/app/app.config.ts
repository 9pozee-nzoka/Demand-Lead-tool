import {
  ApplicationConfig,
  provideBrowserGlobalErrorListeners,
  provideZonelessChangeDetection,
} from '@angular/core';
import { provideRouter, withComponentInputBinding, withRouterConfig } from '@angular/router';
import { provideHttpClient, withInterceptors, withFetch } from '@angular/common/http';
import { provideAnimationsAsync } from '@angular/platform-browser/animations/async';
import { routes } from './app.routes';
import { apiBaseInterceptor } from './core/interceptors/api-base.interceptor';
import { authInterceptor } from './core/interceptors/auth.interceptor';
import { errorInterceptor } from './core/interceptors/error.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    // Error listener
    provideBrowserGlobalErrorListeners(),

    // Zoneless change detection — Angular 22 stable API (no zone.js needed)
    provideZonelessChangeDetection(),

    // Router — component input binding lets route params bind directly to @Input()
    provideRouter(
      routes,
      withComponentInputBinding(),
      withRouterConfig({ onSameUrlNavigation: 'reload' }),
    ),

    // HttpClient with functional interceptors
    provideHttpClient(
      withFetch(),
      withInterceptors([apiBaseInterceptor, authInterceptor, errorInterceptor]),
    ),

    // Angular Material animations (lazy-loaded)
    provideAnimationsAsync(),
  ],
};
