@extends('emails.layouts.base')

@section('content')
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; color: #2563eb; font-size: 24px;">Email Verification</h1>
    </div>

    <p>Hello {{ $userName }},</p>

    <p>You have requested to verify your email address. Please use the following One-Time Password (OTP) to complete the verification process:</p>

    <div style="background-color: #f8f9fa; border: 2px dashed #2563eb; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
        <div style="font-size: 32px; font-weight: bold; color: #2563eb; letter-spacing: 8px; font-family: 'Courier New', monospace;">
            {{ $otp }}
        </div>
    </div>

    <div class="alert-box info">
        <strong>Important:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>This OTP code will expire in <strong>10 minutes</strong>.</li>
            <li>Do not share this code with anyone.</li>
            <li>If you did not request this verification, please ignore this email.</li>
        </ul>
    </div>

    <p>Enter this code in the verification page to complete your email verification.</p>
@endsection
