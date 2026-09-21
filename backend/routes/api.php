<?php

use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Presentation\Http\Mobile\MobileLoginController;
use Sentai\Modules\Identity\Presentation\Http\Mobile\MobileLogoutController;
use Sentai\Modules\Identity\Presentation\Http\Mobile\MobileRefreshController;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth/mobile')->group(function (): void {
        Route::post('/login', MobileLoginController::class)->name('api.v1.mobileLogin');
        Route::post('/refresh', MobileRefreshController::class)->name('api.v1.mobileRefresh');
        Route::post('/logout', MobileLogoutController::class)
            ->middleware('mobile.auth')
            ->name('api.v1.mobileLogout');
    });

    // Remaining contracted business operations are implemented in later API implementation increments.
});
