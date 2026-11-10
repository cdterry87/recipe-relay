<?php

namespace App\Mail;

use App\Enums\EmailPreference;
use App\Models\User;
use App\Support\Digest;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * The weekly digest. The content is built when the email is rendered, for the window fixed
 * when it was queued, so the queue only carries the member and two timestamps.
 */
class WeeklyDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Carbon  $at  When the digest was due.
     * @param  Carbon  $since  The start of its "new" window, fixed when it was queued.
     */
    public function __construct(public User $user, public Carbon $at, public Carbon $since)
    {
        $this->afterCommit();
    }

    public function digest(): Digest
    {
        return Digest::for($this->user, $this->at, $this->since);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->digest()->subject());
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.Unsubscribe::url($this->user, EmailPreference::Digest).'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.digest',
            text: 'mail.digest-text',
            with: self::viewData($this->digest()),
        );
    }

    /**
     * Shared by the email and the in-browser preview.
     *
     * @return array<string, mixed>
     */
    public static function viewData(Digest $digest, bool $preview = false): array
    {
        return [
            'digest' => $digest,
            'name' => $digest->user->name,
            'preview' => $preview,
            'unsubscribeUrl' => Unsubscribe::url($digest->user, EmailPreference::Digest),
            'settingsUrl' => route('settings.notifications'),
        ];
    }
}
