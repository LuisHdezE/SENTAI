<?php

namespace Sentai\Modules\Inventory\Application\Services;

use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\Contracts\InventoryItemRepository;
use Sentai\Modules\Inventory\Application\Contracts\InventoryLocationRepository;
use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;
use Sentai\Modules\Inventory\Application\DTO\InventoryMutationContext;
use Sentai\Modules\Inventory\Domain\Entities\InventoryItem;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryAuditEvents;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryOperations;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryReason;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectory;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;

/**
 * API-INV-003 listInventory, API-INV-004 adjustInventory, API-INV-005 moveInventory,
 * API-INV-006 blockLocation and API-INV-007 unblockLocation.
 *
 * Lock discipline (EVD-ARCH-TXN-001 §2.4): structural Location rows are locked first in
 * ascending identifier order, then `inventory_location_blocks`, then `inventory_items`
 * in ascending primary-key order. No operation validates an invariant outside its lock.
 */
final readonly class InventoryService
{
    public function __construct(
        private InventoryItemRepository $items,
        private InventoryLocationRepository $locations,
        private LocationDirectory $locationDirectory,
        private InventoryAuditSink $audit,
        private IdempotencyGate $idempotency,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function list(array $filters): array
    {
        $page = $this->items->list($filters);

        return [
            'data' => array_map(
                static fn (InventoryItem $item): array => $item->toArray(),
                $page['items'],
            ),
            'meta' => [
                'page' => $page['page'],
                'per_page' => $page['per_page'],
                'total' => $page['total'],
            ],
        ];
    }

    public function adjust(
        string $inventoryItemId,
        string $operationType,
        string $quantity,
        ?string $targetState,
        string $reason,
        InventoryMutationContext $context,
    ): IdempotentResponse {
        return $this->idempotency->execute(
            InventoryOperations::ADJUST_INVENTORY,
            $context->idempotencyKey,
            $this->hash([
                'operation' => InventoryOperations::ADJUST_INVENTORY,
                'inventory_item_id' => $inventoryItemId,
                'operation_type' => $operationType,
                'quantity' => $quantity,
                'target_state' => $targetState,
                'reason' => $reason,
            ]),
            fn (): IdempotentResponse => $this->commitAdjustment(
                $inventoryItemId,
                $operationType,
                $quantity,
                $targetState,
                InventoryReason::fromRequest($reason),
                $context,
            ),
        );
    }

    public function move(
        string $inventoryItemId,
        string $destinationLocationId,
        string $quantity,
        string $reason,
        InventoryMutationContext $context,
    ): IdempotentResponse {
        return $this->idempotency->execute(
            InventoryOperations::MOVE_INVENTORY,
            $context->idempotencyKey,
            $this->hash([
                'operation' => InventoryOperations::MOVE_INVENTORY,
                'inventory_item_id' => $inventoryItemId,
                'destination_location_id' => $destinationLocationId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]),
            fn (): IdempotentResponse => $this->commitMove(
                $inventoryItemId,
                $destinationLocationId,
                $quantity,
                InventoryReason::fromRequest($reason),
                $context,
            ),
        );
    }

    public function block(string $locationId, string $reason, InventoryMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            InventoryOperations::BLOCK_LOCATION,
            $context->idempotencyKey,
            $this->hash([
                'operation' => InventoryOperations::BLOCK_LOCATION,
                'location_id' => $locationId,
                'reason' => $reason,
            ]),
            fn (): IdempotentResponse => $this->commitBlock(
                $locationId,
                InventoryReason::fromRequest($reason),
                $context,
            ),
        );
    }

    public function unblock(string $locationId, string $reason, InventoryMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            InventoryOperations::UNBLOCK_LOCATION,
            $context->idempotencyKey,
            $this->hash([
                'operation' => InventoryOperations::UNBLOCK_LOCATION,
                'location_id' => $locationId,
                'reason' => $reason,
            ]),
            fn (): IdempotentResponse => $this->commitUnblock(
                $locationId,
                InventoryReason::fromRequest($reason),
                $context,
            ),
        );
    }

    private function commitAdjustment(
        string $inventoryItemId,
        string $operationType,
        string $quantity,
        ?string $targetState,
        InventoryReason $reason,
        InventoryMutationContext $context,
    ): IdempotentResponse {
        $item = $this->items->find($inventoryItemId);

        if ($item === null) {
            throw new ResourceNotFound('The referenced inventory item does not exist.');
        }

        if ($this->locationDirectory->lock($item->locationId) === null) {
            throw new ResourceNotFound('The Location of the referenced inventory item no longer exists.');
        }

        $locked = $this->items->lock($inventoryItemId);

        if ($locked === null) {
            throw new ResourceNotFound('The referenced inventory item does not exist.');
        }

        $delta = $this->normalizeQty($quantity);
        $stateAfter = $locked->state;
        $onHandAfter = $locked->onHandQty;

        if (bccomp($delta, '0', 4) < 0) {
            throw new DomainConflict('An adjustment quantity may not be negative.');
        }

        switch ($operationType) {
            case 'increase':
                $onHandAfter = bcadd($onHandAfter, $delta, 4);
                break;
            case 'decrease':
                $onHandAfter = bcsub($onHandAfter, $delta, 4);
                break;
            case 'state_change':
                if ($targetState === null) {
                    throw new DomainConflict('A state change adjustment requires target_state.');
                }

                $stateAfter = InventoryState::fromStateValue($targetState);

                if ($locked->isInReception()) {
                    throw new DomainConflict('Stock pending put-away cannot change state before confirmPutAway.');
                }

                if ($delta !== '0.0000') {
                    throw new DomainConflict('A state change adjustment may not change quantity.');
                }

                break;
            default:
                throw new DomainConflict('Unsupported inventory adjustment type.');
        }

        if (bccomp($onHandAfter, '0', 4) < 0) {
            throw new DomainConflict('The adjustment would leave a negative OnHand quantity.');
        }

        if (bccomp($locked->reservedQty, $onHandAfter, 4) > 0) {
            throw new DomainConflict('The adjustment would violate the invariant Reserved <= OnHand.');
        }

        $this->items->replaceQuantitiesAndState($locked->id, $onHandAfter, $locked->reservedQty, $stateAfter);

        $this->items->storeAdjustment(
            $locked->id,
            $operationType,
            $operationType === 'state_change' ? '0.0000' : ($operationType === 'decrease' ? '-'.$delta : $delta),
            $locked->onHandQty,
            $onHandAfter,
            $locked->state->value,
            $stateAfter->value,
            $reason->value,
            $context->actorId,
            $context->correlationId,
        );

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::ADJUSTMENT_APPLIED,
            aggregateType: 'InventoryItem',
            aggregateId: $locked->id,
            operation: InventoryOperations::ADJUST_INVENTORY,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'inventory_item_id' => $locked->id,
                'product_id' => $locked->productId,
                'location_id' => $locked->locationId,
                'lot_ref' => $locked->lotRef,
                'serial_ref' => $locked->serialRef,
                'operation_type' => $operationType,
                'state_before' => $locked->state->value,
                'state_after' => $stateAfter->value,
                'qty_before' => $locked->onHandQty,
                'qty_after' => $onHandAfter,
                'reason_ref' => $reason->value,
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'inventory_item_id' => $locked->id,
                'operation_type' => $operationType,
                'state_before' => $locked->state->value,
                'state_after' => $stateAfter->value,
                'on_hand_before' => $locked->onHandQty,
                'on_hand_after' => $onHandAfter,
                'reserved_qty' => $locked->reservedQty,
                'reason_ref' => $reason->value,
            ],
        ], 200);
    }

    private function commitMove(
        string $inventoryItemId,
        string $destinationLocationId,
        string $quantity,
        InventoryReason $reason,
        InventoryMutationContext $context,
    ): IdempotentResponse {
        $origin = $this->items->find($inventoryItemId);

        if ($origin === null) {
            throw new ResourceNotFound('The source inventory item does not exist.');
        }

        if ($origin->locationId === $destinationLocationId) {
            throw new DomainConflict('The destination Location is already the Location of the source inventory item.');
        }

        [$firstLock, $secondLock] = $origin->locationId < $destinationLocationId
            ? [$origin->locationId, $destinationLocationId]
            : [$destinationLocationId, $origin->locationId];

        $first = $this->locationDirectory->lock($firstLock);

        if ($first === null) {
            throw new ResourceNotFound('A referenced Location does not exist.');
        }

        $second = $this->locationDirectory->lock($secondLock);

        if ($second === null) {
            throw new ResourceNotFound('A referenced Location does not exist.');
        }

        if ($secondLock === $destinationLocationId && $second->isActive === false) {
            throw new DomainConflict('The destination Location is not active.');
        }

        if ($this->locations->lockBlock($destinationLocationId) !== null) {
            throw new DomainConflict('The destination Location is blocked; unblock it before moving stock into it.');
        }

        $item = $this->items->lock($inventoryItemId);

        if ($item === null) {
            throw new ResourceNotFound('The source inventory item does not exist.');
        }

        if ($item->isInReception()) {
            throw new DomainConflict('Stock pending put-away must be relocated with confirmPutAway.');
        }

        $delta = $this->normalizeQty($quantity);

        if (bccomp($delta, '0', 4) <= 0) {
            throw new DomainConflict('A movement quantity must be positive.');
        }

        if (bccomp($delta, $item->onHandQty, 4) > 0) {
            throw new DomainConflict('The movement quantity exceeds the OnHand quantity of the source inventory item.');
        }

        $target = $this->items->lockIdentity(
            $item->productId,
            $destinationLocationId,
            $item->lotRef,
            $item->serialRef,
            $item->state,
        );

        $destinationOnHandBefore = $target?->onHandQty ?? '0.0000';

        if ($target === null) {
            $target = $this->items->store(
                $item->productId,
                $destinationLocationId,
                $item->lotRef,
                $item->serialRef,
                $item->state,
                $delta,
                '0.0000',
            );
            $destinationOnHandAfter = $delta;
        } else {
            $this->items->incrementOnHand($target->id, $delta);
            $destinationOnHandAfter = bcadd($destinationOnHandBefore, $delta, 4);
        }

        $remaining = $this->items->reduceOnHand($item->id, $delta, $item->reservedQty, $item->state);

        $this->items->storeMovement(
            $item->id,
            $target->id,
            $origin->locationId,
            $destinationLocationId,
            $delta,
            $item->onHandQty,
            $remaining,
            $destinationOnHandBefore,
            $destinationOnHandAfter,
            $item->state->value,
            $target->state->value,
            $reason->value,
            $context->actorId,
            $context->correlationId,
        );

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::MOVE_COMPLETED,
            aggregateType: 'InventoryItem',
            aggregateId: $item->id,
            operation: InventoryOperations::MOVE_INVENTORY,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'inventory_item_id' => $item->id,
                'product_id' => $item->productId,
                'source_location_id' => $origin->locationId,
                'destination_location_id' => $destinationLocationId,
                'lot_ref' => $item->lotRef,
                'serial_ref' => $item->serialRef,
                'state' => $item->state->value,
                'quantity' => $delta,
                'qty_before' => $item->onHandQty,
                'qty_after' => $remaining,
                'reason_ref' => $reason->value,
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'inventory_item_id' => $item->id,
                'source_location_id' => $origin->locationId,
                'destination_location_id' => $destinationLocationId,
                'quantity' => $delta,
                'state' => $item->state->value,
                'source_on_hand_after' => $remaining,
            ],
        ], 200);
    }

    private function commitBlock(string $locationId, InventoryReason $reason, InventoryMutationContext $context): IdempotentResponse
    {
        if ($this->locationDirectory->lock($locationId) === null) {
            throw new ResourceNotFound('The referenced Location does not exist.');
        }

        if ($this->locations->lockBlock($locationId) !== null) {
            throw new DomainConflict('The referenced Location is already blocked.');
        }

        $this->locations->storeBlock($locationId, $reason->value, $context->actorId, $context->correlationId);

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::LOCATION_BLOCKED,
            aggregateType: 'Location',
            aggregateId: $locationId,
            operation: InventoryOperations::BLOCK_LOCATION,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'location_id' => $locationId,
                'blocked_by' => $context->actorId,
                'reason_ref' => $reason->value,
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'location_id' => $locationId,
                'blocked' => true,
                'reason_ref' => $reason->value,
            ],
        ], 200);
    }

    private function commitUnblock(string $locationId, InventoryReason $reason, InventoryMutationContext $context): IdempotentResponse
    {
        if ($this->locationDirectory->lock($locationId) === null) {
            throw new ResourceNotFound('The referenced Location does not exist.');
        }

        if ($this->locations->lockBlock($locationId) === null) {
            throw new DomainConflict('The referenced Location is not blocked.');
        }

        $this->locations->releaseBlock($locationId);

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::LOCATION_UNBLOCKED,
            aggregateType: 'Location',
            aggregateId: $locationId,
            operation: InventoryOperations::UNBLOCK_LOCATION,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'location_id' => $locationId,
                'unblocked_by' => $context->actorId,
                'reason_ref' => $reason->value,
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'location_id' => $locationId,
                'blocked' => false,
                'reason_ref' => $reason->value,
            ],
        ], 200);
    }

    /** @param array<string, mixed> $payload */
    private function hash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeQty(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
