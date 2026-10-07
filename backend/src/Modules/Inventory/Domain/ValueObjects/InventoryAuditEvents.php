<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

/**
 * Canonical audit event types for Inventory (EVD-ARCH-AUDIT-001 §2.4).
 */
final class InventoryAuditEvents
{
    public const ASN_RECEIVED = 'inventory.asn.received';

    public const PUTAWAY_COMPLETED = 'inventory.putaway.completed';

    public const ADJUSTMENT_APPLIED = 'inventory.adjustment.applied';

    public const MOVE_COMPLETED = 'inventory.move.completed';

    public const LOCATION_BLOCKED = 'inventory.location.blocked';

    public const LOCATION_UNBLOCKED = 'inventory.location.unblocked';

    private function __construct() {}
}
