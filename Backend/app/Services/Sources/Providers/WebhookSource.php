<?php

namespace App\Services\Sources\Providers;

use App\Models\ScrapeJob;
use App\Models\SourceScraper;
use App\Services\Sources\AbstractDataSource;
use Illuminate\Support\Str;

/**
 * WebhookSource - Webhook-based lead capture provider
 * 
 * Receives leads from customer websites via HTTP webhooks/POST requests.
 * Does not actively fetch data - waits for incoming webhook calls.
 */
class WebhookSource extends AbstractDataSource
{
    /**
     * Fetch is not applicable for webhooks (passive receiver)
     * Returns empty array as webhooks are received, not fetched
     */
    public function fetch(SourceScraper $source, ScrapeJob $job): array
    {
        // Webhooks don't fetch - they receive
        // This method is called for compatibility but returns empty
        return [];
    }

    /**
     * Parse webhook payload to standardized format
     */
    public function parse(array $rawItem, SourceScraper $source): array
    {
        $config = $source->configuration ?? [];
        
        // Extract data using field mappings from configuration
        $fieldMap = $config['field_mapping'] ?? $this->getDefaultFieldMapping();
        
        return [
            'title' => $this->extractField($rawItem, $fieldMap['title'] ?? ['subject', 'title', 'name']),
            'description' => $this->extractField($rawItem, $fieldMap['description'] ?? ['message', 'description', 'comments']),
            'url' => $rawItem['source_url'] ?? $rawItem['page_url'] ?? null,
            'published_at' => now(),
            'source_name' => $this->extractSourceName($source),
            'external_id' => $this->generateExternalId($rawItem),
            'metadata' => $this->extractMetadata($rawItem, $fieldMap),
        ];
    }

