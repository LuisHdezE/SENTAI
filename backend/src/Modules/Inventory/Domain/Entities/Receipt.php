<?php

namespace Sentai\Modules\Inventory\Domain\Entities;

use Sentai\Modules\Inventory\Domain\ValueObjects\ReceiptStatus;

/**
 * Receipt aggregate: the durable record of physical inbound merchandise (API-INV-001).
 */
final readonly class Receipt
{
    public function __construct(
        public string $id,
        public string $asnId,
        public string $warehouseId,
        public string $receptionLocationId,
        public ReceiptStatus $status,
    ) {}
}
