// Production environment — browser hits the API subdomain directly
export const environment = {
  production: true,
  apiBase:    'https://api.soarcorp.co.ke',   // absolute URL — no proxy in production
  appUrl:     'https://app.soarcorp.co.ke',
};
