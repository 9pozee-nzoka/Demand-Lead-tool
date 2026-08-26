import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { AdminTokenService } from '../services/admin-token.service';

export const adminAuthGuard = () => {
  const token  = inject(AdminTokenService);
  const router = inject(Router);

  if (token.hasToken()) {
    return true;
  }
  return router.createUrlTree(['/admin/login']);
};
