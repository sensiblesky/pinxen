<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $userName;
    public $expire;

    /**
     * Create a new message instance.
     */
    public function __construct(string $url, string $userName = 'User', int $expire = 60)
    {
        $this->url = $url;
        $this->userName = $userName;
        $this->expire = $expire;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $settings = Setting::getAllCached();
        $fromAddress = $settings['smtp_from_address'] ?? config('mail.from.address');
        $fromName = $settings['smtp_from_name'] ?? config('mail.from.name');
        
        // Format current time as HH:MM
        $currentTime = now()->format('H:i');
        
        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: "Password Reset Request : {$currentTime}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'url' => $this->url,
                'userName' => $this->userName,
                'expire' => $this->expire,
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


