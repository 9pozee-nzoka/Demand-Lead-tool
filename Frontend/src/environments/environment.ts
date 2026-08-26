// Development environment — API calls go through the Angular dev-server proxy
// so the base URL is empty (relative paths like /api/v1/... work directly).
export const environment = {
  production: false,
  apiBase:    '',            // relative — proxied to localhost:8000 by proxy.conf.json
  appUrl:     'http://localhost:4200',
};
