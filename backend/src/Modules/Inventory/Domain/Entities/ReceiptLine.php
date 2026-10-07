<?php

namespace Sentai\Modules\Inventory\Domain\Entities;

/**
 * Receipt line: traceable link between an ASN line, the quantity actually received,
 * and the InventoryItem created at the Warehouse reception Location.
 */
final readonly class ReceiptLine
{
    public function __construct(
        public string $id,
        public string $receiptId,
        public ?string $asnLineId,
        public string $productId,
        public ?string $lotRef,
        public ?string $serialRef,
        public string $receivedQty,
        public string $inventoryItemId,
        public ?string $discrepancyNote,
    ) {}
}
