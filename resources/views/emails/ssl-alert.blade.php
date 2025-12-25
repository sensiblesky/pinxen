@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">SSL Certificate Alert</h1>
    </div>
    
    <p>Hello {{ $user->name }},</p>
    
    <div class="alert-box {{ $alert->alert_type === 'expired' ? 'danger' : ($alert->alert_type === 'invalid' ? 'danger' : ($alert->alert_type === 'expiring_soon' ? 'warning' : 'success')) }}">
        <strong>
            @if($alert->alert_type === 'expired')
                CRITICAL: SSL Certificate Expired
            @elseif($alert->alert_type === 'invalid')
                SSL Certificate Invalid
            @elseif($alert->alert_type === 'expiring_soon')
                SSL Certificate Expiring Soon
            @else
                SSL Certificate Recovered
            @endif
        </strong>
        <p style="margin: 10px 0 0 0;">{{ $alert->message }}</p>
    </div>

    <div class="info-section">
        <h3>SSL Certificate Information</h3>
        <div class="info-row">
            <span class="info-label">Monitor Name:</span>
            <span class="info-value">{{ $monitor->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Domain:</span>
            <span class="info-value"><strong>{{ $monitor->domain }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Certificate Status:</span>
            <span class="info-value">
                @if($monitor->status === 'valid')
                    <span class="badge badge-success">Valid</span>
                @elseif($monitor->status === 'expired')
                    <span class="badge badge-danger">Expired</span>
                @elseif($monitor->status === 'invalid')
                    <span class="badge badge-danger">Invalid</span>
                @elseif($monitor->status === 'expiring_soon')
                    <span class="badge badge-warning">Expiring Soon</span>
                @else
                    <span style="color: #666;">Unknown</span>
                @endif
            </span>
        </div>
        @if($monitor->expiration_date)
        <div class="info-row">
            <span class="info-label">Expiration Date:</span>
            <span class="info-value">{{ $monitor->expiration_date->format('F d, Y') }}</span>
        </div>
        @endif
        @if($monitor->days_until_expiration !== null)
        <div class="info-row">
            <span class="info-label">Days Until Expiration:</span>
            <span class="info-value">
                @if($monitor->days_until_expiration < 0)
                    <span class="badge badge-danger">Expired {{ abs($monitor->days_until_expiration) }} days ago</span>
                @elseif($monitor->days_until_expiration <= 30)
                    <span class="badge badge-warning">{{ $monitor->days_until_expiration }} days</span>
                @else
                    <span class="badge badge-success">{{ $monitor->days_until_expiration }} days</span>
                @endif
            </span>
        </div>
        @endif
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
        <strong>Action Required:</strong> Please check and renew your SSL certificate if necessary to avoid security issues.
    </p>

    <p style="margin-top: 20px;">
        You can view and manage your SSL monitor by visiting your dashboard.
    </p>
@endsection
