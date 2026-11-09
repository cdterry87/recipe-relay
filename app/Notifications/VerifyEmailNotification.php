<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * The branded "confirm your email" message. The link identifies the account by its UUID,
 * never its id (see AppServiceProvider).
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * @param  User  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $data = [
            'name' => $notifiable->name,
            'url' => $this->verificationUrl($notifiable),
            'expires' => config('auth.verification.expire', 60),
        ];

        return (new MailMessage)
            ->subject('Confirm your email for '.config('app.name'))
            ->view(['mail.verify-email', 'mail.verify-email-text'], $data);
    }
}
