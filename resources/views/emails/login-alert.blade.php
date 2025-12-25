@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">New Login Alert</h1>
    </div>

    <p>Hello {{ $user->name ?? $user->email }},</p>

    <p>We detected a new login to your account. If this was you, you can safely ignore this email.</p>

    <div class="alert-box warning">
        <strong>Security Notice:</strong> If you did not log in, please secure your account immediately by changing your password.
    </div>

    <div class="info-section">
        <h3>Login Details</h3>
        
        <div class="info-row">
            <span class="info-label">Login Time:</span>
            <span class="info-value">{{ $loginTime }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">IP Address:</span>
            <span class="info-value">{{ $ipAddress }}</span>
        </div>
        
        @if($location)
        <div class="info-row">
            <span class="info-label">Location:</span>
            <span class="info-value">
                @if(!empty($location['city']))
                    {{ $location['city'] }}, 
                @endif
                @if(!empty($location['regionName']))
                    {{ $location['regionName'] }}, 
                @endif
                {{ $location['country'] ?? 'Unknown' }}
            </span>
        </div>
        @endif
        
        <div class="info-row">
            <span class="info-label">Device Type:</span>
            <span class="info-value">{{ ucfirst($deviceType) }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Browser:</span>
            <span class="info-value">{{ $browser }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Platform:</span>
            <span class="info-value">{{ $platform }}</span>
        </div>
    </div>

    @if(!$location || empty($location['country']) || $location['country'] === 'Unknown')
    <div class="alert-box info">
        <strong>Location Information:</strong> We were unable to determine the exact location of this login. This may be due to a VPN, proxy, or network configuration.
    </div>
    @endif

    <p>If you recognize this login, no action is needed. If you don't recognize this activity, please:</p>
    <ul>
        <li>Change your password immediately</li>
        <li>Review your account security settings</li>
        <li>Contact support if you have concerns</li>
    </ul>
@endsection
