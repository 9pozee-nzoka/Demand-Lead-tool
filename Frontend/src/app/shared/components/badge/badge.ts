import { Component, input } from '@angular/core';
import { NgClass } from '@angular/common';

export type BadgeVariant = 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'neutral';

@Component({
  selector: 'app-badge',
  imports: [NgClass],
  template: `
    <span class="badge" [ngClass]="'badge--' + variant()">
      <ng-content />
    </span>
  `,
  styles: [`
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;

      &--primary { background: #eff6ff; color: #1d4ed8; }
      &--success { background: #f0fdf4; color: #15803d; }
      &--warning { background: #fffbeb; color: #b45309; }
      &--danger  { background: #fff1f2; color: #be123c; }
      &--info    { background: #f0f9ff; color: #0369a1; }
      &--neutral { background: #f4f4f5; color: #71717a; }
    }
  `],
})
export class Badge {
  variant = input<BadgeVariant>('neutral');
}
