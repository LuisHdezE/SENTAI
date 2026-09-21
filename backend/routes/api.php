<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // I1 intentionally implements no business endpoint.
    // Contracted operations are added incrementally from EVD-API-007.
});
