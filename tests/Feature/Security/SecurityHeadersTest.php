<?php

it('sends hardening headers', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'")
        ->assertHeaderMissing('X-Powered-By')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('sends HSTS over https', function () {
    $this->get(str_replace('http://', 'https://', route('home')))
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('sends hardening headers on error pages', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertHeader('X-Frame-Options', 'DENY');
});
