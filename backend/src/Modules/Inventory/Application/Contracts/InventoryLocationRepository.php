<?php

namespace Sentai\Modules\Inventory\Application\Contracts;

/**
 * Operational Location block state owned by Inventory.
 *
 * This state is deliberately persisted outside the `locations` table: structural
 * Location data belongs to Master Data, while block/unblock is an Inventory
 * operational fact.
 */
interface InventoryLocationRepository
{
    /**
     * @param  list<string>  $locationIds
     * @return array<string, bool>
     */
    public function blockedStatus(array $locationIds): array;

    public function isBlocked(string $locationId): bool;

    public function lockBlock(string $locationId): ?string;

    public function storeBlock(string $locationId, string $reason, string $actorId, string $correlationId): void;

    public function releaseBlock(string $locationId): void;
}
