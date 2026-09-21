<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Infrastructure adapters will be bound to Application ports here or in module providers.
    }

    public function boot(): void
    {
        // No business behavior belongs in the Laravel host provider.
    }
}
