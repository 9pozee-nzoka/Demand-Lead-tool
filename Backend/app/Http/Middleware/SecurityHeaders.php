<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security Headers
        $headers = [
            // Prevent clickjacking attacks
            'X-Frame-Options' => 'SAMEORIGIN',
            
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Enable XSS protection
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions policy
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            
            // Strict Transport Security (HSTS) - only in production with HTTPS
            ...(config('app.env') === 'production' ? [
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            ] : []),
        ];

        // Content Security Policy
        if (!$request->is('lp/*')) { // Exclude public landing pages that need inline styles
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https://api.openai.com https://graph.facebook.com",
                "frame-ancestors 'self'",
            ];
            
            $headers['Content-Security-Policy'] = implode('; ', $csp);
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
