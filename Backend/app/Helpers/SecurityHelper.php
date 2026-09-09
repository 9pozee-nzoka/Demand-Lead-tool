<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class SecurityHelper
{
    /**
     * Sanitize user input to prevent XSS attacks
     */
    public static function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip all HTML tags except safe ones
        $input = strip_tags($input);
        
        // Encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($input);
    }

    /**
     * Sanitize HTML content (for rich text fields)
     */
    public static function sanitizeHtml(string $html): string
    {
        // Allow only safe HTML tags
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
        
        $cleaned = strip_tags($html, $allowedTags);
        
        // Remove dangerous attributes
        $cleaned = preg_replace('/<([a-z]+)([^>]*)(on\w+\s*=)/i', '<$1$2', $cleaned);
        
        // Remove javascript: protocol
        $cleaned = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href=', $cleaned);
        
        return $cleaned;
    }

    /**
     * Generate secure random token
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate and sanitize email
     */
    public static function sanitizeEmail(string $email): ?string
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower(trim($email));
        }
        
        return null;
    }

    /**
     * Sanitize phone number
     */
    public static function sanitizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        return preg_replace('/[^\d+]/', '', $phone);
    }

    /**
     * Sanitize URL
     */
    public static function sanitizeUrl(string $url): ?string
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        return null;
    }

    /**
     * Check if string contains SQL injection patterns
     */
    public static function containsSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(--|\#|\/\*|\*\/)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if string contains XSS patterns
     */
    public static function containsXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mask sensitive data for logging
     */
    public static function maskSensitiveData(string $data, int $visibleChars = 4): string
    {
        $length = strlen($data);
        
        if ($length <= $visibleChars) {
            return str_repeat('*', $length);
        }
        
        $visible = substr($data, 0, $visibleChars);
        $masked = str_repeat('*', $length - $visibleChars);
        
        return $visible . $masked;
    }

    /**
     * Validate CSRF token manually (for AJAX requests)
     */
    public static function validateCsrfToken(string $token): bool
    {
        return hash_equals(session()->token(), $token);
    }

    /**
     * Generate Content Security Policy nonce
     */
    public static function generateCspNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Sanitize filename for uploads
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions (e.g., file.php.jpg)
        $filename = preg_replace('/\.+/', '.', $filename);
        
        return $filename;
    }

    /**
     * Validate IP address against whitelist
     */
    public static function isIpWhitelisted(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowed) {
            if ($ip === $allowed || Str::is($allowed, $ip)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if request is from a bot/crawler
     */
    public static function isBot(string $userAgent): bool
    {
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/slurp/i',
            '/facebook/i',
            '/twitter/i',
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Rate limit key for specific action
     */
    public static function rateLimitKey(string $action, $identifier): string
    {
        return sprintf('rate_limit:%s:%s', $action, $identifier);
    }

    /**
     * Hash sensitive data for comparison without storing plain text
     */
    public static function hashSensitiveData(string $data): string
    {
        return hash_hmac('sha256', $data, config('app.key'));
    }
}
