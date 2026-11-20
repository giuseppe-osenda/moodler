<?php

use App\Livewire\MoodComposer;
use Illuminate\Support\Facades\Route;

Route::livewire('/', MoodComposer::class)->name('home');
