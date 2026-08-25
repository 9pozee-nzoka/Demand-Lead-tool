import { Component, input } from '@angular/core';

@Component({
  selector: 'app-empty-state',
  template: `
    <div class="empty">
      <span class="empty__icon material-symbols-rounded">{{ icon() }}</span>
      <h3 class="empty__title">{{ title() }}</h3>
      @if (message()) {
        <p class="empty__message">{{ message() }}</p>
      }
      <ng-content />
    </div>
  `,
  styles: [`
    .empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      text-align: center;
      gap: 12px;

      &__icon {
        font-size: 48px;
        color: var(--color-border);
      }

      &__title {
        font-size: 17px;
        font-weight: 600;
        color: var(--color-text);
        margin: 0;
      }

      &__message {
        font-size: 14px;
        color: var(--color-text-secondary);
        max-width: 340px;
        line-height: 1.6;
        margin: 0;
      }
    }
  `],
})
export class EmptyState {
  icon    = input('inbox');
  title   = input.required<string>();
  message = input<string>();
}
