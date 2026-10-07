<?php

namespace Sentai\Modules\Inventory\Application\Services;

use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\Contracts\InventoryItemRepository;
use Sentai\Modules\Inventory\Application\Contracts\InventoryLocationRepository;
use Sentai\Modules\Inventory\Application\Contracts\ReceiptRepository;
use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;
use Sentai\Modules\Inventory\Application\DTO\InventoryMutationContext;
use Sentai\Modules\Inventory\Domain\Entities\ReceiptLine;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryAuditEvents;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryOperations;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Sentai\Modules\Inventory\Domain\ValueObjects\ReceiptStatus;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectory;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;

/**
 * API-INV-002 confirmPutAway.
 *
 * Moves stock committed into a Warehouse reception Location by API-INV-001 to the
 * declared destination Location. After the confirmation the stock is no longer
 * functionally in the reception area (AC-003) and its reception-only state is lifted:
 * it becomes `available`, or `blocked` when the destination Location is operationally
 * blocked (BR-004). Quantities are never duplicated: target items with the same
 * Product x Lot x Serial x State identity are merged.
 */
final readonly class ConfirmPutAwayService
{
    public function __construct(
        private ReceiptRepository $receipts,
        private InventoryItemRepository $items,
        private InventoryLocationRepository $locations,
        private LocationDirectory $locationDirectory,
        private InventoryAuditSink $audit,
        private IdempotencyGate $idempotency,
    ) {}

    /**
     * @param  list<array{receipt_line_id: string, inventory_item_id: string, quantity: string}>  $lines
     */
    public function confirm(string $receiptId, string $destinationLocationId, array $lines, InventoryMutationContext $context): IdempotentResponse
    {
        $requestHash = hash('sha256', json_encode([
            'operation' => InventoryOperations::CONFIRM_PUT_AWAY,
            'receipt_id' => $receiptId,
            'destination_location_id' => $destinationLocationId,
            'lines' => $lines,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $this->idempotency->execute(
            InventoryOperations::CONFIRM_PUT_AWAY,
            $context->idempotencyKey,
            $requestHash,
            fn (): IdempotentResponse => $this->commit($receiptId, $destinationLocationId, $lines, $context),
        );
    }

    /**
     * @param  list<array{receipt_line_id: string, inventory_item_id: string, quantity: string}>  $lines
     */
    private function commit(string $receiptId, string $destinationLocationId, array $lines, InventoryMutationContext $context): IdempotentResponse
    {
        $receipt = $this->receipts->lock($receiptId);

        if ($receipt === null) {
            throw new ResourceNotFound('The referenced receipt does not exist.');
        }

        if ($receipt->status !== ReceiptStatus::Received) {
            throw new DomainConflict('The referenced receipt has already completed put-away.');
        }

        $destination = $this->locationDirectory->lock($destinationLocationId);

        if ($destination === null) {
            throw new ResourceNotFound('The destination Location does not exist.');
        }

        if ($destination->warehouseId !== $receipt->warehouseId) {
            throw new DomainConflict('The destination Location belongs to a different warehouse than the receipt.');
        }

        if ($destination->isActive === false) {
            throw new DomainConflict('The destination Location is not active.');
        }

        $destinationBlocked = $this->locations->lockBlock($destinationLocationId) !== null;

        $receiptLines = $this->indexLines($this->receipts->lockLinesForReceipt($receiptId));

        if ($receiptLines === []) {
            throw new DomainConflict('The referenced receipt has no lines to put away.');
        }

        $requested = $this->resolveLines($lines, $receiptLines);

        $itemIds = array_keys($requested);
        sort($itemIds, SORT_STRING);

        $taskIds = [];
        $movedQty = [];
        $movements = [];

        foreach ($itemIds as $inventoryItemId) {
            $item = $this->items->lock($inventoryItemId);

            if ($item === null) {
                throw new ResourceNotFound("Inventory item {$inventoryItemId} does not exist.");
            }

            if ($item->locationId !== $receipt->receptionLocationId) {
                throw new DomainConflict("Inventory item {$inventoryItemId} is not in the receipt's reception Location.");
            }

            if (! $item->isInReception()) {
                throw new DomainConflict("Inventory item {$inventoryItemId} is not pending put-away.");
            }

            $quantity = $requested[$inventoryItemId]['quantity'];

            if (bccomp($quantity, $item->onHandQty, 4) > 0) {
                throw new DomainConflict("Put-away quantity exceeds the received quantity of inventory item {$inventoryItemId}.");
            }

            $targetState = $destinationBlocked ? InventoryState::Blocked : InventoryState::Available;

            $target = $this->items->lockIdentity(
                $item->productId,
                $destinationLocationId,
                $item->lotRef,
                $item->serialRef,
                $targetState,
                $item->id,
            );

            $destinationOnHandBefore = $target?->onHandQty ?? '0.0000';

            if ($target === null) {
                $target = $this->items->store(
                    $item->productId,
                    $destinationLocationId,
                    $item->lotRef,
                    $item->serialRef,
                    $targetState,
                    $quantity,
                    '0.0000',
                );
                $destinationOnHandAfter = $quantity;
            } else {
                $this->items->incrementOnHand($target->id, $quantity);
                $destinationOnHandAfter = bcadd($destinationOnHandBefore, $quantity, 4);
            }

            foreach ($requested[$inventoryItemId]['receipt_lines'] as $receiptLineId => $lineQty) {
                $taskIds[] = $this->receipts->storePutAway(
                    $receipt->id,
                    $receiptLineId,
                    $item->id,
                    $receipt->receptionLocationId,
                    $destinationLocationId,
                    $lineQty,
                    $context->actorId,
                    $context->correlationId,
                );

                $movedQty[$receiptLineId] = bcadd($movedQty[$receiptLineId] ?? '0', $lineQty, 4);
            }

            $remaining = $this->items->reduceOnHand($item->id, $quantity, $item->reservedQty, $item->state);

            $this->items->storeMovement(
                $item->id,
                $target->id,
                $receipt->receptionLocationId,
                $destinationLocationId,
                $quantity,
                $item->onHandQty,
                $remaining,
                $destinationOnHandBefore,
                $destinationOnHandAfter,
                $item->state->value,
                $targetState->value,
                'put_away:'.$receipt->id,
                $context->actorId,
                $context->correlationId,
            );

            $movements[] = [
                'inventory_item_id' => $item->id,
                'source_location_id' => $receipt->receptionLocationId,
                'destination_location_id' => $destinationLocationId,
                'quantity' => $quantity,
                'state' => $targetState->value,
            ];
        }

        $completed = true;

        foreach ($receiptLines as $line) {
            $moved = $movedQty[$line->id] ?? '0';

            if (bccomp($moved, $line->receivedQty, 4) < 0) {
                $completed = false;
                break;
            }
        }

        if ($completed) {
            $this->receipts->updateReceiptStatus($receipt->id, ReceiptStatus::PutAwayCompleted);
        }

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::PUTAWAY_COMPLETED,
            aggregateType: 'Receipt',
            aggregateId: $receipt->id,
            operation: InventoryOperations::CONFIRM_PUT_AWAY,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'receipt_id' => $receipt->id,
                'putaway_task_ids' => $taskIds,
                'location_id' => $destinationLocationId,
                'source_location_id' => $receipt->receptionLocationId,
                'destination_blocked' => $destinationBlocked,
                'movements' => $movements,
                'receipt_completed' => $completed,
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'receipt_id' => $receipt->id,
                'destination_location_id' => $destinationLocationId,
                'destination_blocked' => $destinationBlocked,
                'status' => $completed ? ReceiptStatus::PutAwayCompleted->value : ReceiptStatus::Received->value,
                'movements' => $movements,
            ],
        ], 200);
    }

    /**
     * @param  list<ReceiptLine>  $lines
     * @return array<string, ReceiptLine>
     */
    private function indexLines(array $lines): array
    {
        $indexed = [];

        foreach ($lines as $line) {
            $indexed[$line->id] = $line;
        }

        return $indexed;
    }

    /**
     * @param  list<array{receipt_line_id: string, inventory_item_id: string, quantity: string}>  $lines
     * @param  array<string, ReceiptLine>  $receiptLines
     * @return array<string, array{quantity: string, receipt_lines: array<string, string>}>
     */
    private function resolveLines(array $lines, array $receiptLines): array
    {
        $requested = [];

        foreach ($lines as $index => $line) {
            $receiptLine = $receiptLines[$line['receipt_line_id']] ?? null;

            if ($receiptLine === null) {
                throw new DomainConflict("Put-away line {$index} references a receipt line that does not belong to this receipt.");
            }

            if ($receiptLine->inventoryItemId !== $line['inventory_item_id']) {
                throw new DomainConflict("Put-away line {$index} does not match the inventory item of its receipt line.");
            }

            $quantity = number_format((float) $line['quantity'], 4, '.', '');

            if (bccomp($quantity, '0', 4) <= 0) {
                throw new DomainConflict("Put-away line {$index} must declare a positive quantity.");
            }

            $itemId = $line['inventory_item_id'];

            $requested[$itemId] ??= ['quantity' => '0', 'receipt_lines' => []];
            $requested[$itemId]['quantity'] = bcadd($requested[$itemId]['quantity'], $quantity, 4);
            $requested[$itemId]['receipt_lines'][$receiptLine->id] =
                bcadd($requested[$itemId]['receipt_lines'][$receiptLine->id] ?? '0', $quantity, 4);
        }

        if ($requested === []) {
            throw new DomainConflict('A put-away confirmation must declare at least one line.');
        }

        foreach ($requested as $itemId => $entry) {
            $received = '0';

            foreach (array_keys($entry['receipt_lines']) as $receiptLineId) {
                $received = bcadd($received, $receiptLines[$receiptLineId]->receivedQty, 4);
            }

            if (bccomp($entry['quantity'], $received, 4) > 0) {
                throw new DomainConflict("Put-away quantity exceeds the received quantity of inventory item {$itemId}.");
            }
        }

        return $requested;
    }
}