    /**
     * Extract field value from raw data using multiple possible keys
     */
    protected function extractField(array $data, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($data[$key]) && !empty($data[$key])) {
                return is_string($data[$key]) ? $data[$key] : json_encode($data[$key]);
            }
        }
        return null;
    }

    /**
     * Extract metadata from webhook payload
     */
    protected function extractMetadata(array $data, array $fieldMap): array
    {
        $metadata = [
            'contact_name' => $this->extractField($data, $fieldMap['contact_name'] ?? ['name', 'full_name', 'contact_name']),
            'email' => $this->extractField($data, $fieldMap['email'] ?? ['email', 'email_address', 'contact_email']),
            'phone' => $this->extractField($data, $fieldMap['phone'] ?? ['phone', 'telephone', 'contact_phone', 'mobile']),
            'company' => $this->extractField($data, $fieldMap['company'] ?? ['company', 'organization', 'business_name']),
            'location' => $this->extractField($data, $fieldMap['location'] ?? ['location', 'city', 'address']),
            'source_url' => $data['source_url'] ?? $data['page_url'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'form_id' => $data['form_id'] ?? null,
        ];

        // Include any additional custom fields
        $standardKeys = ['title', 'description', 'name', 'email', 'phone', 'company', 'message', 'comments'];
        foreach ($data as $key => $value) {
            if (!in_array($key, $standardKeys) && !isset($metadata[$key])) {
                $metadata['custom_' . $key] = $value;
            }
        }

        return array_filter($metadata);
    }

    /**
     * Extract source name
     */
    protected function extractSourceName(SourceScraper $source): string
    {
        if (isset($source->configuration['source_name'])) {
            return $source->configuration['source_name'];
        }

        return 'Website Lead Capture';
    }

    /**
     * Generate external ID for webhook submission
     */
    protected function generateExternalId(array $item): string
    {
        // Use submission ID if provided
        if (!empty($item['submission_id'])) {
            return 'webhook_' . $item['submission_id'];
        }

        // Generate from email + timestamp
        if (!empty($item['email'])) {
            return 'webhook_' . md5($item['email'] . ($item['timestamp'] ?? time()));
        }

        // Fallback to random ID
        return 'webhook_' . Str::random(16);
    }

    /**
     * Get the source type
     */
    public function getType(): string
    {
        return 'webhook';
    }

    /**
     * Get the source category
     */
    public function getCategory(): string
    {
        return 'lead_capture';
    }

    /**
     * Get default configuration
     */
    public function getDefaultConfiguration(): array
    {
        return [
            'webhook_url' => null, // Will be auto-generated
            'authentication' => [
                'type' => 'token', // token, hmac, none
                'token' => null, // Auto-generated secret token
            ],
            'field_mapping' => $this->getDefaultFieldMapping(),
            'auto_qualify' => true, // Automatically qualify leads
            'notification' => [
                'enabled' => true,
                'channels' => ['email'], // email, sms, slack
            ],
        ];
    }

    /**
     * Get default field mapping
     */
    protected function getDefaultFieldMapping(): array
    {
        return [
            'title' => ['subject', 'title', 'inquiry'],
            'description' => ['message', 'description', 'comments', 'details'],
            'contact_name' => ['name', 'full_name', 'contact_name', 'your_name'],
            'email' => ['email', 'email_address', 'contact_email'],
            'phone' => ['phone', 'telephone', 'contact_phone', 'mobile', 'phone_number'],
            'company' => ['company', 'organization', 'business_name', 'company_name'],
            'location' => ['location', 'city', 'address', 'region'],
        ];
    }

    /**
     * Get required configuration keys
     */
    protected function getRequiredConfigKeys(): array
    {
        return []; // No required config - webhook URL is auto-generated
    }

    /**
     * Generate webhook URL for a source
     */
    public function generateWebhookUrl(SourceScraper $source): string
    {
        $token = $this->generateWebhookToken();
        
        // Update source configuration with token
        $config = $source->configuration ?? [];
        $config['authentication']['token'] = $token;
        $source->configuration = $config;
        $source->save();

        return route('api.webhooks.receive', [
            'source' => $source->id,
            'token' => $token,
        ]);
    }

    /**
     * Generate secure webhook token
     */
    protected function generateWebhookToken(): string
    {
        return Str::random(64);
    }

    /**
     * Validate webhook authentication
     */
    public function validateWebhook(SourceScraper $source, array $payload, string $providedToken): bool
    {
        $config = $source->configuration ?? [];
        $authType = $config['authentication']['type'] ?? 'token';

        if ($authType === 'none') {
            return true;
        }

        if ($authType === 'token') {
            $expectedToken = $config['authentication']['token'] ?? null;
            return $expectedToken && hash_equals($expectedToken, $providedToken);
        }

        if ($authType === 'hmac') {
            // HMAC signature validation
            $secret = $config['authentication']['secret'] ?? null;
            $signature = $payload['signature'] ?? '';
            unset($payload['signature']);
            
            $expected = hash_hmac('sha256', json_encode($payload), $secret);
            return hash_equals($expected, $signature);
        }

        return false;
    }

    /**
     * Process incoming webhook
     */
    public function processWebhook(SourceScraper $source, array $payload): array
    {
        // Parse the payload
        $parsed = $this->parse($payload, $source);
        
        // Normalize the data
        $normalized = $this->normalize($parsed);

        return [
            'success' => true,
            'data' => $normalized,
            'source_id' => $source->id,
        ];
    }

    /**
     * Get webhook integration code snippets
     */
    public function getIntegrationSnippets(SourceScraper $source): array
    {
        $webhookUrl = $this->generateWebhookUrl($source);
        $token = $source->configuration['authentication']['token'] ?? '';

        return [
            'html_form' => $this->getHtmlFormSnippet($webhookUrl, $token),
            'javascript' => $this->getJavaScriptSnippet($webhookUrl, $token),
            'php' => $this->getPhpSnippet($webhookUrl, $token),
            'curl' => $this->getCurlSnippet($webhookUrl, $token),
        ];
    }

    /**
     * Get HTML form snippet
     */
    protected function getHtmlFormSnippet(string $url, string $token): string
    {
        return <<<HTML
<form id="leadForm" action="{$url}" method="POST">
    <input type="hidden" name="token" value="{$token}">
    <input type="text" name="name" placeholder="Your Name" required>
    <input type="email" name="email" placeholder="Your Email" required>
    <input type="tel" name="phone" placeholder="Your Phone">
    <input type="text" name="company" placeholder="Company Name">
    <textarea name="message" placeholder="Your Message" required></textarea>
    <button type="submit">Submit</button>
</form>
HTML;
    }

    /**
     * Get JavaScript snippet
     */
    protected function getJavaScriptSnippet(string $url, string $token): string
    {
        return <<<JS
fetch('{$url}', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer {$token}'
    },
    body: JSON.stringify({
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+254712345678',
        company: 'Example Co',
        message: 'I am interested in your services'
    })
}).then(response => response.json())
  .then(data => console.log('Success:', data))
  .catch(error => console.error('Error:', error));
JS;
    }

    /**
     * Get PHP snippet
     */
    protected function getPhpSnippet(string $url, string $token): string
    {
        return <<<PHP
\$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+254712345678',
    'company' => 'Example Co',
    'message' => 'I am interested in your services'
];

\$ch = curl_init('{$url}');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_POST, true);
curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$data));
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer {$token}'
]);

\$response = curl_exec(\$ch);
curl_close(\$ch);
PHP;
    }

    /**
     * Get cURL snippet
     */
    protected function getCurlSnippet(string $url, string $token): string
    {
        return <<<CURL
curl -X POST {$url} \\
  -H "Content-Type: application/json" \\
  -H "Authorization: Bearer {$token}" \\
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+254712345678",
    "company": "Example Co",
    "message": "I am interested in your services"
  }'
CURL;
    }
}
