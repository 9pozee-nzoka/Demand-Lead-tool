import { Component, computed, inject, input } from '@angular/core';
import { RouterLink, ActivatedRoute } from '@angular/router';

export type ErrorCode = 401 | 403 | 404 | 429 | 500;

interface ErrorConfig {
  icon:    string;
  title:   string;
  message: string;
  action:  string;
  route:   string;
}

const CONFIGS: Record<ErrorCode, ErrorConfig> = {
  401: {
    icon:    'lock',
    title:   'Session expired',
    message: 'Your session has expired or you are not signed in. Please log in to continue.',
    action:  'Sign in',
    route:   '/login',
  },
  403: {
    icon:    'block',
    title:   'Access denied',
    message: 'You do not have permission to view this page. Contact your administrator if you believe this is a mistake.',
    action:  'Go to dashboard',
    route:   '/dashboard',
  },
  404: {
    icon:    'search_off',
    title:   'Page not found',
    message: 'The page you are looking for does not exist or has been moved.',
    action:  'Go to dashboard',
    route:   '/dashboard',
  },
  429: {
    icon:    'hourglass_bottom',
    title:   'Too many requests',
    message: 'You have made too many requests in a short time. Please wait a moment and try again.',
    action:  'Go to dashboard',
    route:   '/dashboard',
  },
  500: {
    icon:    'cloud_off',
    title:   'Server error',
    message: 'Something went wrong on our end. The issue has been logged and our team will investigate.',
    action:  'Try again',
    route:   '/dashboard',
  },
};

@Component({
  selector: 'app-error-page',
  imports: [RouterLink],
  template: `
    <div class="error-page">
      <div class="error-page__inner">
        <div class="error-page__icon-wrap">
          <span class="material-symbols-rounded error-page__icon">{{ config().icon }}</span>
        </div>
        <div class="error-page__code">{{ resolvedCode() }}</div>
        <h1 class="error-page__title">{{ config().title }}</h1>
        <p class="error-page__message">{{ config().message }}</p>
        <a [routerLink]="config().route" class="btn btn--primary error-page__cta">
          {{ config().action }}
        </a>
      </div>
    </div>
  `,
  styles: [`
    .error-page {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      padding: 40px 24px;

      &__inner {
        max-width: 420px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
      }

      &__icon-wrap {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: var(--color-primary-light);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 4px;
      }

      &__icon {
        font-size: 36px;
        color: var(--color-primary);
        font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 48;
      }

      &__code {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--color-text-secondary);
      }

      &__title {
        font-size: 26px;
        font-weight: 800;
        color: var(--color-text);
        margin: 0;
        line-height: 1.2;
      }

      &__message {
        font-size: 15px;
        color: var(--color-text-secondary);
        line-height: 1.6;
        margin: 0;
      }

      &__cta { margin-top: 8px; }
    }
  `],
})
export class ErrorPage {
  /** Can be passed directly via [code] binding or resolved from route data. */
  code = input<ErrorCode | null>(null);

  private readonly route = inject(ActivatedRoute);

  resolvedCode = computed<ErrorCode>(() => {
    return this.code()
      ?? (this.route.snapshot.data['code'] as ErrorCode)
      ?? 404;
  });

  config = computed(() => CONFIGS[this.resolvedCode()] ?? CONFIGS[404]);
}
