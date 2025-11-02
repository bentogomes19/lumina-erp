<?php

use App\Http\Middleware\RedirectUserByRole;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', RedirectUserByRole::class])->group(function () {
    Route::get('/lumina', function () {
        return redirect()->route('filament.lumina.home');
    });
});
