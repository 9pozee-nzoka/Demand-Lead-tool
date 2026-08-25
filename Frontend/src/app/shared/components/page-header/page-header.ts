import { Component, input } from '@angular/core';

@Component({
  selector: 'app-page-header',
  template: `
    <div class="page-header">
      <div class="page-header__text">
        <h1 class="page-header__title">{{ title() }}</h1>
        @if (subtitle()) {
          <p class="page-header__subtitle">{{ subtitle() }}</p>
        }
      </div>
      <div class="page-header__actions">
        <ng-content />
      </div>
    </div>
  `,
  styles: [`
    .page-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
      flex-wrap: wrap;

      &__title {
        font-size: 22px;
        font-weight: 700;
        color: var(--color-text);
        margin: 0;
      }

      &__subtitle {
        font-size: 14px;
        color: var(--color-text-secondary);
        margin: 4px 0 0;
      }

      &__actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-shrink: 0;
      }
    }
  `],
})
export class PageHeader {
  title    = input.required<string>();
  subtitle = input<string>();
}
