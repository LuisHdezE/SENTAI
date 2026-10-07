<?php

namespace Sentai\Modules\Inventory\Application\Contracts;

use Sentai\Modules\Inventory\Domain\Entities\Receipt;
use Sentai\Modules\Inventory\Domain\Entities\ReceiptLine;
use Sentai\Modules\Inventory\Domain\ValueObjects\ReceiptStatus;

/**
 * Receipt, receipt-line and put-away persistence seam.
 */
interface ReceiptRepository
{
    public function find(string $id): ?Receipt;

    public function lock(string $id): ?Receipt;

    /**
     * @param  list<array{asn_line_id: ?string, product_id: string, lot_ref: ?string, serial_ref: ?string, received_qty: string, inventory_item_id: string, discrepancy_note: ?string}>  $lines
     * @return string the persisted receipt identifier
     */
    public function storeReceipt(
        string $asnId,
        string $warehouseId,
        string $receptionLocationId,
        string $actorId,
        string $correlationId,
        array $lines,
    ): string;

    /** @return list<ReceiptLine> */
    public function linesForReceipt(string $receiptId): array;

    /**
     * Lock every receipt line of a receipt in ascending primary-key order.
     *
     * @return list<ReceiptLine>
     */
    public function lockLinesForReceipt(string $receiptId): array;

    public function lockLine(string $receiptLineId): ?ReceiptLine;

    public function updateReceiptStatus(string $receiptId, ReceiptStatus $status): void;

    /**
     * Persist one completed put-away task.
     *
     * @return string the persisted put-away task identifier
     */
    public function storePutAway(
        string $receiptId,
        string $receiptLineId,
        string $inventoryItemId,
        string $sourceLocationId,
        string $destinationLocationId,
        string $quantity,
        string $actorId,
        string $correlationId,
    ): string;
}
