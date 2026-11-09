<?php

it('renders a friendly page for each error', function (int $status, string $title) {
    $html = view("errors.{$status}")->render();

    expect($html)
        ->toContain("<p class=\"error-page__code\">{$status}</p>")
        ->toContain("<h1>{$title}</h1>")
        ->toContain('portfolio application that is not intended for public use')
        ->toContain('<footer class="site-footer">');
})->with([
    [403, 'Not allowed'],
    [404, 'Page not found'],
    [419, 'Page expired'],
    [429, 'Slow down'],
    [500, 'Something went wrong'],
    [503, 'Back soon'],
]);

it('uses the custom page for a missing route', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found');
});
