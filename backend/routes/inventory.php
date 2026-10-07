<?php

use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Inventory\Presentation\Http\Mobile\MobileInventoryController;

Route::prefix('reception')
    ->middleware(['mobile.auth', 'capability:'.Capabilities::WAREHOUSE_RECEIVE])
    ->group(function (): void {
        Route::post('/asn/{id}/receive', [MobileInventoryController::class, 'receiveAsn'])
            ->name('api.v1.receiveAsn');
    });

Route::post('/inventory/put-away', [MobileInventoryController::class, 'confirmPutAway'])
    ->middleware(['mobile.auth', 'capability:'.Capabilities::WAREHOUSE_PUTAWAY])
    ->name('api.v1.confirmPutAway');

// Remaining contracted mobile business operations are implemented in later API implementation increments.
