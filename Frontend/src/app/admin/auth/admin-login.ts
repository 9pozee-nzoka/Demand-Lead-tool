import { Component, signal } from '@angular/core';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AdminAuthService } from '../services/admin-auth.service';

@Component({
  selector: 'app-admin-login',
  imports: [ReactiveFormsModule],
  template: `
    <div class="admin-login">
      <div class="admin-login__card">
        <div class="admin-login__brand">
          <span class="material-symbols-rounded admin-login__logo">admin_panel_settings</span>
          <div>
            <div class="admin-login__brand-name">DemandLead</div>
            <div class="admin-login__brand-tag">Super-Admin Panel</div>
          </div>
        </div>

        <h1 class="admin-login__title">Sign in</h1>
        <p class="admin-login__sub">Super-admin access only.</p>

        @if (error()) {
          <div class="admin-login__error">
            <span class="material-symbols-rounded">error</span>{{ error() }}
          </div>
        }

        <form [formGroup]="form" (ngSubmit)="submit()" novalidate>
          <div class="al-field">
            <label for="al_email">Email</label>
            <input id="al_email" type="email" formControlName="email"
              placeholder="admin@yourdomain.com"
              [class.al-field__input--error]="emailField.invalid && emailField.touched" />
          </div>
          <div class="al-field">
            <label for="al_pwd">Password</label>
            <input id="al_pwd" type="password" formControlName="password"
              placeholder="••••••••"
              [class.al-field__input--error]="pwdField.invalid && pwdField.touched" />
          </div>
          <button type="submit" class="al-submit" [disabled]="loading()">
            @if (loading()) { <span class="al-spinner"></span> Signing in… }
            @else { Sign in }
          </button>
        </form>
      </div>
    </div>
  `,
  styles: [`
    :host { display: block; }
    .admin-login {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: #0f172a; padding: 24px; font-family: 'Inter', system-ui, sans-serif;
    }
    .admin-login__card {
      width: 100%; max-width: 400px; background: #1e293b; border-radius: 16px;
      padding: 36px 32px; border: 1px solid rgba(255,255,255,.08);
      box-shadow: 0 24px 48px rgba(0,0,0,.4);
    }
    .admin-login__brand { display: flex; align-items: center; gap: 12px; margin-bottom: 28px; }
    .admin-login__logo  {
      font-size: 32px; color: #818cf8;
      font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 48;
    }
    .admin-login__brand-name { font-size: 18px; font-weight: 800; color: #f1f5f9; }
    .admin-login__brand-tag  { font-size: 11px; font-weight: 600; color: #6366f1; text-transform: uppercase; letter-spacing: 0.8px; }
    .admin-login__title { font-size: 22px; font-weight: 800; color: #f1f5f9; margin: 0 0 4px; }
    .admin-login__sub   { font-size: 13px; color: #64748b; margin: 0 0 24px; }
    .admin-login__error {
      display: flex; align-items: center; gap: 8px;
      background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3);
      color: #fca5a5; font-size: 13px; padding: 10px 14px; border-radius: 8px; margin-bottom: 18px;
      .material-symbols-rounded { font-size: 18px; flex-shrink: 0; }
    }
    .al-field {
      display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px;
      label { font-size: 13px; font-weight: 600; color: #94a3b8; }
      input {
        padding: 10px 14px; background: #0f172a; border: 1px solid rgba(255,255,255,.12);
        border-radius: 10px; color: #f1f5f9; font-size: 14.5px; font-family: inherit;
        width: 100%; box-sizing: border-box; outline: none; transition: border-color .15s;
        &::placeholder { color: #475569; }
        &:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
      }
      &__input--error { border-color: #ef4444 !important; }
    }
    .al-submit {
      width: 100%; padding: 12px; margin-top: 6px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff; border: none; border-radius: 10px;
      font-size: 15px; font-weight: 700; font-family: inherit; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: opacity .15s;
      &:hover:not(:disabled) { opacity: .9; }
      &:disabled { opacity: .5; cursor: not-allowed; }
    }
    .al-spinner {
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.3); border-top-color: #fff;
      border-radius: 50%; animation: spin .6s linear infinite; flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  `],
})
export class AdminLogin {
  loading = signal(false);
  error   = signal<string | null>(null);

  form: FormGroup;

  constructor(
    private fb:     FormBuilder,
    private auth:   AdminAuthService,
    private router: Router,
  ) {
    this.form = this.fb.group({
      email:    ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });
  }

  submit(): void {
    if (this.form.invalid || this.loading()) { this.form.markAllAsTouched(); return; }
    this.loading.set(true);
    this.error.set(null);

    this.auth.login(this.form.value.email!, this.form.value.password!).subscribe({
      next: () => { this.loading.set(false); this.router.navigate(['/admin/dashboard']); },
      error: (err: any) => {
        this.loading.set(false);
        this.error.set(err.message ?? err.error?.message ?? 'Login failed.');
      },
    });
  }

  get emailField() { return this.form.get('email')!; }
  get pwdField()   { return this.form.get('password')!; }
}
