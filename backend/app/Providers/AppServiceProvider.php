<?php

namespace App\Providers;

use App\Infrastructure\Audit\DatabaseMasterDataAuditSink;
use App\Infrastructure\Audit\DatabaseSecurityAuditSink;
use Illuminate\Support\ServiceProvider;
use Sentai\Modules\Identity\Application\Contracts\CapabilityLookup;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Infrastructure\Authentication\DatabaseMobileTokenStore;
use Sentai\Modules\Identity\Infrastructure\Authentication\EloquentCredentialVerifier;
use Sentai\Modules\Identity\Infrastructure\Authorization\EloquentCapabilityLookup;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataAuditSink;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataRepository;
use Sentai\Modules\MasterData\Infrastructure\Persistence\DatabaseMasterDataRepository;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Infrastructure\Idempotency\DatabaseIdempotencyGate;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CredentialVerifier::class, EloquentCredentialVerifier::class);
        $this->app->singleton(CapabilityLookup::class, EloquentCapabilityLookup::class);
        $this->app->singleton(MobileTokenStore::class, DatabaseMobileTokenStore::class);
        $this->app->singleton(SecurityAuditSink::class, DatabaseSecurityAuditSink::class);
        $this->app->singleton(IdempotencyGate::class, DatabaseIdempotencyGate::class);
        $this->app->singleton(MasterDataRepository::class, DatabaseMasterDataRepository::class);
        $this->app->singleton(MasterDataAuditSink::class, DatabaseMasterDataAuditSink::class);
    }

    public function boot(): void
    {
        // No business behavior belongs in the Laravel host provider.
    }
}
