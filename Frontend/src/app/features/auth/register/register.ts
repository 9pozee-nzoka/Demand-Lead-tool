import { Component, signal } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';
import { NotificationService } from '../../../core/services/notification.service';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './register.html',
  styleUrl:    './register.scss',
})
export class Register {
  form: FormGroup;
  loading  = signal(false);
  showPass = signal(false);
  step     = signal<1 | 2>(1); // step 1 = org info, step 2 = user info

  constructor(
    private fb: FormBuilder,
    private auth: AuthService,
    private router: Router,
    private notify: NotificationService,
  ) {
    this.form = this.fb.group({
      // Step 1 — Organization
      organization_name: ['', [Validators.required, Validators.minLength(2)]],
      industry:          [''],
      country:           ['KE'],
      timezone:          ['Africa/Nairobi'],
      // Step 2 — User
      name:                  ['', Validators.required],
      email:                 ['', [Validators.required, Validators.email]],
      phone:                 [''],
      password:              ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', Validators.required],
    }, { validators: this.passwordMatch });
  }

  passwordMatch(group: FormGroup) {
    const pw  = group.get('password')?.value;
    const pwc = group.get('password_confirmation')?.value;
    return pw === pwc ? null : { passwordMismatch: true };
  }

  nextStep(): void {
    const orgName = this.form.get('organization_name');
    if (orgName?.invalid) { orgName.markAsTouched(); return; }
    this.step.set(2);
  }

  submit(): void {
    if (this.form.invalid || this.loading()) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.auth.register(this.form.value).subscribe({
      next: () => {
        this.notify.success('Account created! Welcome aboard.');
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.loading.set(false);
        const firstError = Object.values(err.error?.errors ?? {})?.[0] as string[] | undefined;
        this.notify.error(firstError?.[0] ?? err.error?.message ?? 'Registration failed.');
      },
    });
  }

  get orgName()  { return this.form.get('organization_name')!; }
  get name()     { return this.form.get('name')!; }
  get email()    { return this.form.get('email')!; }
  get password() { return this.form.get('password')!; }
  get pwConfirm(){ return this.form.get('password_confirmation')!; }
}
