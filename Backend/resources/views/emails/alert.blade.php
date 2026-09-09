<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $alert->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #fff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .alert-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .alert-spike { background: #fee2e2; color: #991b1b; }
        .alert-opportunity { background: #dcfce7; color: #166534; }
        .alert-threshold { background: #fef3c7; color: #92400e; }
        .alert-info { background: #dbeafe; color: #1e40af; }
        .details {
            background: #f9fafb;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .details dt {
            font-weight: 600;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .details dd {
            margin: 0 0 16px 0;
            font-size: 16px;
            color: #111827;
        }
        .cta {
            display: inline-block;
            background: #7c3aed;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta:hover {
            background: #6d28d9;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .metrics {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        .metric {
            flex: 1;
            text-align: center;
            padding: 16px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
        }
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 {{ $alert->title }}</h1>
    </div>

    <div class="content">
        <span class="alert-type alert-{{ str_replace('_', '-', $alert->alert_type) }}">
            {{ ucwords(str_replace('_', ' ', $alert->alert_type)) }}
        </span>

        <p style="font-size: 16px; margin: 20px 0;">
            {{ $alert->message }}
        </p>

        @if($alert->keyword)
        <div class="details">
            <dl>
                <dt>Keyword</dt>
                <dd>{{ $alert->keyword->term }}</dd>

                @if($alert->keyword->project)
                <dt>Project</dt>
                <dd>{{ $alert->keyword->project->name }}</dd>
                @endif

                @if($alert->keyword->trend_state)
                <dt>Trend State</dt>
                <dd>{{ ucfirst($alert->keyword->trend_state) }}</dd>
                @endif
            </dl>
        </div>

        <div class="metrics">
            @if($alert->keyword->current_interest)
            <div class="metric">
                <div class="metric-value">{{ number_format($alert->keyword->current_interest, 0) }}</div>
                <div class="metric-label">Current Interest</div>
            </div>
            @endif

            @if($alert->keyword->growth_rate_7d)
            <div class="metric">
                <div class="metric-value">{{ number_format($alert->keyword->growth_rate_7d, 1) }}%</div>
                <div class="metric-label">7-Day Growth</div>
            </div>
            @endif

            @if($alert->keyword->growth_rate_30d)
            <div class="metric">
                <div class="metric-value">{{ number_format($alert->keyword->growth_rate_30d, 1) }}%</div>
                <div class="metric-label">30-Day Growth</div>
            </div>
            @endif
        </div>
        @endif

        @if($alert->opportunity)
        <div class="details">
            <dl>
                <dt>Opportunity</dt>
                <dd>{{ $alert->opportunity->title }}</dd>

                @if($alert->opportunity->opportunity_score)
                <dt>Opportunity Score</dt>
                <dd>{{ number_format($alert->opportunity->opportunity_score, 0) }}/100 - {{ $alert->opportunity->scoreLabel() }}</dd>
                @endif
            </dl>
        </div>
        @endif

        <a href="{{ $alert->opportunity_id ? route('opportunities.show', $alert->opportunity_id) : ($alert->keyword_id ? route('keywords.show', $alert->keyword_id) : route('dashboard')) }}" class="cta">
            View Details →
        </a>

        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            This alert was triggered by <strong>{{ $alert->alertRule->name ?? 'System' }}</strong>.
        </p>
    </div>

    <div class="footer">
        <p>
            You received this alert because you're monitoring demand signals in your organization.<br>
            <a href="{{ route('alerts.index') }}" style="color: #7c3aed;">Manage Alert Settings</a>
        </p>
        <p style="margin-top: 20px;">
            © {{ date('Y') }} DemandLead. All rights reserved.
        </p>
    </div>
</body>
</html>
