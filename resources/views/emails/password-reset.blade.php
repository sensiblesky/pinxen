@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">Password Reset Request</h1>
    </div>

    <p>Hello {{ $userName }},</p>

    <p>You are receiving this email because we received a password reset request for your account.</p>

    <div class="button-box">
        <a href="{{ $url }}" class="button">Reset Password</a>
    </div>

    <div class="alert-box info">
        <strong>Important:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This password reset link will expire in <strong>{{ $expire }} minutes</strong>.</li>
            <li>If you did not request a password reset, no further action is required.</li>
            <li>Do not share this link with anyone.</li>
        </ul>
    </div>

    <p>If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>

    <div class="url-box">
        {{ $url }}
    </div>

    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
@endsection
