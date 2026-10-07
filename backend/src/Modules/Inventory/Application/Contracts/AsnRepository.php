<?php

namespace Sentai\Modules\Inventory\Application\Contracts;

use Sentai\Modules\Inventory\Domain\Entities\Asn;
use Sentai\Modules\Inventory\Domain\Entities\AsnLine;

/**
 * ASN persistence seam used by the receipt operation.
 */
interface AsnRepository
{
    public function find(string $id): ?Asn;

    public function lock(string $id): ?Asn;

    /** @return list<AsnLine> */
    public function lines(string $asnId): array;

    public function incrementReceivedQty(string $asnLineId, string $delta): void;

    public function updateStatus(string $asnId, string $status): void;

    public function isFullyReceived(string $asnId): bool;
}
