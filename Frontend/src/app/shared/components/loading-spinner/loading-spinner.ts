import { Component, input } from '@angular/core';

@Component({
  selector: 'app-loading-spinner',
  template: `
    <div class="spinner-wrap" [class.spinner-wrap--full]="full()">
      <div class="spinner" [style.width.px]="size()" [style.height.px]="size()"></div>
    </div>
  `,
  styles: [`
    .spinner-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;

      &--full {
        min-height: 300px;
      }
    }
    .spinner {
      border: 3px solid var(--color-border);
      border-top-color: var(--color-primary);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  `],
})
export class LoadingSpinner {
  size = input(32);
  full = input(false);
}
