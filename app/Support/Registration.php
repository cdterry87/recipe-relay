<?php

namespace App\Support;

/**
 * Whether new accounts can be made from the website. Demo mode closes registration,
 * including account creation through Google sign-in. `user:create` always works.
 */
final class Registration
{
    public static function open(): bool
    {
        return ! config('relay.demo_mode');
    }

    /**
     * Google sign-in is offered only when its credentials are configured.
     */
    public static function googleEnabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
