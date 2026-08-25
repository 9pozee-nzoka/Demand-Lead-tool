<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Demand Alert</title>
  <style>
    body { font-family: 'Inter', Arial, sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #0f172a; }
    .wrapper { max-width: 540px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
    .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 28px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
    .header p { color: rgba(255,255,255,.8); margin: 6px 0 0; font-size: 14px; }
    .body { padding: 28px 32px; }
    .score-badge { display: inline-block; background: #eef2ff; color: #4f46e5; font-size: 28px; font-weight: 800; padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
    .score-label { font-size: 13px; font-weight: 600; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; margin-left: 8px; }
    .detail { background: #f8fafc; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #64748b; font-weight: 500; }
    .detail-value { font-weight: 600; }
    .message { font-size: 14px; line-height: 1.6; color: #374151; margin-bottom: 24px; }
    .cta { display: inline-block; background: #4f46e5; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
    .footer { padding: 16px 32px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <h1>🚀 Demand Opportunity Detected</h1>
      <p>DemandLead AI Intelligence Platform</p>
    </div>
    <div class="body">
      <div>
        <span class="score-badge">{{ number_format($payload['opportunity_score'] ?? 0) }}</span>
        <span class="score-label">{{ $payload['label'] ?? '' }}</span>
      </div>

      <div class="detail">
        @if (!empty($payload['keyword']))
          <div class="detail-row">
            <span class="detail-label">Keyword</span>
            <span class="detail-value">{{ $payload['keyword'] }}</span>
          </div>
        @endif
        @if (!empty($payload['location']))
          <div class="detail-row">
            <span class="detail-label">Location</span>
            <span class="detail-value">{{ $payload['location'] }}</span>
          </div>
        @endif
        @if (!empty($payload['trend_state']))
          <div class="detail-row">
            <span class="detail-label">Trend State</span>
            <span class="detail-value">{{ ucwords(str_replace('_', ' ', $payload['trend_state'])) }}</span>
          </div>
        @endif
      </div>

      <p class="message">{{ $alert->message }}</p>

      <a href="{{ config('app.url') }}/opportunities" class="cta">View Opportunity →</a>
    </div>
    <div class="footer">
      You are receiving this alert because it matched one of your configured rules.
      To manage alert rules, visit your DemandLead dashboard.
    </div>
  </div>
</body>
</html>
