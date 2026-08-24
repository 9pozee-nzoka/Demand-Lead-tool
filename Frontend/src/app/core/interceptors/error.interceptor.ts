import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';
import { NotificationService } from '../services/notification.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const notify = inject(NotificationService);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      // 401 is handled by authInterceptor — skip here
      if (err.status !== 401) {
        const message =
          err.error?.message ??
          (err.status === 0 ? 'Cannot reach server. Check your connection.' : `Server error (${err.status})`);
        notify.error(message);
      }
      return throwError(() => err);
    }),
  );
};
