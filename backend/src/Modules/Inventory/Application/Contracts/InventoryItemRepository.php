<?php

namespace Sentai\Modules\Inventory\Application\Contracts;

use Sentai\Modules\Inventory\Domain\Entities\InventoryItem;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;

/**
 * InventoryItem persistence and locked-mutation seam.
 *
 * Every method that changes state is expected to be invoked inside the business
 * transaction; locking reads use `SELECT ... FOR UPDATE` and lock `inventory_items`
 * rows in ascending primary-key order (EVD-ARCH-TXN-001 §2.4).
 */
interface InventoryItemRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<InventoryItem>, page: int, per_page: int, total: int}
     */
    public function list(array $filters): array;

    public function find(string $id): ?InventoryItem;

    public function lock(string $id): ?InventoryItem;

    public function findIdentity(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
    ): ?InventoryItem;

    public function lockIdentity(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
        ?string $excludeId = null,
    ): ?InventoryItem;

    public function store(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
        string $onHandQty,
        string $reservedQty,
    ): InventoryItem;

    public function incrementOnHand(string $id, string $delta): void;

    /**
     * Reduce the OnHand quantity of an item, leaving the aggregate at zero when it is
     * exhausted. Returns the remaining OnHand quantity.
     */
    public function reduceOnHand(string $id, string $delta, string $reservedQty, InventoryState $state): string;

    public function replaceQuantitiesAndState(string $id, string $onHandQty, string $reservedQty, InventoryState $state): void;

    public function storeAdjustment(
        string $inventoryItemId,
        string $operation,
        ?string $quantityDelta,
        ?string $onHandBefore,
        ?string $onHandAfter,
        ?string $stateBefore,
        ?string $stateAfter,
        string $reason,
        string $actorId,
        string $correlationId,
    ): void;

    public function storeMovement(
        string $sourceInventoryItemId,
        string $destinationInventoryItemId,
        string $sourceLocationId,
        string $destinationLocationId,
        string $quantity,
        string $sourceOnHandBefore,
        string $sourceOnHandAfter,
        string $destinationOnHandBefore,
        string $destinationOnHandAfter,
        string $sourceState,
        string $destinationState,
        string $reason,
        string $actorId,
        string $correlationId,
    ): void;
}
