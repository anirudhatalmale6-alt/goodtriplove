<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Queued on purpose. The mail server on this host holds its SMTP greeting back
 * for fifteen seconds before it will talk, so sending inline made the visitor
 * sit on a submitted form for a quarter of a minute — long enough that the
 * browser looked hung and people pressed the button again. Handing the message
 * to the queue returns the page immediately; a worker started every minute by
 * the scheduler does the waiting instead.
 */
class VerificationCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('security.email_code.ttl_minutes', 10);

        return (new MailMessage)
            ->subject(__('gtl.mail_verification_subject', ['code' => $this->code]))
            ->greeting(__('gtl.mail_hello', ['name' => $notifiable->name]))
            ->line(__('gtl.mail_verification_intro'))
            ->line('# '.$this->code)
            ->line(__('gtl.mail_verification_expiry', ['minutes' => $minutes]))
            ->line(__('gtl.mail_verification_ignore'))
            ->salutation(__('gtl.mail_signature'));
    }
}
