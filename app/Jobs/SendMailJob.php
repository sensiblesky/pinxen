<?php

namespace App\Jobs;

use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60; // Job timeout in seconds
    public $tries = 3; // Retry 3 times on failure
    public $backoff = [10, 30, 60]; // Wait 10s, 30s, 60s between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $to,
        public string $subject,
        public string $view,
        public array $data = [],
        public ?string $mailableClass = null,
        public array $mailableData = []
    ) {
        // Set queue name for better organization
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if SMTP is configured
            if (!MailService::isSmtpConfigured()) {
                Log::warning('SendMailJob: SMTP is not configured, skipping email', [
                    'to' => $this->to,
                    'subject' => $this->subject,
                ]);
                return;
            }

            // If a mailable class is provided, use it
            if ($this->mailableClass && class_exists($this->mailableClass)) {
                $mailable = new $this->mailableClass(...$this->mailableData);
                // Get configured mailer and send
                $mailer = MailService::getConfiguredMailer();
                $mailer->to($this->to)->send($mailable);
            } else {
                // Use view-based email - ensure mailer is configured first
                MailService::getConfiguredMailer(); // This sets up the mailer config
                // Use Mail facade with explicit mailer name
                Mail::mailer('database_smtp')->send($this->view, $this->data, function ($message) {
                    $message->to($this->to)
                        ->subject($this->subject);
                });
            }

            Log::info('SendMailJob: Email sent successfully', [
                'to' => $this->to,
                'subject' => $this->subject,
            ]);
        } catch (\Exception $e) {
            Log::error('SendMailJob: Failed to send email', [
                'to' => $this->to,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to trigger retry mechanism
        }
    }
}
