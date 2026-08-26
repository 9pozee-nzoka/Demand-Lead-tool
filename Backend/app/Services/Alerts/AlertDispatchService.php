<?php

namespace App\Services\Alerts;

use App\Mail\OpportunityAlertMail;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Routes an Alert record to its configured delivery channel.
 *
 * Sprint 7: Dashboard channel is fully implemented (record already
 *           in DB — frontend polls /api/v1/alerts).
 * Sprint 9: Fill in SMS, email, WhatsApp, push, and webhook drivers.
 */
class AlertDispatchService
{
    public function dispatch(Alert $alert): void
    {
        $sent = match ($alert->channel) {
            'dashboard' => $this->dispatchDashboard($alert),
            'email'     => $this->dispatchEmail($alert),
            'sms'       => $this->dispatchSms($alert),
            'whatsapp'  => $this->dispatchWhatsApp($alert),
            'webhook'   => $this->dispatchWebhook($alert),
            default     => false,
        };

        if ($sent) {
            $alert->update(['status' => 'sent', 'sent_at' => now()]);
        } else {
            // Dashboard alerts are "sent" by definition — they live in the DB
            if ($alert->channel === 'dashboard') {
                $alert->update(['status' => 'sent', 'sent_at' => now()]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Channel drivers
    // -------------------------------------------------------------------------

    private function dispatchDashboard(Alert $alert): bool
    {
        // The alert record IS the dashboard notification — nothing to deliver.
        // The frontend reads /api/v1/alerts to display them.
        return true;
    }

    private function dispatchEmail(Alert $alert): bool
    {
        if (! $alert->recipient) {
            Log::warning("AlertDispatch: email alert #{$alert->id} has no recipient.");
            return false;
        }

        try {
            Mail::to($alert->recipient)->send(new OpportunityAlertMail($alert));
            return true;
        } catch (\Throwable $e) {
            Log::error("AlertDispatch: email failed for alert #{$alert->id}: " . $e->getMessage());
            return false;
        }
    }

    private function dispatchSms(Alert $alert): bool
    {
        if (! $alert->recipient) {
            return false;
        }

        $apiKey   = env('AT_API_KEY');
        $username = env('AT_USERNAME');

        if (! $apiKey || ! $username) {
            Log::warning("AlertDispatch: Africa's Talking credentials not set. Cannot send SMS.");
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'apiKey'       => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to'       => $alert->recipient,
                'message'  => substr($alert->message, 0, 160), // SMS max length
                'from'     => env('AT_SENDER_ID', 'DemandLead'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = data_get($data, 'SMSMessageData.Recipients.0.status', '');
                if (str_contains($status, 'Success')) {
                    Log::info("AlertDispatch: SMS sent to {$alert->recipient}");
                    return true;
                }
            }

            Log::warning("AlertDispatch: SMS failed for alert #{$alert->id}: " . $response->body());
            return false;

        } catch (\Throwable $e) {
            Log::error("AlertDispatch: SMS exception: " . $e->getMessage());
            return false;
        }
    }

    private function dispatchWhatsApp(Alert $alert): bool
    {
        if (! $alert->recipient) {
            return false;
        }

        // TODO Sprint 10: WhatsApp Business API integration
        // $waService = app(WhatsAppService::class);
        // $waService->sendTemplate($alert->recipient, 'opportunity_alert', $alert->payload);

        Log::info("AlertDispatch: [STUB] WhatsApp to {$alert->recipient}");
        return false;
    }

    private function dispatchWebhook(Alert $alert): bool
    {
        $url = $alert->payload['webhook_url'] ?? null;
        if (! $url) {
            return false;
        }

        // TODO Sprint 9: Add signature header + retry logic
        // Http::post($url, $alert->payload);

        Log::info("AlertDispatch: [STUB] Webhook to {$url}");
        return false;
    }
}
