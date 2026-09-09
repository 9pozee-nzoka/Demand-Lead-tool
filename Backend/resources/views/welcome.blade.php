<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SoarCorp Demand Intelligence — API</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        .card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 16px;
            padding: 40px 48px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }
        p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            background: rgba(99,102,241,.2);
            color: #818cf8;
            border: 1px solid rgba(99,102,241,.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #4ade80;
        }
        .dot {
            width: 8px; height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.4; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚡</div>
        <h1>SoarCorp Demand Intelligence</h1>
        <p>This is the REST API backend.<br>
           The web application is at
           <strong style="color:#a5b4fc">app.soarcorp.co.ke</strong>
        </p>
        <span class="badge">API v1</span>
        <div class="status">
            <div class="dot"></div>
            API operational &mdash; Laravel {{ app()->version() }}
        </div>
    </div>
</body>
</html>
