<?php

use App\Http\Middleware\RedirectUserByRole;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => redirect()->route('filament.lumina.auth.login'))
    ->name('login');
