<?php

namespace Sentai\Modules\Inventory\Application\Services;

use Sentai\Modules\Inventory\Application\Contracts\AsnRepository;
use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\Contracts\InventoryItemRepository;
use Sentai\Modules\Inventory\Application\Contracts\ReceiptRepository;
use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;
use Sentai\Modules\Inventory\Application\DTO\InventoryMutationContext;
use Sentai\Modules\Inventory\Application\DTO\ReceiveAsnLineInput;
use Sentai\Modules\Inventory\Domain\Entities\AsnLine;
use Sentai\Modules\Inventory\Domain\ValueObjects\AsnStatus;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryAuditEvents;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryOperations;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectory;
use Sentai\Modules\MasterData\Application\Contracts\WarehouseReceptionLocationReader;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;

/**
 * API-INV-001 receiveAsn.
 *
 * Committed stock is persisted immediately as InventoryItem rows at the Warehouse's
 * designated reception Location, in state `received_pending_put_away`: it counts as
 * OnHand (API-INV-003) but is not eligible for normal commercial assignment until
 * confirmPutAway (API-INV-002) relocates it.
 */
final readonly class ReceiveAsnService
{
    public function __construct(
        private AsnRepository $asns,
        private ReceiptRepository $receipts,
        private InventoryItemRepository $items,
        private WarehouseReceptionLocationReader $receptionLocations,
        private LocationDirectory $locationDirectory,
        private InventoryAuditSink $audit,
        private IdempotencyGate $idempotency,
    ) {}

    /**
     * @param  list<array{asn_line_id?: ?string, product_id: string, received_qty: string, lot_ref?: ?string, serial_ref?: ?string, discrepancy_note?: ?string}>  $lines
     */
    public function receive(string $asnId, array $lines, InventoryMutationContext $context): IdempotentResponse
    {
        $requestHash = hash('sha256', json_encode([
            'operation' => InventoryOperations::RECEIVE_ASN,
            'asn_id' => $asnId,
            'lines' => $lines,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $this->idempotency->execute(
            InventoryOperations::RECEIVE_ASN,
            $context->idempotencyKey,
            $requestHash,
            fn (): IdempotentResponse => $this->commit($asnId, $lines, $context),
        );
    }

    /**
     * @param  list<array{asn_line_id?: ?string, product_id: string, received_qty: string, lot_ref?: ?string, serial_ref?: ?string, discrepancy_note?: ?string}>  $lines
     */
    private function commit(string $asnId, array $lines, InventoryMutationContext $context): IdempotentResponse
    {
        $asn = $this->asns->lock($asnId);

        if ($asn === null) {
            throw new ResourceNotFound('The referenced ASN does not exist.');
        }

        if (! $asn->isReceivable()) {
            throw new DomainConflict('The referenced ASN is not in a receivable state.');
        }

        $reception = $this->receptionLocations->forWarehouse($asn->warehouseId);

        if ($reception === null) {
            throw new DomainConflict(
                'Warehouse reception configuration is missing: no reception Location is designated for this warehouse.',
                ['reason' => 'reception_location_not_configured', 'warehouse_id' => $asn->warehouseId],
            );
        }

        $receptionRow = $this->locationDirectory->lock($reception->locationId);

        if ($receptionRow === null) {
            throw new DomainConflict('The designated reception Location no longer exists.');
        }

        $asnLines = $this->indexAsnLines($asn->id);
        $resolved = $this->resolveLines($lines, $asnLines);
        $this->assertQuantities($resolved, $asnLines);

        $receiptLines = [];

        foreach ($resolved as $input) {
            $state = InventoryState::ReceivedPendingPutAway;
            $existing = $this->items->lockIdentity(
                $input->asnLine->productId,
                $reception->locationId,
                $input->lotRef,
                $input->serialRef,
                $state,
            );

            if ($existing === null) {
                $item = $this->items->store(
                    $input->asnLine->productId,
                    $reception->locationId,
                    $input->lotRef,
                    $input->serialRef,
                    $state,
                    $input->receivedQty,
                    '0.0000',
                );
            } else {
                $this->items->incrementOnHand($existing->id, $input->receivedQty);
                $item = $existing;
            }

            $this->asns->incrementReceivedQty($input->asnLine->id, $input->receivedQty);

            $receiptLines[] = [
                'asn_line_id' => $input->asnLine->id,
                'product_id' => $input->asnLine->productId,
                'lot_ref' => $input->lotRef,
                'serial_ref' => $input->serialRef,
                'received_qty' => $input->receivedQty,
                'inventory_item_id' => $item->id,
                'discrepancy_note' => $input->discrepancyNote,
            ];
        }

        $receiptId = $this->receipts->storeReceipt(
            $asn->id,
            $asn->warehouseId,
            $reception->locationId,
            $context->actorId,
            $context->correlationId,
            $receiptLines,
        );

        $this->asns->updateStatus(
            $asn->id,
            $this->asns->isFullyReceived($asn->id) ? AsnStatus::Received->value : AsnStatus::PartiallyReceived->value,
        );

        $this->audit->record(new InventoryAuditEvent(
            eventType: InventoryAuditEvents::ASN_RECEIVED,
            aggregateType: 'Receipt',
            aggregateId: $receiptId,
            operation: InventoryOperations::RECEIVE_ASN,
            actorId: $context->actorId,
            correlationId: $context->correlationId,
            sourceSurface: $context->sourceSurface,
            context: [
                'asn_id' => $asn->id,
                'receipt_id' => $receiptId,
                'warehouse_id' => $asn->warehouseId,
                'reception_location_id' => $reception->locationId,
                'items_ref' => array_map(
                    static fn (array $line): array => [
                        'inventory_item_id' => $line['inventory_item_id'],
                        'product_id' => $line['product_id'],
                        'lot_ref' => $line['lot_ref'],
                        'serial_ref' => $line['serial_ref'],
                        'received_qty' => $line['received_qty'],
                    ],
                    $receiptLines,
                ),
            ],
        ));

        return new IdempotentResponse([
            'data' => [
                'receipt_id' => $receiptId,
                'asn_id' => $asn->id,
                'warehouse_id' => $asn->warehouseId,
                'reception_location_id' => $reception->locationId,
                'status' => 'received',
                'lines' => array_map(
                    static fn (array $line): array => [
                        'asn_line_id' => $line['asn_line_id'],
                        'product_id' => $line['product_id'],
                        'lot_ref' => $line['lot_ref'],
                        'serial_ref' => $line['serial_ref'],
                        'received_qty' => $line['received_qty'],
                        'inventory_item_id' => $line['inventory_item_id'],
                        'discrepancy_note' => $line['discrepancy_note'],
                    ],
                    $receiptLines,
                ),
            ],
        ], 201);
    }

    /**
     * @return array<string, AsnLine>
     */
    private function indexAsnLines(string $asnId): array
    {
        $indexed = [];

        foreach ($this->asns->lines($asnId) as $line) {
            $indexed[$line->id] = $line;
        }

        return $indexed;
    }

    /**
     * @param  list<array{asn_line_id?: ?string, product_id: string, received_qty: string, lot_ref?: ?string, serial_ref?: ?string, discrepancy_note?: ?string}>  $lines
     * @param  array<string, AsnLine>  $asnLines
     * @return list<ReceiveAsnLineInput>
     */
    private function resolveLines(array $lines, array $asnLines): array
    {
        $resolved = [];

        foreach ($lines as $index => $line) {
            $asnLineId = $line['asn_line_id'] ?? null;

            if ($asnLineId !== null) {
                if (! isset($asnLines[$asnLineId])) {
                    throw new DomainConflict("Received line {$index} references an ASN line that does not belong to this ASN.");
                }

                $asnLine = $asnLines[$asnLineId];

                if ($asnLine->productId !== $line['product_id']) {
                    throw new DomainConflict("Received line {$index} declares a product that does not match its ASN line.");
                }
            } else {
                $asnLine = $this->matchByProduct($line, $asnLines, $index);
            }

            $receivedQty = $this->normalizeQty($line['received_qty']);

            if (bccomp($receivedQty, '0', 4) <= 0) {
                throw new DomainConflict("Received line {$index} must declare a positive received quantity.");
            }

            $lotRef = $this->optionalRef($line['lot_ref'] ?? null) ?? $asnLine->lotRef;
            $serialRef = $this->optionalRef($line['serial_ref'] ?? null) ?? $asnLine->serialRef;

            $resolved[] = new ReceiveAsnLineInput(
                $asnLine,
                $receivedQty,
                $lotRef,
                $serialRef,
                $this->optionalRef($line['discrepancy_note'] ?? null),
            );
        }

        if ($resolved === []) {
            throw new DomainConflict('A receipt must declare at least one received line.');
        }

        return $resolved;
    }

    /**
     * @param  array{asn_line_id?: ?string, product_id: string, received_qty: string, lot_ref?: ?string, serial_ref?: ?string, discrepancy_note?: ?string}  $line
     * @param  array<string, AsnLine>  $asnLines
     */
    private function matchByProduct(array $line, array $asnLines, int $index): AsnLine
    {
        $candidates = array_values(array_filter(
            $asnLines,
            static fn (AsnLine $asnLine): bool => $asnLine->productId === $line['product_id'],
        ));

        if ($candidates === []) {
            throw new DomainConflict("Received line {$index} declares a product that is not expected by this ASN.");
        }

        if (count($candidates) > 1) {
            throw new DomainConflict("Received line {$index} is ambiguous: several ASN lines expect this product; asn_line_id is required.");
        }

        return $candidates[0];
    }

    /**
     * Over-receipt is not silently accepted: the cumulative received quantity of an ASN
     * line may not exceed its expected quantity.
     *
     * @param  list<ReceiveAsnLineInput>  $resolved
     * @param  array<string, AsnLine>  $asnLines
     */
    private function assertQuantities(array $resolved, array $asnLines): void
    {
        $requested = [];

        foreach ($resolved as $input) {
            $id = $input->asnLine->id;
            $requested[$id] = bcadd($requested[$id] ?? '0', $input->receivedQty, 4);
        }

        foreach ($requested as $asnLineId => $quantity) {
            $expected = $asnLines[$asnLineId]->expectedQty;

            if (bccomp($quantity, $expected, 4) > 0) {
                throw new DomainConflict(
                    "Receipt exceeds the expected quantity of ASN line {$asnLineId}.",
                    ['reason' => 'quantity_discrepancy_exceeds_expected', 'asn_line_id' => $asnLineId],
                );
            }
        }
    }

    private function normalizeQty(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function optionalRef(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
