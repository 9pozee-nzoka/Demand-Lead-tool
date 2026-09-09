<?php

namespace App\Services\Alerts;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Africa's Talking SMS Provider
 * 
 * Sends SMS alerts for spike notifications and important events.
 * 
 * Sprint 9
 */
class AfricasTalkingSmsProvider
{
    protected string $apiKey;
    protected string $username;
    protected string $from;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.africas_talking.api_key');
        $this->username = config('services.africas_talking.username');
        $this->from = config('services.africas_talking.from', 'DemandLead');
        $this->baseUrl = config('services.africas_talking.sandbox', false) 
            ? 'https://api.sandbox.africastalking.com/version1'
            : 'https://api.africastalking.com/version1';
    }

    /**
     * Send SMS to single recipient
     */
    public function send(string $to, string $message): array
    {
        return $this->sendBulk([$to], $message);
    }

    /**
     * Send SMS to multiple recipients
     */
    public function sendBulk(array $recipients, string $message): array
    {
        if (empty($this->apiKey) || empty($this->username)) {
            Log::warning('Africa\'s Talking SMS not configured');
            return [
                'success' => false,
                'error' => 'SMS provider not configured',
            ];
        }

        // Format phone numbers (Africa's Talking expects international format)
        $to = implode(',', array_map(function($phone) {
            return $this->formatPhoneNumber($phone);
        }, $recipients));

        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])
            ->asForm()
            ->post("{$this->baseUrl}/messaging", [
                'username' => $this->username,
                'to' => $to,
                'message' => $message,
                'from' => $this->from,
            ]);

            if (!$response->successful()) {
                Log::error('Africa\'s Talking SMS failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'SMS delivery failed: ' . $response->body(),
                ];
            }

            $data = $response->json();

            Log::info('Africa\'s Talking SMS sent', [
                'recipients' => count($recipients),
                'response' => $data,
            ]);

            return [
                'success' => true,
                'data' => $data,
                'message_data' => $data['SMSMessageData'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Africa\'s Talking SMS exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If doesn't start with +, add it
        if (!str_starts_with($phone, '+')) {
            // Assume Kenya if no country code (change based on your region)
            if (strlen($phone) <= 10) {
                $phone = '+254' . ltrim($phone, '0');
            } else {
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Check account balance
     */
    public function getBalance(): ?array
    {
        if (empty($this->apiKey) || empty($this->username)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Accept' => 'application/json',
            ])
            ->get("{$this->baseUrl}/user", [
                'username' => $this->username,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get SMS balance', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if SMS is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->username);
    }

    /**
     * Send alert notification (formatted for alerts)
     */
    public function sendAlert(string $to, string $alertType, string $title, array $details = []): array
    {
        $message = $this->formatAlertMessage($alertType, $title, $details);
        return $this->send($to, $message);
    }

    /**
     * Format alert message for SMS
     */
    protected function formatAlertMessage(string $type, string $title, array $details): string
    {
        $message = "🚨 {$type}: {$title}\n";

        foreach ($details as $key => $value) {
            $message .= ucfirst($key) . ": {$value}\n";
        }

        $message .= "\n- DemandLead";

        // SMS limit is 160 chars for single message, 456 for concatenated
        if (strlen($message) > 450) {
            $message = substr($message, 0, 447) . '...';
        }

        return $message;
    }
}
