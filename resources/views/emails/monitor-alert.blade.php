@extends('emails.layouts.base')

@section('content')
    <div style="border-bottom: 2px solid #e0e0e0; padding-bottom: 20px; margin-bottom: 30px;">
        <h1 style="margin: 0 0 10px 0; color: #333333; font-size: 24px;">Monitor Alert</h1>
        <span class="status-badge status-{{ $status }}">
            @if($alertType === 'down')
                DOWN
            @elseif($alertType === 'up')
                UP
            @elseif($alertType === 'recovery')
                RECOVERED
            @else
                ALERT
            @endif
        </span>
    </div>

    <div class="info-section">
        <h3>{{ $monitor->name }}</h3>
        <div class="info-row">
            <span class="info-label">Service Type:</span>
            <span class="info-value">{{ $monitor->monitoringService->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Category:</span>
            <span class="info-value">{{ $monitor->serviceCategory->name ?? 'N/A' }}</span>
        </div>
        @if($monitor->url)
        <div class="info-row">
            <span class="info-label">URL:</span>
            <span class="info-value">
                <a href="{{ $monitor->url }}" target="_blank" style="color: #2563eb; text-decoration: none;">
                    {{ $monitor->url }}
                </a>
            </span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Check Interval:</span>
            <span class="info-value">{{ $monitor->check_interval }} minute(s)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Last Checked:</span>
            <span class="info-value">{{ $monitor->last_checked_at ? $monitor->last_checked_at->format('Y-m-d H:i:s') : 'Never' }}</span>
        </div>
        @if($responseTime !== null)
        <div class="info-row">
            <span class="info-label">Response Time:</span>
            <span class="info-value">{{ number_format($responseTime, 2) }} ms</span>
        </div>
        @endif
        @if($statusCode !== null)
        <div class="info-row">
            <span class="info-label">HTTP Status Code:</span>
            <span class="info-value">
                <span style="color: {{ $statusCode >= 200 && $statusCode < 300 ? '#28a745' : '#dc3545' }};">
                    {{ $statusCode }}
                </span>
            </span>
        </div>
        @endif
    </div>

    @if($message)
    <div class="alert-box warning">
        <strong>Alert Message:</strong><br>
        {{ $message }}
    </div>
    @endif

    @if($errorMessage)
    <div class="alert-box danger">
        <strong>Error Details:</strong><br>
        {{ $errorMessage }}
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('monitors.show', $monitor->uid) }}" class="button">
            View Monitor Details
        </a>
    </div>

    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0; color: #999; font-size: 11px;">
        <p style="margin: 5px 0;">
            Alert ID: {{ $alert->id }} | 
            Sent: {{ $alert->sent_at ? $alert->sent_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s') }}
        </p>
        <p style="margin: 5px 0;">
            You are receiving this because you have email alerts enabled for this monitor.
        </p>
    </div>
@endsection
