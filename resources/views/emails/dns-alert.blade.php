@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">DNS Monitoring Alert</h1>
    </div>
    
    <p>Hello {{ $user->name }},</p>
    
    <div class="alert-box {{ $alert->alert_type === 'missing' || $alert->alert_type === 'error' ? 'danger' : ($alert->alert_type === 'changed' ? 'warning' : 'success') }}">
        <strong>
            @if($alert->alert_type === 'changed')
                DNS Records Changed
            @elseif($alert->alert_type === 'missing')
                DNS Records Missing
            @elseif($alert->alert_type === 'error')
                DNS Check Error
            @else
                DNS Records Recovered
            @endif
        </strong>
        <p style="margin: 10px 0 0 0;">{{ $alert->message }}</p>
    </div>

    <div class="info-section">
        <h3>Monitor Information</h3>
        <div class="info-row">
            <span class="info-label">Monitor Name:</span>
            <span class="info-value">{{ $monitor->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Domain:</span>
            <span class="info-value"><strong>{{ $monitor->domain }}</strong></span>
        </div>
        @if($alert->record_type)
        <div class="info-row">
            <span class="info-label">Record Type:</span>
            <span class="info-value"><code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;">{{ $alert->record_type }}</code></span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                @if($monitor->status === 'healthy')
                    <span class="badge badge-success">Healthy</span>
                @elseif($monitor->status === 'changed')
                    <span class="badge badge-warning">Changed</span>
                @elseif($monitor->status === 'missing')
                    <span class="badge badge-danger">Missing</span>
                @elseif($monitor->status === 'error')
                    <span class="badge badge-danger">Error</span>
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

    @php
        $changedRecords = is_array($alert->changed_records) ? $alert->changed_records : (is_string($alert->changed_records) ? json_decode($alert->changed_records, true) : []);
    @endphp
    @if(!empty($changedRecords))
    <div class="info-section">
        <h3>DNS Record Changes</h3>
        @if(!empty($changedRecords['added']))
            <h4 style="margin: 15px 0 10px 0; color: #333;">Added Records:</h4>
            @foreach($changedRecords['added'] as $record)
                <div style="padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #2563eb; border-radius: 3px;">
                    <strong>{{ $record['type'] ?? 'N/A' }}</strong>: {{ json_encode($record) }}
                </div>
            @endforeach
        @endif
        @if(!empty($changedRecords['removed']))
            <h4 style="margin: 15px 0 10px 0; color: #333;">Removed Records:</h4>
            @foreach($changedRecords['removed'] as $record)
                <div style="padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 3px;">
                    <strong>{{ $record['type'] ?? 'N/A' }}</strong>: {{ json_encode($record) }}
                </div>
            @endforeach
        @endif
        @if(!empty($changedRecords['modified']))
            <h4 style="margin: 15px 0 10px 0; color: #333;">Modified Records:</h4>
            @foreach($changedRecords['modified'] as $change)
                <div style="padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 3px solid #ffc107; border-radius: 3px;">
                    <strong>Old:</strong> {{ json_encode($change['old'] ?? []) }}<br>
                    <strong>New:</strong> {{ json_encode($change['new'] ?? []) }}
                </div>
            @endforeach
        @endif
    </div>
    @endif

    <p style="margin-top: 30px;">
        <strong>Action Required:</strong> Please review the DNS changes and verify they are intentional. Unauthorized DNS changes could indicate a security issue.
    </p>

    <p style="margin-top: 20px;">
        You can view and manage your DNS monitor by visiting your dashboard.
    </p>
@endsection
