<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DemandLead Alert</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 20px; background: #dbeafe; color: #1e40af; }
        .details { background: #f9fafb; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .details dt { font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; margin-bottom: 4px; }
        .details dd { margin: 0 0 16px 0; font-size: 16px; color: #111827; }
        .cta { display: inline-block; background: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
        .metrics { display: flex; gap: 20px; margin: 20px 0; }
        .metric { flex: 1; text-align: center; padding: 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; }
        .metric-value { font-size: 28px; font-weight: bold; color: #7c3aed; }
        .metric-label { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 DemandLead Alert</h1>
    </div>

    <div class="content">
        <span class="badge">{{ ucwords(str_replace('_', ' ', $alert->type)) }}</span>

        <p style="font-size: 16px; margin: 20px 0;">
            {{ $alert->message }}
        </p>

        @if($alert->opportunity)
        <div class="details">
            <dl>
                <dt>Opportunity</dt>
                <dd>{{ $alert->opportunity->title }}</dd>

                @if($alert->opportunity->opportunity_score)
                <dt>Opportunity Score</dt>
                <dd>{{ number_format($alert->opportunity->opportunity_score, 0) }}/100</dd>
                @endif

                @if($alert->opportunity->keyword)
                <dt>Keyword</dt>
                <dd>{{ $alert->opportunity->keyword->keyword }}</dd>
                @endif
            </dl>
        </div>

        @if($alert->opportunity->keyword)
        <div class="metrics">
            @if($alert->opportunity->keyword->current_interest)
            <div class="metric">
                <div class="metric-value">{{ number_format($alert->opportunity->keyword->current_interest, 0) }}</div>
                <div class="metric-label">Current Interest</div>
            </div>
            @endif

            @if($alert->opportunity->keyword->growth_rate_7d)
            <div class="metric">
                <div class="metric-value">+{{ number_format($alert->opportunity->keyword->growth_rate_7d, 1) }}%</div>
                <div class="metric-label">7-Day Growth</div>
            </div>
            @endif
        </div>
        @endif
        @endif

        @if($alert->lead)
        <div class="details">
            <dl>
                <dt>Lead</dt>
                <dd>{{ $alert->lead->first_name }} {{ $alert->lead->last_name }}</dd>
                @if($alert->lead->company)
                <dt>Company</dt>
                <dd>{{ $alert->lead->company }}</dd>
                @endif
            </dl>
        </div>
        @endif

        <a href="{{ $alert->opportunity_id ? route('opportunities.show', $alert->opportunity_id) : ($alert->lead_id ? route('leads.show', $alert->lead_id) : route('alerts.index')) }}" class="cta">
            View Details →
        </a>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            This alert was triggered by <strong>{{ $alert->rule->name ?? 'System' }}</strong>.
        </p>
    </div>

    <div class="footer">
        <p>
            You received this alert because you're monitoring demand signals.<br>
            <a href="{{ route('alerts.index') }}" style="color: #7c3aed;">Manage Alert Settings</a>
        </p>
        <p style="margin-top: 20px;">© {{ date('Y') }} DemandLead. All rights reserved.</p>
    </div>
</body>
</html>
