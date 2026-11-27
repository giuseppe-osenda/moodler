<?php

use App\Http\Controllers\SocialLoginController;
use App\Livewire\Account;
use App\Livewire\MoodComposer;
use App\Livewire\PlaylistLibrary;
use Illuminate\Support\Facades\Route;

Route::livewire('/', MoodComposer::class)->name('home');
Route::view('/privacy', 'legal.page', ['page' => 'privacy'])->name('privacy');
Route::view('/terms', 'legal.page', ['page' => 'terms'])->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('social.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/account', Account::class)->name('account');
    Route::livewire('/playlists', PlaylistLibrary::class)->name('playlists.index');
    Route::livewire('/playlists/{playlist}', MoodComposer::class)->name('playlists.show');
});
