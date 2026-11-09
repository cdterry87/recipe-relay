<?php

namespace App\Http\Controllers;

use App\Enums\EmailPreference;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Signed unsubscribe links from emails. Opening the link only shows a button, so link
 * scanners can't unsubscribe anyone; the button, and mail apps' one-click unsubscribe
 * (RFC 8058, a POST to the same signed address), turn that one email off.
 */
class UnsubscribeController
{
    public function show(Request $request, User $user, string $type): View
    {
        return view('pages.unsubscribe', [
            'preference' => $this->preference($type),
            'action' => $request->fullUrl(),
            'done' => false,
        ]);
    }

    public function store(Request $request, User $user, string $type): Response|View
    {
        $preference = $this->preference($type);
        $user->setEmailPreference($preference, false);

        if ($request->has('List-Unsubscribe')) {
            return response()->noContent();
        }

        return view('pages.unsubscribe', ['preference' => $preference, 'action' => null, 'done' => true]);
    }

    private function preference(string $type): EmailPreference
    {
        return EmailPreference::tryFrom($type) ?? abort(404);
    }
}
