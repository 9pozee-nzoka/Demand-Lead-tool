import { HttpInterceptorFn } from '@angular/common/http';
import { environment } from '../../../environments/environment';

/**
 * API Base URL Interceptor
 * 
 * Prepends the environment.apiBase to all API requests that start with /api/
 * 
 * Development: apiBase is empty string, so /api/v1/... goes through proxy to localhost:8000
 * Production:  apiBase is https://api.soarcorp.co.ke, so requests go directly to backend
 */
export const apiBaseInterceptor: HttpInterceptorFn = (req, next) => {
  // Only modify requests to our API (those starting with /api/ or /sanctum/)
  if (req.url.startsWith('/api/') || req.url.startsWith('/sanctum/')) {
    const apiReq = req.clone({
      url: `${environment.apiBase}${req.url}`,
    });
    return next(apiReq);
  }

  // Pass through all other requests unchanged
  return next(req);
};
