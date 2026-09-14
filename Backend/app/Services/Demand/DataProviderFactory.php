<?php

namespace App\Services\Demand;

use App\Models\DataSource;
use Illuminate\Support\Facades\Http;

/**
 * DataProviderFactory - Creates provider instances for DataSource integrations
 * 
 * This handles the API integrations (Google Trends, Google Ads, etc.)
 * which are different from SourceScrapers
 */
class DataProviderFactory
{
    /**
     * Create a provider instance for the given data source
     */
    public function make(DataSource $source)
    {
        return match ($source->type) {
            'google_trends' => new GoogleTrendsProvider($source),
            'google_ads' => new GoogleAdsProvider($source),
            'search_console' => new SearchConsoleProvider($source),
            'africas_talking' => new AfricasTalkingProvider($source),
            'webhook' => new WebhookProvider($source),
            default => throw new \InvalidArgumentException("Unknown provider type: {$source->type}"),
        };
    }

    /**
     * Test if a provider can be instantiated
     */
    public function canMake(string $type): bool
    {
        return in_array($type, [
            'google_trends',
            'google_ads',
            'search_console',
            'africas_talking',
            'webhook',
        ]);
    }
}

/**
 * Base Provider Interface
 */
interface DataProviderInterface
{
    public function test(): array;
    public function collect(array $keywords): array;
    public function supports(string $feature): bool;
}

/**
 * Google Trends Provider
 */
class GoogleTrendsProvider implements DataProviderInterface
{
    public function __construct(private DataSource $source) {}

    public function test(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'DemandLead/1.0'])
                ->get('https://trends.google.com/trending/rss', ['geo' => 'US']);

            return [
                'success' => $response->successful(),
                'message' => $response->successful() ? 'Google Trends is accessible' : 'Failed to connect',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function collect(array $keywords): array
    {
        // Implementation would use actual Google Trends API/library
        // For now, return structure
        return [
            'provider' => 'google_trends',
            'collected_at' => now(),
            'keywords' => [],
        ];
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['trends', 'interest_over_time', 'related_queries']);
    }
}

/**
 * Google Ads Provider
 */
class GoogleAdsProvider implements DataProviderInterface
{
    public function __construct(private DataSource $source) {}

    public function test(): array
    {
        $creds = $this->source->getCredentials();
        
        if (empty($creds['client_id']) || empty($creds['developer_token'])) {
            return ['success' => false, 'message' => 'Missing Google Ads credentials'];
        }

        // Would implement actual OAuth test here
        return ['success' => true, 'message' => 'Credentials configured (OAuth test not implemented)'];
    }

    public function collect(array $keywords): array
    {
        return [
            'provider' => 'google_ads',
            'collected_at' => now(),
            'keywords' => [],
        ];
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['search_volume', 'cpc', 'competition']);
    }
}

/**
 * Search Console Provider
 */
class SearchConsoleProvider implements DataProviderInterface
{
    public function __construct(private DataSource $source) {}

    public function test(): array
    {
        $creds = $this->source->getCredentials();
        
        if (empty($creds['site_url']) || empty($creds['service_account_json'])) {
            return ['success' => false, 'message' => 'Missing Search Console credentials'];
        }

        return ['success' => true, 'message' => 'Credentials configured (API test not implemented)'];
    }

    public function collect(array $keywords): array
    {
        return [
            'provider' => 'search_console',
            'collected_at' => now(),
            'keywords' => [],
        ];
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['impressions', 'clicks', 'ctr', 'position']);
    }
}

/**
 * Africa's Talking Provider
 */
class AfricasTalkingProvider implements DataProviderInterface
{
    public function __construct(private DataSource $source) {}

    public function test(): array
    {
        $creds = $this->source->getCredentials();
        $apiKey = $creds['api_key'] ?? env('AT_API_KEY');
        $username = $creds['username'] ?? env('AT_USERNAME');

        if (!$apiKey || !$username) {
            return ['success' => false, 'message' => 'Missing Africa\'s Talking credentials'];
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->get("https://api.africastalking.com/version1/user?username={$username}");

            return [
                'success' => $response->successful(),
                'message' => $response->successful() 
                    ? "Connected as {$username}" 
                    : "Failed: HTTP {$response->status()}",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function collect(array $keywords): array
    {
        // This provider is for sending alerts, not collecting data
        return [
            'provider' => 'africas_talking',
            'collected_at' => now(),
            'keywords' => [],
        ];
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['sms', 'voice', 'ussd']);
    }

    public function sendSms(string $to, string $message): array
    {
        $creds = $this->source->getCredentials();
        $apiKey = $creds['api_key'] ?? env('AT_API_KEY');
        $username = $creds['username'] ?? env('AT_USERNAME');

        try {
            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to' => $to,
                'message' => $message,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

/**
 * Webhook Provider
 */
class WebhookProvider implements DataProviderInterface
{
    public function __construct(private DataSource $source) {}

    public function test(): array
    {
        $creds = $this->source->getCredentials();
        $url = $creds['url'] ?? null;

        if (!$url) {
            return ['success' => false, 'message' => 'No webhook URL configured'];
        }

        try {
            $response = Http::timeout(8)->post($url, [
                'event' => 'test',
                'source' => 'demandlead',
                'timestamp' => now()->toIso8601String(),
            ]);

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? "Webhook responded with HTTP {$response->status()}"
                    : "Webhook failed: HTTP {$response->status()}",
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function collect(array $keywords): array
    {
        // Webhooks don't collect data, they send it
        return [
            'provider' => 'webhook',
            'collected_at' => now(),
            'keywords' => [],
        ];
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, ['push_notifications', 'alerts']);
    }

    public function send(array $data): array
    {
        $creds = $this->source->getCredentials();
        $url = $creds['url'] ?? null;
        $secret = $creds['secret'] ?? null;

        if (!$url) {
            return ['success' => false, 'error' => 'No webhook URL configured'];
        }

        try {
            $headers = ['Content-Type' => 'application/json'];
            
            // Add custom headers if configured
            if (!empty($creds['headers'])) {
                $customHeaders = explode("\n", $creds['headers']);
                foreach ($customHeaders as $header) {
                    if (str_contains($header, ':')) {
                        [$key, $value] = explode(':', $header, 2);
                        $headers[trim($key)] = trim($value);
                    }
                }
            }

            // Add signature if secret is configured
            if ($secret) {
                $payload = json_encode($data);
                $signature = hash_hmac('sha256', $payload, $secret);
                $headers['X-Webhook-Signature'] = $signature;
            }

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $data);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
