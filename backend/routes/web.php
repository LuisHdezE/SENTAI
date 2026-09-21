<?php

use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Identity\Presentation\Http\Web\WebCsrfController;
use Sentai\Modules\Identity\Presentation\Http\Web\WebLoginController;
use Sentai\Modules\Identity\Presentation\Http\Web\WebLogoutController;
use Sentai\Modules\MasterData\Presentation\Http\MasterDataController;

Route::prefix('api/v1/auth/web')->group(function (): void {
    Route::get('/csrf', WebCsrfController::class)->name('api.v1.getWebCsrf');
    Route::post('/login', WebLoginController::class)->name('api.v1.webLogin');
    Route::post('/logout', WebLogoutController::class)
        ->middleware('auth:web')
        ->name('api.v1.webLogout');
});

Route::prefix('api/v1')
    ->middleware(['auth:web', 'capability:'.Capabilities::ADMIN_MASTERS_MAINTAIN])
    ->group(function (): void {
        Route::get('/products', [MasterDataController::class, 'listProducts'])->name('api.v1.listProducts');
        Route::post('/products', [MasterDataController::class, 'createProduct'])->name('api.v1.createProduct');
        Route::put('/products/{id}', [MasterDataController::class, 'updateProduct'])->name('api.v1.updateProduct');

        Route::get('/customers', [MasterDataController::class, 'listCustomers'])->name('api.v1.listCustomers');
        Route::post('/customers', [MasterDataController::class, 'createCustomer'])->name('api.v1.createCustomer');
        Route::put('/customers/{id}', [MasterDataController::class, 'updateCustomer'])->name('api.v1.updateCustomer');

        Route::get('/warehouses', [MasterDataController::class, 'listWarehouses'])->name('api.v1.listWarehouses');
        Route::post('/warehouses', [MasterDataController::class, 'createWarehouse'])->name('api.v1.createWarehouse');
        Route::put('/warehouses/{id}', [MasterDataController::class, 'updateWarehouse'])->name('api.v1.updateWarehouse');

        Route::get('/zones', [MasterDataController::class, 'listZones'])->name('api.v1.listZones');
        Route::post('/zones', [MasterDataController::class, 'createZone'])->name('api.v1.createZone');
        Route::put('/zones/{id}', [MasterDataController::class, 'updateZone'])->name('api.v1.updateZone');

        Route::get('/locations', [MasterDataController::class, 'listLocations'])->name('api.v1.listLocations');
        Route::post('/locations', [MasterDataController::class, 'createLocation'])->name('api.v1.createLocation');
        Route::put('/locations/{id}', [MasterDataController::class, 'updateLocation'])->name('api.v1.updateLocation');
    });

// Remaining browser business routes under /api/v1 are contract-driven and added incrementally.
