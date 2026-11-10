<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the shared demo account away from things that would spoil the demo for the next
 * visitor or reach the outside world: changing the email or password, deleting the
 * account, and uploading files. See section B11.3 of docs/spec.md.
 */
class EnsureNotDemo
{
    public const MESSAGE = "That's turned off for the demo account.";

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_demo) {
            if ($request->expectsJson() || ! $request->isMethod('GET')) {
                abort(403, self::MESSAGE);
            }

            return redirect()->route('settings.profile')->with('toast', ['type' => 'info', 'message' => self::MESSAGE]);
        }

        return $next($request);
    }
}
