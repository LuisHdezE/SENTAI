<?php

namespace Sentai\Modules\Inventory\Domain\Entities;

/**
 * Expected line of an ASN. Preserved for receipt validation and discrepancy facts.
 */
final readonly class AsnLine
{
    public function __construct(
        public string $id,
        public string $asnId,
        public string $productId,
        public string $expectedQty,
        public ?string $lotRef,
        public ?string $serialRef,
    ) {}
}
