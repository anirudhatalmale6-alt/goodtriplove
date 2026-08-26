<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCodeNotification extends Notification
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
