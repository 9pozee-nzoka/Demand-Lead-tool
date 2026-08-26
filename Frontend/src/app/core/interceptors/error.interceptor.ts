import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { NotificationService } from '../services/notification.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const notify = inject(NotificationService);
  const router = inject(Router);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      // 401 — handled upstream by authInterceptor (redirects to login)
      if (err.status === 401) {
        return throwError(() => err);
      }

      const serverMessage: string | undefined = err.error?.message;

      switch (err.status) {
        case 0:
          notify.error('Cannot reach the server. Check your connection.', 0);
          break;

        case 403:
          notify.error(serverMessage ?? 'You do not have permission to perform this action.');
          break;

        case 404:
          // Don't toast on every 404 — only for explicit API calls (not background polling)
          if (!req.headers.has('X-Silent')) {
            notify.warning(serverMessage ?? 'The requested resource was not found.');
          }
          break;

        case 422: {
          // Validation errors — collect all field messages into one toast
          const errors: Record<string, string[]> = err.error?.errors ?? {};
          const lines = Object.values(errors).flat();
          const message = lines.length > 0
            ? lines.join(' ')
            : (serverMessage ?? 'Validation failed. Please check your input.');
          notify.warning(message);
          break;
        }

        case 429:
          notify.warning(serverMessage ?? 'Too many requests. Please slow down and try again.');
          break;

        case 500:
        case 502:
        case 503:
          notify.error(
            serverMessage ?? `Server error (${err.status}). Our team has been notified.`,
            8000,
          );
          break;

        default:
          notify.error(serverMessage ?? `Unexpected error (${err.status}).`);
      }

      return throwError(() => err);
    }),
  );
};
