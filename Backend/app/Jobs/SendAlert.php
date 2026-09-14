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
 * Delivers a single alert record via its configured channel:
 *   - email
 *   - sms  (Africa's Talking)
 *   - dashboard / push  (in-app notification)
 *
 * Queue: notifications
 */
class SendAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int    $timeout = 60;
    public int    $tries   = 3;
    public array  $backoff = [60, 300, 900];

    public function __construct(protected Alert $alert)
    {
        $this->onQueue('notifications');
    }

    public function handle(AfricasTalkingSmsProvider $smsProvider): void
    {
        Log::info('Sending alert', [
            'alert_id' => $this->alert->id,
            'type'     => $this->alert->type,
            'channel'  => $this->alert->channel,
        ]);

        $success = match ($this->alert->channel) {
            'email'                => $this->sendEmail(),
            'sms'                  => $this->sendSms($smsProvider),
            'dashboard', 'push'    => $this->sendInApp(),
            'whatsapp'             => $this->logUnsupported('whatsapp'),
            'webhook'              => $this->logUnsupported('webhook'),
            default                => $this->logUnsupported($this->alert->channel),
        };

        $this->alert->update([
            'status'  => $success ? 'sent' : 'failed',
            'sent_at' => now(),
        ]);
    }

    // -------------------------------------------------------------------------

    protected function sendEmail(): bool
    {
        $recipient = $this->alert->recipient;

        if (empty($recipient)) {
            Log::warning('SendAlert: no recipient email', ['alert_id' => $this->alert->id]);
            return false;
        }

        try {
            Mail::send(
                'emails.alert',
                ['alert' => $this->alert],
                fn ($m) => $m->to($recipient)->subject($this->buildSubject())
            );
            return true;
        } catch (\Exception $e) {
            Log::error('SendAlert: email failed', [
                'alert_id' => $this->alert->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function sendSms(AfricasTalkingSmsProvider $smsProvider): bool
    {
        if (! $smsProvider->isConfigured()) {
            Log::info('SendAlert: SMS provider not configured', ['alert_id' => $this->alert->id]);
            return false;
        }

        $phone = $this->alert->recipient;

        if (empty($phone)) {
            return false;
        }

        $result = $smsProvider->send($phone, $this->alert->message);

        if (! ($result['success'] ?? false)) {
            Log::error('SendAlert: SMS failed', [
                'alert_id' => $this->alert->id,
                'error'    => $result['error'] ?? 'unknown',
            ]);
        }

        return $result['success'] ?? false;
    }

    protected function sendInApp(): bool
    {
        // Find the user matching the recipient (email)
        $user = User::where('email', $this->alert->recipient)
            ->where('organization_id', $this->alert->organization_id)
            ->first();

        if (! $user) {
            return false;
        }

        try {
            $user->notifications()->create([
                'type'     => 'App\\Notifications\\AlertNotification',
                'data'     => [
                    'alert_id' => $this->alert->id,
                    'type'     => $this->alert->type,
                    'message'  => $this->alert->message,
                    'url'      => $this->buildUrl(),
                ],
                'read_at'  => null,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('SendAlert: in-app notification failed', [
                'alert_id' => $this->alert->id,
                'error'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function logUnsupported(string $channel): bool
    {
        Log::info("SendAlert: channel '{$channel}' not implemented yet", [
            'alert_id' => $this->alert->id,
        ]);
        return false;
    }

    // -------------------------------------------------------------------------

    protected function buildSubject(): string
    {
        $typeLabel = ucfirst(str_replace('_', ' ', $this->alert->type));
        return "DemandLead Alert: {$typeLabel}";
    }

    protected function buildUrl(): string
    {
        if ($this->alert->opportunity_id) {
            return route('opportunities.show', $this->alert->opportunity_id);
        }

        if ($this->alert->lead_id) {
            return route('leads.show', $this->alert->lead_id);
        }

        return route('alerts.index');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAlert job failed permanently', [
            'alert_id' => $this->alert->id,
            'error'    => $exception->getMessage(),
        ]);

        $this->alert->update(['status' => 'failed']);
    }
}
