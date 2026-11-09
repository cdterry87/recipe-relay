<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\RecipeController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');

// Placeholders until Discover (M3) and creator pages (M4) arrive.
Route::view('/discover', 'pages.coming-soon', [
    'title' => 'Discover recipes',
    'eyebrow' => 'Coming soon',
    'heading' => 'Discover <em>recipes</em>',
    'emoji' => '🔎',
    'message' => 'Search and filter public recipes by meal, collection, time and star rating. The kitchen is still warming up!',
])->name('discover');

Route::view('/creators', 'pages.coming-soon', [
    'title' => 'Trending creators',
    'eyebrow' => 'Coming soon',
    'heading' => 'Trending <em>creators</em>',
    'emoji' => '👩‍🍳',
    'message' => 'Meet the cooks behind the recipes and follow the ones you love. They are still prepping their kitchens.',
])->name('creators');

// whereUuid keeps /recipes/new (an auth route below) from being read as a recipe.
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show')->whereUuid('recipe');
Route::get('/r/{token}', [RecipeController::class, 'shared'])->name('recipes.shared')->where('token', '[A-Za-z0-9]{32}');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->middleware('registration.enabled')->name('register');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/verify-email', 'pages::auth.verify-email')->name('verification.notice');
    Route::get('/verify-email/{user}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::livewire('/recipes/new', 'pages::recipes.edit')->name('recipes.create');
    Route::livewire('/recipes/{recipe}/edit', 'pages::recipes.edit')->name('recipes.edit');
    Route::livewire('/my/recipes', 'pages::my.recipes')->name('my.recipes');

    Route::redirect('/settings', '/settings/profile');
    Route::livewire('/settings/profile', 'pages::settings.profile')->name('settings.profile');
    Route::livewire('/settings/account', 'pages::settings.account')->name('settings.account');
});

Route::get('/styleguide', function () {
    abort_unless(app()->environment('local', 'testing'), 404);

    return view('styleguide');
})->name('styleguide');
