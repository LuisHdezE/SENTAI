<?php

use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Presentation\Http\Web\WebCsrfController;
use Sentai\Modules\Identity\Presentation\Http\Web\WebLoginController;
use Sentai\Modules\Identity\Presentation\Http\Web\WebLogoutController;

Route::prefix('api/v1/auth/web')->group(function (): void {
    Route::get('/csrf', WebCsrfController::class)->name('api.v1.getWebCsrf');
    Route::post('/login', WebLoginController::class)->name('api.v1.webLogin');
    Route::post('/logout', WebLogoutController::class)
        ->middleware('auth:web')
        ->name('api.v1.webLogout');
});

// Browser business routes under /api/v1 remain contract-driven and are added incrementally.
