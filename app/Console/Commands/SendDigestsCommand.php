<?php

namespace App\Console\Commands;

use App\Enums\EmailPreference;
use App\Mail\WeeklyDigest;
use App\Models\User;
use App\Support\Digest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Runs hourly. Queues the weekly digest for every member whose chosen day and hour (in
 * their own timezone) is now, who has something new from people they follow, and who
 * wants it. The demo account and sample creators are never emailed.
 */
#[Signature('digest:send')]
#[Description('Queue the weekly digest for members whose digest is due this hour')]
class SendDigestsCommand extends Command
{
    public function handle(): int
    {
        $now = now()->startOfHour();
        $sent = 0;

        User::query()
            ->where('is_demo', false)
            ->where('is_sample', false)
            ->whereNotNull('email_verified_at')
            ->whereHas('following')
            ->chunkById(500, function (Collection $users) use ($now, &$sent) {
                foreach ($users as $user) {
                    /** @var User $user */
                    if (! $user->wantsEmail(EmailPreference::Digest) || ! $user->digestIsDue($now)) {
                        continue;
                    }

                    $digest = Digest::for($user, $now);

                    if ($digest->isEmpty()) {
                        continue;
                    }

                    Mail::to($user)->queue(new WeeklyDigest($user, $now, $digest->since));
                    $user->forceFill(['last_digest_at' => $now])->saveQuietly();
                    $sent++;
                }
            });

        $this->info("Queued {$sent} ".str('digest')->plural($sent).'.');

        return self::SUCCESS;
    }
}
