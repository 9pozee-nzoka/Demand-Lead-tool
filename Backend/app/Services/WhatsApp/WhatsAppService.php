<?php

namespace App\Services\WhatsApp;

use App\Models\Lead;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected ?string $accessToken;
    protected ?string $phoneNumberId;
    protected ?string $verifyToken;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->verifyToken = config('services.whatsapp.verify_token');
    }

    /**
     * Send a WhatsApp message
     */
    public function sendMessage(string $to, string $message, ?string $type = 'text'): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => $type,
        ];

        if ($type === 'text') {
            $payload['text'] = ['body' => $message];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent', [
                    'to' => $to,
                    'message_id' => $response->json('messages.0.id'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                    'data' => $response->json(),
                ];
            }

            Log::error('WhatsApp message failed', [
                'to' => $to,
                'error' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp exception', [
                'to' => $to,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a template message
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = []): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
            ],
        ];

        if (!empty($parameters)) {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => $parameters,
                ],
            ];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook
     */
    public function verifyWebhook(string $mode, string $token, string $challenge): ?string
    {
        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            Log::info('WhatsApp webhook verified');
            return $challenge;
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return null;
    }

    /**
     * Process incoming webhook message
     */
    public function processWebhook(array $data): void
    {
        Log::info('WhatsApp webhook received', ['data' => $data]);

        if (!isset($data['entry'])) {
            return;
        }

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                if ($change['field'] !== 'messages') {
                    continue;
                }

                $value = $change['value'];

                // Process incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->handleIncomingMessage($message, $value['metadata'] ?? []);
                    }
                }

                // Process message status updates
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->handleStatusUpdate($status);
                    }
                }
            }
        }
    }

    /**
     * Handle incoming message
     */
    protected function handleIncomingMessage(array $message, array $metadata): void
    {
        $from = $message['from'];
        $messageId = $message['id'];
        $timestamp = $message['timestamp'];

        // Extract message content
        $messageText = '';
        $messageType = $message['type'];

        switch ($messageType) {
            case 'text':
                $messageText = $message['text']['body'] ?? '';
                break;
            case 'button':
                $messageText = $message['button']['text'] ?? '';
                break;
            case 'interactive':
                if (isset($message['interactive']['button_reply'])) {
                    $messageText = $message['interactive']['button_reply']['title'] ?? '';
                } elseif (isset($message['interactive']['list_reply'])) {
                    $messageText = $message['interactive']['list_reply']['title'] ?? '';
                }
                break;
            default:
                $messageText = "[{$messageType}]";
        }

        Log::info('WhatsApp message received', [
            'from' => $from,
            'message_id' => $messageId,
            'type' => $messageType,
            'text' => $messageText,
        ]);

        // Try to find existing lead or create new one
        $lead = $this->findOrCreateLead($from, $messageText);

        if ($lead) {
            // Log the conversation
            $lead->update([
                'last_contact_at' => now(),
                'message' => ($lead->message ?? '') . "\n[WhatsApp {$timestamp}]: {$messageText}",
            ]);

            // Auto-respond based on keywords
            $this->autoRespond($from, $messageText, $lead);
        }
    }

    /**
     * Handle status update
     */
    protected function handleStatusUpdate(array $status): void
    {
        $messageId = $status['id'];
        $statusValue = $status['status']; // sent, delivered, read, failed

        Log::info('WhatsApp status update', [
            'message_id' => $messageId,
            'status' => $statusValue,
        ]);

        // You can update lead_events or tracking here
    }

    /**
     * Find or create lead from WhatsApp number
     */
    protected function findOrCreateLead(string $phone, string $firstMessage): ?Lead
    {
        $formattedPhone = $this->formatPhoneNumber($phone);

        // Try to find existing lead
        $lead = Lead::where('phone', $formattedPhone)
            ->orWhere('phone', $phone)
            ->first();

        if ($lead) {
            return $lead;
        }

        // Extract name from message if possible
        $name = $this->extractNameFromMessage($firstMessage) ?? 'WhatsApp Contact';

        // Create new lead - assign to first organization with WhatsApp integration
        $organization = Organization::whereHas('dataSource', function ($query) {
            $query->where('provider', 'whatsapp')->where('status', 'active');
        })->first();

        if (!$organization) {
            // Fallback: use first organization
            $organization = Organization::first();
        }

        if (!$organization) {
            Log::error('No organization found for WhatsApp lead');
            return null;
        }

        return Lead::create([
            'organization_id' => $organization->id,
            'name' => $name,
            'phone' => $formattedPhone,
            'message' => $firstMessage,
            'source' => 'whatsapp',
            'status' => 'new',
            'lead_score' => 60, // WhatsApp leads typically higher intent
            'quality' => 'warm',
        ]);
    }

    /**
     * Auto-respond to incoming messages
     */
    protected function autoRespond(string $to, string $message, Lead $lead): void
    {
        $messageLower = strtolower($message);

        // Check for greeting keywords
        if (preg_match('/\b(hi|hello|hey|good morning|good afternoon)\b/i', $messageLower)) {
            $this->sendMessage(
                $to,
                "Hello! Thank you for contacting us. How can we help you today?\n\nReply with:\n• INFO - Learn more about our services\n• PRICING - View pricing\n• CONTACT - Speak with our team"
            );
            return;
        }

        // Check for info request
        if (preg_match('/\b(info|information|details|about)\b/i', $messageLower)) {
            $this->sendMessage(
                $to,
                "We help businesses track market demand and generate qualified leads automatically. Our AI-powered platform detects rising trends and creates opportunities for you.\n\nWould you like to schedule a demo? Reply YES to continue."
            );
            return;
        }

        // Check for pricing request
        if (preg_match('/\b(price|pricing|cost|how much)\b/i', $messageLower)) {
            $this->sendMessage(
                $to,
                "Our plans start from $50/month:\n\n• Starter: $50/mo\n• Growth: $150/mo\n• Pro: $300/mo\n\nAll plans include AI-powered insights and lead generation. Reply DEMO to see it in action!"
            );
            return;
        }

        // Check for affirmative responses
        if (preg_match('/\b(yes|sure|ok|okay|interested|demo)\b/i', $messageLower)) {
            $lead->update(['status' => 'qualified', 'quality' => 'hot']);
            
            $this->sendMessage(
                $to,
                "Excellent! A team member will contact you within 24 hours to schedule your personalized demo.\n\nIn the meantime, can you share:\n1. Your company name\n2. Your industry\n3. Your biggest challenge\n\nThis helps us tailor the demo to your needs."
            );
            return;
        }

        // Check for negative responses
        if (preg_match('/\b(no|not interested|stop|unsubscribe)\b/i', $messageLower)) {
            $lead->update(['status' => 'disqualified']);
            
            $this->sendMessage(
                $to,
                "No problem! Thank you for your time. Feel free to reach out if you have questions in the future."
            );
            return;
        }

        // Default response for unrecognized messages
        if ($lead->status === 'new') {
            $this->sendMessage(
                $to,
                "Thank you for your message. A team member will respond shortly.\n\nFor faster service, reply with:\n• INFO - Learn about our services\n• PRICING - View plans\n• DEMO - Schedule a demo"
            );
        }
    }

    /**
     * Extract name from first message
     */
    protected function extractNameFromMessage(string $message): ?string
    {
        // Look for "I'm NAME" or "My name is NAME" patterns
        if (preg_match('/(?:i\'m|i am|my name is)\s+([a-z]+(?:\s+[a-z]+)?)/i', $message, $matches)) {
            return ucwords(strtolower($matches[1]));
        }

        // Look for "NAME here" pattern
        if (preg_match('/^([a-z]+(?:\s+[a-z]+)?)\s+here/i', $message, $matches)) {
            return ucwords(strtolower($matches[1]));
        }

        return null;
    }

    /**
     * Format phone number for WhatsApp API
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If doesn't start with +, assume it needs country code
        if (!str_starts_with($phone, '+')) {
            // Add default country code if configured
            $defaultCountryCode = config('services.whatsapp.default_country_code', '254');
            $phone = $defaultCountryCode . ltrim($phone, '0');
        } else {
            // Remove + for API
            $phone = ltrim($phone, '+');
        }

        return $phone;
    }

    /**
     * Get business profile
     */
    public function getBusinessProfile(): array
    {
        $url = "{$this->apiUrl}/{$this->phoneNumberId}";

        try {
            $response = Http::withToken($this->accessToken)
                ->get($url, [
                    'fields' => 'verified_name,code_verification_status,display_phone_number,quality_rating',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send welcome message to new lead
     */
    public function sendWelcomeMessage(Lead $lead): array
    {
        $message = "👋 Hi {$lead->name}!\n\n";
        $message .= "Thank you for your interest in our demand intelligence platform.\n\n";
        $message .= "We help businesses:\n";
        $message .= "✅ Detect rising market demand\n";
        $message .= "✅ Generate qualified leads automatically\n";
        $message .= "✅ Make data-driven decisions\n\n";
        $message .= "Reply INFO to learn more or DEMO to schedule a walkthrough!";

        return $this->sendMessage($lead->phone, $message);
    }

    /**
     * Check if service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) 
            && !empty($this->phoneNumberId) 
            && !empty($this->verifyToken);
    }
}
