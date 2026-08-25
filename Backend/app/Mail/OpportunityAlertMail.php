<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpportunityAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Alert $alert) {}

    public function envelope(): Envelope
    {
        $keyword = $alert->payload['keyword'] ?? 'Demand Opportunity';
        $score   = $alert->payload['opportunity_score'] ?? 0;
        $label   = $alert->payload['label'] ?? '';

        return new Envelope(
            subject: "🚀 {$label} opportunity: {$keyword} (Score {$score})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.opportunity-alert', with: [
            'alert'   => $this->alert,
            'payload' => $this->alert->payload,
        ]);
    }
}
