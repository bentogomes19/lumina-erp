<?php

use App\Http\Controllers\Pdf\EnrollmentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/login', fn () => redirect()->route('filament.lumina.auth.login'))
    ->name('login');

// ── PDFs de Matrículas ────────────────────────────────────────────────────────
// Rotas protegidas por autenticação — somente usuários logados no painel.
Route::middleware(['auth'])->prefix('pdf/enrollment')->name('pdf.enrollment.')->group(function () {
    Route::get('{enrollment}/comprovante',            [EnrollmentPdfController::class, 'comprovante'])          ->name('comprovante');
    Route::get('{enrollment}/transferencia-interna',  [EnrollmentPdfController::class, 'transferenciaInterna']) ->name('transferencia-interna');
    Route::get('{enrollment}/transferencia-externa',  [EnrollmentPdfController::class, 'transferenciaExterna']) ->name('transferencia-externa');
    Route::get('{enrollment}/trancamento',            [EnrollmentPdfController::class, 'trancamento'])          ->name('trancamento');
    Route::get('{enrollment}/cancelamento',           [EnrollmentPdfController::class, 'cancelamento'])         ->name('cancelamento');
});
