<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\User;
use App\Services\Alerts\AfricasTalkingSmsProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Alert Job
 * 
 * Delivers alerts via multiple channels:
 * - Email
 * - SMS (Africa's Talking)
 * - In-app notification
 * 
 * Queue: notifications
 * Sprint 9
 */
class SendAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    protected Alert $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
        $this->onQueue('notifications');
    }

    public function handle(AfricasTalkingSmsProvider $smsProvider): void
    {
        Log::info('Sending alert', [
            'alert_id' => $this->alert->id,
            'type' => $this->alert->alert_type,
            'channels' => $this->alert->channels,
        ]);

        $channels = $this->alert->channels ?? ['email', 'in_app'];
        $recipients = $this->getRecipients();

        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for alert', [
                'alert_id' => $this->alert->id,
            ]);
            
            $this->alert->update([
                'sent_at' => now(),
                'delivery_status' => 'no_recipients',
            ]);
            
            return;
        }

        $deliveryStatus = [];

        // Send email
        if (in_array('email', $channels)) {
            $deliveryStatus['email'] = $this->sendEmail($recipients);
        }

        // Send SMS
        if (in_array('sms', $channels) && $smsProvider->isConfigured()) {
            $deliveryStatus['sms'] = $this->sendSms($recipients, $smsProvider);
        }

        // Send in-app notification
        if (in_array('in_app', $channels)) {
            $deliveryStatus['in_app'] = $this->sendInApp($recipients);
        }

        // Update alert status
        $overallStatus = $this->determineOverallStatus($deliveryStatus);
        
        $this->alert->update([
            'sent_at' => now(),
            'delivery_status' => $overallStatus,
            'delivery_details' => $deliveryStatus,
        ]);

        Log::info('Alert sent successfully', [
            'alert_id' => $this->alert->id,
            'status' => $overallStatus,
            'recipients' => $recipients->count(),
        ]);
    }

    /**
     * Get alert recipients
     */
    protected function getRecipients()
    {
        $recipients = collect();

        // Get users based on alert rule recipients
        if ($this->alert->alertRule) {
            $recipientIds = $this->alert->alertRule->recipient_user_ids ?? [];
            
            if (!empty($recipientIds)) {
                $recipients = User::whereIn('id', $recipientIds)
                    ->where('organization_id', $this->alert->organization_id)
                    ->where('status', 'active')
                    ->get();
            }
        }

        // Fallback: org admins and owners
        if ($recipients->isEmpty()) {
            $recipients = User::where('organization_id', $this->alert->organization_id)
                ->where('status', 'active')
                ->whereIn('role', ['owner', 'admin'])
                ->get();
        }

        return $recipients;
    }

    /**
     * Send email notifications
     */
    protected function sendEmail($recipients): array
    {
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            if (empty($recipient->email)) {
                $failed++;
                continue;
            }

            try {
                Mail::send('emails.alert', [
                    'alert' => $this->alert,
                    'recipient' => $recipient,
                ], function($message) use ($recipient) {
                    $message->to($recipient->email, $recipient->name)
                            ->subject("Alert: {$this->alert->title}");
                });

                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to send alert email', [
                    'alert_id' => $this->alert->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'status' => $failed === 0 ? 'success' : ($sent > 0 ? 'partial' : 'failed'),
        ];
    }

    /**
     * Send SMS notifications
     */
    protected function sendSms($recipients, AfricasTalkingSmsProvider $smsProvider): array
    {
        $phones = $recipients->filter(fn($u) => !empty($u->phone))
                            ->pluck('phone')
                            ->toArray();

        if (empty($phones)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'status' => 'no_phones',
            ];
        }

        $details = [
            'keyword' => $this->alert->keyword?->term ?? '',
            'score' => $this->alert->opportunity?->opportunity_score ?? '',
            'growth' => $this->alert->keyword?->growth_rate_7d ?? '',
        ];

        $result = $smsProvider->sendAlert(
            implode(',', $phones),
            $this->alert->alert_type,
            $this->alert->title,
            array_filter($details)
        );

        return [
            'sent' => $result['success'] ? count($phones) : 0,
            'failed' => $result['success'] ? 0 : count($phones),
            'status' => $result['success'] ? 'success' : 'failed',
            'error' => $result['error'] ?? null,
        ];
    }

    /**
     * Send in-app notifications
     */
    protected function sendInApp($recipients): array
    {
        $sent = 0;

        foreach ($recipients as $recipient) {
            try {
                $recipient->notifications()->create([
                    'type' => 'App\\Notifications\\AlertNotification',
                    'data' => [
                        'alert_id' => $this->alert->id,
                        'alert_type' => $this->alert->alert_type,
                        'title' => $this->alert->title,
                        'message' => $this->alert->message,
                        'url' => $this->getAlertUrl(),
                    ],
                    'read_at' => null,
                ]);

                $sent++;
            } catch (\Exception $e) {
                Log::error('Failed to create in-app notification', [
                    'alert_id' => $this->alert->id,
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $recipients->count() - $sent,
            'status' => $sent > 0 ? 'success' : 'failed',
        ];
    }

    /**
     * Get URL for alert
     */
    protected function getAlertUrl(): string
    {
        if ($this->alert->opportunity_id) {
            return route('opportunities.show', $this->alert->opportunity_id);
        }

        if ($this->alert->keyword_id) {
            return route('keywords.show', $this->alert->keyword_id);
        }

        return route('dashboard');
    }

    /**
     * Determine overall delivery status
     */
    protected function determineOverallStatus(array $deliveryStatus): string
    {
        $allSuccess = true;
        $anySuccess = false;

        foreach ($deliveryStatus as $status) {
            if ($status['status'] === 'success') {
                $anySuccess = true;
            } else {
                $allSuccess = false;
            }
        }

        if ($allSuccess) {
            return 'sent';
        } elseif ($anySuccess) {
            return 'partial';
        } else {
            return 'failed';
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAlert job failed', [
            'alert_id' => $this->alert->id,
            'error' => $exception->getMessage(),
        ]);

        $this->alert->update([
            'delivery_status' => 'failed',
            'delivery_details' => [
                'error' => $exception->getMessage(),
                'failed_at' => now()->toDateTimeString(),
            ],
        ]);
    }
}
