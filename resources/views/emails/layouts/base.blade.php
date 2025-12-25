<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Email' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-header {
            background-color: #2563eb;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .alert-box {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-box.info {
            background-color: #eff6ff;
            border-left-color: #2563eb;
            color: #1e40af;
        }
        .alert-box.warning {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .alert-box.danger {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .alert-box.success {
            background-color: #d1e7dd;
            border-left-color: #28a745;
            color: #155724;
        }
        .info-section {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-section h3 {
            margin: 0 0 15px 0;
            color: #333333;
            font-size: 18px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666666;
        }
        .info-value {
            color: #333333;
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-primary {
            background-color: #2563eb;
            color: #ffffff;
        }
        .badge-success {
            background-color: #28a745;
            color: #ffffff;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #333333;
        }
        .badge-danger {
            background-color: #dc3545;
            color: #ffffff;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .button-box {
            text-align: center;
            margin: 30px 0;
        }
        .url-box {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 12px;
            color: #666666;
            font-family: 'Courier New', monospace;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .status-badge.down {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .status-badge.up {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        .status-badge.recovery {
            background-color: #eef;
            color: #33c;
            border: 1px solid #ccf;
        }
        .email-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #666666;
            font-size: 12px;
        }
        .email-footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        @if(isset($showHeader) && $showHeader)
        <div class="email-header">
            <h1>{{ $headerTitle ?? config('app.name') }}</h1>
        </div>
        @endif
        
        <div class="email-body">
            @yield('content')
        </div>
        
        <div class="email-footer">
            <p>This is an automated email from {{ config('app.name') }}. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>



