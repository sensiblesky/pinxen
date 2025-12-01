<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $settings = Setting::getAllCached();
        $fromAddress = $settings['smtp_from_address'] ?? config('mail.from.address');
        $fromName = $settings['smtp_from_name'] ?? config('mail.from.name');
        
        // Format current time as HH:MM
        $currentTime = now()->format('H:i');
        
        $url = URL::temporarySignedRoute(
            'password.reset',
            now()->addMinutes(config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)),
            ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]
        );

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("Password Reset Request : {$currentTime}")
            ->view('emails.password-reset', [
                'url' => $url,
                'userName' => $notifiable->name ?? 'User',
                'expire' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
