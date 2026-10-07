<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

/**
 * Lifecycle of an ASN as far as receipt (API-INV-001) is concerned.
 *
 * ASN authoring/approval is explicitly deferred by the API contract closure, so only
 * the states observable by the receipt operation exist here.
 */
enum AsnStatus: string
{
    case Approved = 'approved';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';

    public function isReceivable(): bool
    {
        return $this === self::Approved || $this === self::PartiallyReceived;
    }
}
