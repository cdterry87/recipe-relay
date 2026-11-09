<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');

Route::get('/styleguide', function () {
    abort_unless(app()->environment('local', 'testing'), 404);

    return view('styleguide');
})->name('styleguide');
