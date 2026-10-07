<?php

namespace Sentai\Modules\Inventory\Application\DTO;

use Sentai\Modules\Inventory\Domain\Entities\AsnLine;

/**
 * Fully resolved receipt input for a single ASN line: the ASN line, the effectively
 * received quantity and the discrepancy facts declared by the operator.
 */
final readonly class ReceiveAsnLineInput
{
    public function __construct(
        public AsnLine $asnLine,
        public string $receivedQty,
        public ?string $lotRef,
        public ?string $serialRef,
        public ?string $discrepancyNote,
    ) {}
}
