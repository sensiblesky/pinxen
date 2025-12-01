@extends('layouts.master')

@section('styles')
    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.css')}}">
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="page-header-breadcrumb mb-3">
        <div class="d-flex align-center justify-content-between flex-wrap">
            <h1 class="page-title fw-medium fs-18 mb-0">Communication Channels</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('panel') }}">Panel</a></li>
                <li class="breadcrumb-item active" aria-current="page">Comm Channels</li>
            </ol>
        </div>
    </div>
    <!-- End::page-header -->

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Start::row-1 -->
    <form action="{{ route('panel.comm-channels.update') }}" method="POST" id="comm-channels-form">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card">
                    <div class="card-header">
                        <div class="card-title">Communication Channels Configuration</div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3 border-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#smtp-tab" aria-selected="true">
                                    <i class="ri-mail-line me-1"></i>SMTP
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#sms-tab" aria-selected="false">
                                    <i class="ri-message-3-line me-1"></i>SMS
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#whatsapp-tab" aria-selected="false">
                                    <i class="ri-whatsapp-line me-1"></i>WhatsApp
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#telegram-tab" aria-selected="false">
                                    <i class="ri-telegram-line me-1"></i>Telegram
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" role="tab" href="#discord-tab" aria-selected="false">
                                    <i class="ri-discord-line me-1"></i>Discord
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- SMTP Tab -->
                            <div class="tab-pane fade show active" id="smtp-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="smtp_driver" class="form-label">SMTP Driver</label>
                                        <select class="form-control @error('smtp_driver') is-invalid @enderror" id="smtp_driver" name="smtp_driver" data-trigger>
                                            <option value="">Select Driver</option>
                                            <option value="smtp" {{ old('smtp_driver', $settings['smtp_driver'] ?? '') == 'smtp' ? 'selected' : '' }}>SMTP</option>
                                            <option value="sendmail" {{ old('smtp_driver', $settings['smtp_driver'] ?? '') == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                            <option value="mailgun" {{ old('smtp_driver', $settings['smtp_driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                                            <option value="ses" {{ old('smtp_driver', $settings['smtp_driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
                                            <option value="postmark" {{ old('smtp_driver', $settings['smtp_driver'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
                                        </select>
                                        @error('smtp_driver')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control @error('smtp_host') is-invalid @enderror" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}" placeholder="smtp.example.com">
                                        @error('smtp_host')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control @error('smtp_port') is-invalid @enderror" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', $settings['smtp_port'] ?? '') }}" placeholder="587" min="1" max="65535">
                                        @error('smtp_port')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_encryption" class="form-label">Encryption</label>
                                        <select class="form-control @error('smtp_encryption') is-invalid @enderror" id="smtp_encryption" name="smtp_encryption" data-trigger>
                                            <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                            <option value="none" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : '' }}>None</option>
                                        </select>
                                        @error('smtp_encryption')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control @error('smtp_username') is-invalid @enderror" id="smtp_username" name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}" placeholder="your-email@example.com">
                                        @error('smtp_username')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('smtp_password') is-invalid @enderror" id="smtp_password" name="smtp_password" value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}" placeholder="Enter password">
                                            <button class="btn btn-light" type="button" id="toggle_smtp_password">
                                                <i class="ri-eye-line" id="smtp_password_icon"></i>
                                            </button>
                                        </div>
                                        @error('smtp_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current password</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_from_address" class="form-label">From Address</label>
                                        <input type="email" class="form-control @error('smtp_from_address') is-invalid @enderror" id="smtp_from_address" name="smtp_from_address" value="{{ old('smtp_from_address', $settings['smtp_from_address'] ?? '') }}" placeholder="noreply@example.com">
                                        @error('smtp_from_address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="smtp_from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control @error('smtp_from_name') is-invalid @enderror" id="smtp_from_name" name="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name'] ?? '') }}" placeholder="Your App Name">
                                        @error('smtp_from_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- SMS Tab -->
                            <div class="tab-pane fade" id="sms-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="sms_provider" class="form-label">SMS Provider</label>
                                        <select class="form-control @error('sms_provider') is-invalid @enderror" id="sms_provider" name="sms_provider" data-trigger>
                                            <option value="">Select Provider</option>
                                            <option value="twilio" {{ old('sms_provider', $settings['sms_provider'] ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                            <option value="nexmo" {{ old('sms_provider', $settings['sms_provider'] ?? '') == 'nexmo' ? 'selected' : '' }}>Vonage (Nexmo)</option>
                                            <option value="aws" {{ old('sms_provider', $settings['sms_provider'] ?? '') == 'aws' ? 'selected' : '' }}>AWS SNS</option>
                                        </select>
                                        @error('sms_provider')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="sms_api_key" class="form-label">API Key</label>
                                        <input type="text" class="form-control @error('sms_api_key') is-invalid @enderror" id="sms_api_key" name="sms_api_key" value="{{ old('sms_api_key', $settings['sms_api_key'] ?? '') }}" placeholder="Your API Key">
                                        @error('sms_api_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="sms_api_secret" class="form-label">API Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('sms_api_secret') is-invalid @enderror" id="sms_api_secret" name="sms_api_secret" value="{{ old('sms_api_secret', $settings['sms_api_secret'] ?? '') }}" placeholder="Your API Secret">
                                            <button class="btn btn-light" type="button" id="toggle_sms_secret">
                                                <i class="ri-eye-line" id="sms_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('sms_api_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="sms_from_number" class="form-label">From Number</label>
                                        <input type="text" class="form-control @error('sms_from_number') is-invalid @enderror" id="sms_from_number" name="sms_from_number" value="{{ old('sms_from_number', $settings['sms_from_number'] ?? '') }}" placeholder="+1234567890">
                                        @error('sms_from_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp Tab -->
                            <div class="tab-pane fade" id="whatsapp-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="whatsapp_provider" class="form-label">WhatsApp Provider</label>
                                        <select class="form-control @error('whatsapp_provider') is-invalid @enderror" id="whatsapp_provider" name="whatsapp_provider" data-trigger>
                                            <option value="">Select Provider</option>
                                            <option value="twilio" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                            <option value="whatsapp_business" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? '') == 'whatsapp_business' ? 'selected' : '' }}>WhatsApp Business API</option>
                                        </select>
                                        @error('whatsapp_provider')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="whatsapp_api_key" class="form-label">API Key</label>
                                        <input type="text" class="form-control @error('whatsapp_api_key') is-invalid @enderror" id="whatsapp_api_key" name="whatsapp_api_key" value="{{ old('whatsapp_api_key', $settings['whatsapp_api_key'] ?? '') }}" placeholder="Your API Key">
                                        @error('whatsapp_api_key')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="whatsapp_api_secret" class="form-label">API Secret</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('whatsapp_api_secret') is-invalid @enderror" id="whatsapp_api_secret" name="whatsapp_api_secret" value="{{ old('whatsapp_api_secret', $settings['whatsapp_api_secret'] ?? '') }}" placeholder="Your API Secret">
                                            <button class="btn btn-light" type="button" id="toggle_whatsapp_secret">
                                                <i class="ri-eye-line" id="whatsapp_secret_icon"></i>
                                            </button>
                                        </div>
                                        @error('whatsapp_api_secret')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current secret</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="whatsapp_phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control @error('whatsapp_phone_number') is-invalid @enderror" id="whatsapp_phone_number" name="whatsapp_phone_number" value="{{ old('whatsapp_phone_number', $settings['whatsapp_phone_number'] ?? '') }}" placeholder="+1234567890">
                                        @error('whatsapp_phone_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Telegram Tab -->
                            <div class="tab-pane fade" id="telegram-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="telegram_bot_token" class="form-label">Bot Token</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('telegram_bot_token') is-invalid @enderror" id="telegram_bot_token" name="telegram_bot_token" value="{{ old('telegram_bot_token', $settings['telegram_bot_token'] ?? '') }}" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                                            <button class="btn btn-light" type="button" id="toggle_telegram_token">
                                                <i class="ri-eye-line" id="telegram_token_icon"></i>
                                            </button>
                                        </div>
                                        @error('telegram_bot_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current token</span>
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="telegram_chat_id" class="form-label">Chat ID</label>
                                        <input type="text" class="form-control @error('telegram_chat_id') is-invalid @enderror" id="telegram_chat_id" name="telegram_chat_id" value="{{ old('telegram_chat_id', $settings['telegram_chat_id'] ?? '') }}" placeholder="-1001234567890">
                                        @error('telegram_chat_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Discord Tab -->
                            <div class="tab-pane fade" id="discord-tab" role="tabpanel">
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label for="discord_webhook_url" class="form-label">Webhook URL</label>
                                        <input type="url" class="form-control @error('discord_webhook_url') is-invalid @enderror" id="discord_webhook_url" name="discord_webhook_url" value="{{ old('discord_webhook_url', $settings['discord_webhook_url'] ?? '') }}" placeholder="https://discord.com/api/webhooks/...">
                                        @error('discord_webhook_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label for="discord_bot_token" class="form-label">Bot Token</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control @error('discord_bot_token') is-invalid @enderror" id="discord_bot_token" name="discord_bot_token" value="{{ old('discord_bot_token', $settings['discord_bot_token'] ?? '') }}" placeholder="Your Discord Bot Token">
                                            <button class="btn btn-light" type="button" id="toggle_discord_token">
                                                <i class="ri-eye-line" id="discord_token_icon"></i>
                                            </button>
                                        </div>
                                        @error('discord_bot_token')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <span class="d-block fs-12 text-muted mt-1">Leave blank to keep current token</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-list float-end">
                            <button type="submit" class="btn btn-primary btn-wave">
                                <i class="ri-save-line me-1"></i>Save Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <!-- End::row-1 -->

@endsection

@section('scripts')
    <!-- Choices JS -->
    <script src="{{asset('build/assets/libs/choices.js/public/assets/scripts/choices.min.js')}}"></script>
    
    <!-- Sweetalerts JS -->
    <script src="{{asset('build/assets/libs/sweetalert2/sweetalert2.min.js')}}"></script>
    
    <script>
        // Initialize Choices.js for select dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            const selects = document.querySelectorAll('select[data-trigger]');
            selects.forEach(select => {
                new Choices(select, {
                    searchEnabled: false,
                    placeholder: true,
                });
            });
        });

        // Toggle password visibility functions
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('ri-eye-line');
                icon.classList.add('ri-eye-off-line');
            } else {
                input.type = 'password';
                icon.classList.remove('ri-eye-off-line');
                icon.classList.add('ri-eye-line');
            }
        }

        // SMTP Password toggle
        document.getElementById('toggle_smtp_password')?.addEventListener('click', function() {
            togglePasswordVisibility('smtp_password', 'smtp_password_icon');
        });

        // SMS Secret toggle
        document.getElementById('toggle_sms_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('sms_api_secret', 'sms_secret_icon');
        });

        // WhatsApp Secret toggle
        document.getElementById('toggle_whatsapp_secret')?.addEventListener('click', function() {
            togglePasswordVisibility('whatsapp_api_secret', 'whatsapp_secret_icon');
        });

        // Telegram Token toggle
        document.getElementById('toggle_telegram_token')?.addEventListener('click', function() {
            togglePasswordVisibility('telegram_bot_token', 'telegram_token_icon');
        });

        // Discord Token toggle
        document.getElementById('toggle_discord_token')?.addEventListener('click', function() {
            togglePasswordVisibility('discord_bot_token', 'discord_token_icon');
        });
    </script>
@endsection
