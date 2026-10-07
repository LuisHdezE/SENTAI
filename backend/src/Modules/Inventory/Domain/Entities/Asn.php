<?php

namespace Sentai\Modules\Inventory\Domain\Entities;

use Sentai\Modules\Inventory\Domain\ValueObjects\AsnStatus;

/**
 * ASN aggregate: the consistency boundary for expected inbound merchandise (UC-003).
 *
 * ASN authoring/approval is explicitly deferred outside the receipt contract; API-INV-001
 * only requires that a valid ASN exists before a receipt can be committed.
 */
final readonly class Asn
{
    public function __construct(
        public string $id,
        public string $warehouseId,
        public AsnStatus $status,
    ) {}

    public function isReceivable(): bool
    {
        return $this->status->isReceivable();
    }
}
