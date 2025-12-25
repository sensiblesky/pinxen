<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $ipAddress;
    public string $userAgent;
    public string $deviceType;
    public string $browser;
    public string $platform;
    public ?array $location;
    public string $loginTime;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        string $ipAddress,
        string $userAgent,
        string $deviceType,
        string $browser,
        string $platform,
        ?array $location = null
    ) {
        $this->user = $user;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->deviceType = $deviceType;
        $this->browser = $browser;
        $this->platform = $platform;
        
        // If location is not provided, fetch it now (in queue job)
        if ($location === null) {
            $location = \App\Services\IPGeolocationService::getLocation($ipAddress);
        }
        $this->location = $location;
        
        $this->loginTime = now()->format('Y-m-d H:i:s');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $settings = Setting::getAllCached();
        $fromAddress = $settings['smtp_from_address'] ?? config('mail.from.address');
        $fromName = $settings['smtp_from_name'] ?? config('mail.from.name');
        
        $loginTime = now()->format('H:i');
        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'New Login Alert - ' . config('app.name') . ' - ' . $loginTime,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.login-alert',
            with: [
                'user' => $this->user,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'deviceType' => $this->deviceType,
                'browser' => $this->browser,
                'platform' => $this->platform,
                'location' => $this->location,
                'loginTime' => $this->loginTime,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
