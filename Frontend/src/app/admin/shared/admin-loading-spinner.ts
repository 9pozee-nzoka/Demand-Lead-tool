import { Component } from '@angular/core';

@Component({
  selector: 'app-admin-loading-spinner',
  template: `
    <div class="admin-spinner-wrap">
      <div class="admin-spinner"></div>
    </div>
  `,
  styles: [`
    .admin-spinner-wrap {
      display: flex; align-items: center; justify-content: center;
      min-height: 40vh;
    }
    .admin-spinner {
      width: 36px; height: 36px;
      border: 3px solid rgba(99,102,241,.2);
      border-top-color: #6366f1;
      border-radius: 50%;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  `],
})
export class AdminLoadingSpinner {}
