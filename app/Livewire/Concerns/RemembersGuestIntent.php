<?php

namespace App\Livewire\Concerns;

/**
 * Lets a guest click Save or Follow: we remember what they wanted, send them to log in, and
 * finish the job when they land back on the page. The intent lives in the session (set by
 * a Livewire action, never by a link), so nobody can trick a member into saving something.
 */
trait RemembersGuestIntent
{
    protected function rememberAndLogIn(string $intent, string $uuid, string $returnUrl): void
    {
        session()->put("pending.{$intent}", $uuid);
        session()->put('url.intended', $returnUrl);

        $this->redirectRoute('login');
    }

    /**
     * True once, when the member has just logged in to do this.
     */
    protected function pendingIntent(string $intent, string $uuid): bool
    {
        if (auth()->guest() || session()->get("pending.{$intent}") !== $uuid) {
            return false;
        }

        session()->forget("pending.{$intent}");

        return true;
    }
}
