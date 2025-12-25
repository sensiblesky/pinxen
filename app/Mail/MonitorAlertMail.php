<?php

namespace App\Mail;

use App\Models\Monitor;
use App\Models\MonitorAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitorAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public Monitor $monitor;
    public MonitorAlert $alert;
    public string $alertType;
    public string $message;
    public string $status;
    public ?int $responseTime;
    public ?int $statusCode;
    public ?string $errorMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Monitor $monitor,
        MonitorAlert $alert,
        string $alertType,
        string $message,
        string $status,
        ?int $responseTime = null,
        ?int $statusCode = null,
        ?string $errorMessage = null
    ) {
        $this->monitor = $monitor;
        $this->alert = $alert;
        $this->alertType = $alertType;
        $this->message = $message;
        $this->status = $status;
        $this->responseTime = $responseTime;
        $this->statusCode = $statusCode;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->alertType) {
            'down' => "⚠️ Monitor Alert: {$this->monitor->name} is DOWN",
            'up' => "✅ Monitor Alert: {$this->monitor->name} is UP",
            'recovery' => "🔄 Monitor Alert: {$this->monitor->name} Recovered",
            default => "Monitor Alert: {$this->monitor->name}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.monitor-alert',
            with: [
                'monitor' => $this->monitor,
                'alert' => $this->alert,
                'alertType' => $this->alertType,
                'message' => $this->message,
                'status' => $this->status,
                'responseTime' => $this->responseTime,
                'statusCode' => $this->statusCode,
                'errorMessage' => $this->errorMessage,
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





