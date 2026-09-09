<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditSecurityEvents
{
    /**
     * Handle an incoming request and log security-relevant events
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log suspicious patterns before processing
        $this->detectSuspiciousActivity($request);

        $response = $next($request);

        // Log authentication events
        if ($this->isAuthenticationEndpoint($request)) {
            $this->logAuthenticationAttempt($request, $response);
        }

        // Log admin actions
        if ($request->user() && $request->user()->role === 'owner') {
            $this->logAdminAction($request);
        }

        return $response;
    }

    /**
     * Detect and log suspicious activity
     */
    protected function detectSuspiciousActivity(Request $request): void
    {
        $suspicious = false;
        $reasons = [];

        // Check for SQL injection patterns
        foreach ($request->all() as $key => $value) {
            if (is_string($value) && $this->containsSqlPattern($value)) {
                $suspicious = true;
                $reasons[] = 'SQL injection pattern detected';
                break;
            }
        }

        // Check for XSS patterns
        foreach ($request->all() as $key => $value) {
            if (is_string($value) && $this->containsXssPattern($value)) {
                $suspicious = true;
                $reasons[] = 'XSS pattern detected';
                break;
            }
        }

        // Check for path traversal
        if ($this->containsPathTraversal($request->getPathInfo())) {
            $suspicious = true;
            $reasons[] = 'Path traversal attempt';
        }

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        if ($this->isSuspiciousUserAgent($userAgent)) {
            $suspicious = true;
            $reasons[] = 'Suspicious user agent';
        }

        if ($suspicious) {
            Log::warning('Suspicious activity detected', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $userAgent,
                'reasons' => $reasons,
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    /**
     * Log authentication attempts
     */
    protected function logAuthenticationAttempt(Request $request, Response $response): void
    {
        $successful = $response->isSuccessful();

        Log::info('Authentication attempt', [
            'success' => $successful,
            'ip' => $request->ip(),
            'email' => $request->input('email'),
            'path' => $request->path(),
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode(),
        ]);

        // Alert on failed login attempts
        if (!$successful && $request->is('login')) {
            $this->checkFailedLoginAttempts($request);
        }
    }

    /**
     * Log admin actions
     */
    protected function logAdminAction(Request $request): void
    {
        // Only log state-changing operations
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            Log::info('Admin action', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'input' => $this->sanitizeLogData($request->except(['password', 'password_confirmation'])),
            ]);
        }
    }

    /**
     * Check if endpoint is authentication-related
     */
    protected function isAuthenticationEndpoint(Request $request): bool
    {
        return $request->is('login') 
            || $request->is('register') 
            || $request->is('logout')
            || $request->is('password/*');
    }

    /**
     * Check for SQL injection patterns
     */
    protected function containsSqlPattern(string $value): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\b(TABLE|DATABASE)\b)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    protected function containsXssPattern(string $value): bool
    {
        $patterns = [
            '/<script\b/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for path traversal attempts
     */
    protected function containsPathTraversal(string $path): bool
    {
        return str_contains($path, '..') || str_contains($path, '//');
    }

    /**
     * Check if user agent is suspicious
     */
    protected function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if (!$userAgent) {
            return true;
        }

        $suspicious = ['sqlmap', 'nikto', 'nmap', 'masscan', 'metasploit'];

        foreach ($suspicious as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check and alert on multiple failed login attempts
     */
    protected function checkFailedLoginAttempts(Request $request): void
    {
        $key = 'failed_login:' . $request->ip();
        $attempts = cache()->get($key, 0);
        
        cache()->put($key, $attempts + 1, now()->addMinutes(15));

        if ($attempts >= 5) {
            Log::warning('Multiple failed login attempts detected', [
                'ip' => $request->ip(),
                'attempts' => $attempts + 1,
                'email' => $request->input('email'),
            ]);

            // In production, you might want to:
            // - Send alert email to admin
            // - Temporarily block the IP
            // - Require CAPTCHA
        }
    }

    /**
     * Sanitize data for logging (remove sensitive fields)
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'access_token'];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeLogData($value);
            } elseif (is_string($key)) {
                foreach ($sensitive as $pattern) {
                    if (stripos($key, $pattern) !== false) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
        }

        return $data;
    }
}
