<?php

namespace App\Providers;

use App\Infrastructure\Audit\DatabaseSecurityAuditSink;
use Illuminate\Support\ServiceProvider;
use Sentai\Modules\Identity\Application\Contracts\CapabilityLookup;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Infrastructure\Authentication\DatabaseMobileTokenStore;
use Sentai\Modules\Identity\Infrastructure\Authentication\EloquentCredentialVerifier;
use Sentai\Modules\Identity\Infrastructure\Authorization\EloquentCapabilityLookup;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CredentialVerifier::class, EloquentCredentialVerifier::class);
        $this->app->singleton(CapabilityLookup::class, EloquentCapabilityLookup::class);
        $this->app->singleton(MobileTokenStore::class, DatabaseMobileTokenStore::class);
        $this->app->singleton(SecurityAuditSink::class, DatabaseSecurityAuditSink::class);
    }

    public function boot(): void
    {
        // No business behavior belongs in the Laravel host provider.
    }
}
