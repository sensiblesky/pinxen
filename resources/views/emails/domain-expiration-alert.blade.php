@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">Domain Expiration Alert</h1>
    </div>
    
    <p>Hello {{ $user->name }},</p>
    
    <div class="alert-box {{ $alert->alert_type === 'expired' ? 'danger' : ($alert->alert_type === '5_days' ? 'danger' : 'warning') }}">
        <strong>
            @if($alert->alert_type === 'expired')
                CRITICAL: Domain Expired
            @elseif($alert->alert_type === '5_days')
                URGENT: Domain Expiring Soon
            @elseif($alert->alert_type === '30_days')
                Domain Expiration Warning
            @else
                Domain Expiration Reminder
            @endif
        </strong>
        <p style="margin: 10px 0 0 0;">{{ $alert->message }}</p>
    </div>

    <div class="info-section">
        <h3>Domain Information</h3>
        <div class="info-row">
            <span class="info-label">Monitor Name:</span>
            <span class="info-value">{{ $monitor->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Domain:</span>
            <span class="info-value"><strong>{{ $monitor->domain }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Expiration Date:</span>
            <span class="info-value">
                @if($monitor->expiration_date)
                    {{ $monitor->expiration_date->format('F d, Y') }}
                @else
                    <span style="color: #666;">Not set</span>
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Days Until Expiration:</span>
            <span class="info-value">
                @if($monitor->days_until_expiration !== null)
                    @if($monitor->days_until_expiration < 0)
                        <span class="badge badge-danger">Expired {{ abs($monitor->days_until_expiration) }} days ago</span>
                    @elseif($monitor->days_until_expiration <= 5)
                        <span class="badge badge-danger">{{ $monitor->days_until_expiration }} days</span>
                    @elseif($monitor->days_until_expiration <= 30)
                        <span class="badge badge-warning">{{ $monitor->days_until_expiration }} days</span>
                    @else
                        <span class="badge badge-success">{{ $monitor->days_until_expiration }} days</span>
                    @endif
                @else
                    <span style="color: #666;">Unknown</span>
                @endif
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Last Checked:</span>
            <span class="info-value">
                @if($monitor->last_checked_at)
                    {{ $monitor->last_checked_at->format('M d, Y H:i') }}
                @else
                    <span style="color: #666;">Never</span>
                @endif
            </span>
        </div>
    </div>

    <p style="margin-top: 30px;">
        <strong>Action Required:</strong> Please renew your domain before the expiration date to avoid service interruption.
    </p>

    <p style="margin-top: 20px;">
        You can view and manage your domain monitor by visiting your dashboard.
    </p>
@endsection
