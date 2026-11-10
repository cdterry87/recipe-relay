<?php

namespace App\Http\Controllers;

use App\Mail\WeeklyDigest;
use App\Models\User;
use App\Support\Digest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Shows the member their next weekly digest in the browser, rendered from the email's own
 * templates (inline, not in a frame, so browsers that block frames still show it).
 * Works for everyone, including the demo account, which is never sent email.
 */
class DigestPreviewController
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $digest = Digest::for($user);

        return view('pages.digest-preview', [
            'user' => $user,
            'digest' => $digest,
            'next' => $this->nextSend($user),
            // Our own template with escaped content, so it is safe to place in the page.
            'email' => view('mail.digest', WeeklyDigest::viewData($digest, preview: true))->render(),
        ]);
    }

    /**
     * The next time the digest is due, in the member's timezone.
     */
    private function nextSend(User $user): Carbon
    {
        $next = now($user->timezone)->startOfHour()->setTime($user->digest_hour, 0);

        while ($next->dayOfWeek !== $user->digest_day || $next->isPast()) {
            $next->addDay();
        }

        return $next;
    }
}
