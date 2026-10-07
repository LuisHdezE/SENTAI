<?php

namespace App\Providers;

use App\Infrastructure\Audit\DatabaseInventoryAuditSink;
use App\Infrastructure\Audit\DatabaseMasterDataAuditSink;
use App\Infrastructure\Audit\DatabaseSecurityAuditSink;
use Illuminate\Support\ServiceProvider;
use Sentai\Modules\Audit\Application\Contracts\AuditEventRepository;
use Sentai\Modules\Audit\Infrastructure\Persistence\DatabaseAuditEventRepository;
use Sentai\Modules\Identity\Application\Contracts\AdminIdentityRepository;
use Sentai\Modules\Identity\Application\Contracts\CapabilityLookup;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Infrastructure\Authentication\DatabaseMobileTokenStore;
use Sentai\Modules\Identity\Infrastructure\Authentication\EloquentCredentialVerifier;
use Sentai\Modules\Identity\Infrastructure\Authorization\EloquentCapabilityLookup;
use Sentai\Modules\Identity\Infrastructure\Persistence\DatabaseAdminIdentityRepository;
use Sentai\Modules\Inventory\Application\Contracts\AsnRepository;
use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\Contracts\InventoryItemRepository;
use Sentai\Modules\Inventory\Application\Contracts\InventoryLocationRepository;
use Sentai\Modules\Inventory\Application\Contracts\ReceiptRepository;
use Sentai\Modules\Inventory\Infrastructure\Persistence\DatabaseAsnRepository;
use Sentai\Modules\Inventory\Infrastructure\Persistence\DatabaseInventoryItemRepository;
use Sentai\Modules\Inventory\Infrastructure\Persistence\DatabaseInventoryLocationRepository;
use Sentai\Modules\Inventory\Infrastructure\Persistence\DatabaseReceiptRepository;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectory;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataAuditSink;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataRepository;
use Sentai\Modules\MasterData\Application\Contracts\WarehouseReceptionLocationReader;
use Sentai\Modules\MasterData\Infrastructure\Persistence\DatabaseLocationDirectory;
use Sentai\Modules\MasterData\Infrastructure\Persistence\DatabaseMasterDataRepository;
use Sentai\Modules\MasterData\Infrastructure\Persistence\DatabaseWarehouseReceptionLocationReader;
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
        $this->app->singleton(AdminIdentityRepository::class, DatabaseAdminIdentityRepository::class);
        $this->app->singleton(AuditEventRepository::class, DatabaseAuditEventRepository::class);
        $this->app->singleton(MasterDataRepository::class, DatabaseMasterDataRepository::class);
        $this->app->singleton(MasterDataAuditSink::class, DatabaseMasterDataAuditSink::class);
        $this->app->singleton(WarehouseReceptionLocationReader::class, DatabaseWarehouseReceptionLocationReader::class);
        $this->app->singleton(LocationDirectory::class, DatabaseLocationDirectory::class);
        $this->app->singleton(AsnRepository::class, DatabaseAsnRepository::class);
        $this->app->singleton(ReceiptRepository::class, DatabaseReceiptRepository::class);
        $this->app->singleton(InventoryItemRepository::class, DatabaseInventoryItemRepository::class);
        $this->app->singleton(InventoryLocationRepository::class, DatabaseInventoryLocationRepository::class);
        $this->app->singleton(InventoryAuditSink::class, DatabaseInventoryAuditSink::class);
    }

    public function boot(): void
    {
        // No business behavior belongs in the Laravel host provider.
    }
}
