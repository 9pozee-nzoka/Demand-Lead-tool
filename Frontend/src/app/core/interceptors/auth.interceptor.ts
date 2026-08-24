import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { TokenStorage } from '../auth/token.storage';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const tokenStorage = inject(TokenStorage);
  const router       = inject(Router);
  const token        = tokenStorage.getToken();

  // Attach Bearer token to every outgoing request
  const authReq = token
    ? req.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
    : req;

  return next(authReq).pipe(
    catchError((err: HttpErrorResponse) => {
      if (err.status === 401) {
        // Token expired or invalid — clear and redirect to login
        tokenStorage.clear();
        router.navigate(['/login'], { queryParams: { reason: 'session_expired' } });
      }
      return throwError(() => err);
    }),
  );
};
