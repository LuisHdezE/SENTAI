<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Inventory state of an InventoryItem (FR-004 / BR-003 / BR-004).
 *
 * `ReceivedPendingPutAway` is the state of stock committed by API-INV-001 into a
 * Warehouse reception Location. It counts as OnHand but is not eligible for normal
 * commercial assignment until confirmPutAway (API-INV-002) moves it to its destination.
 *
 * The canonical inventory state vocabulary of EVD-ARCH-AUDIT-001 is available
 * (`Blocked`, `Quarantine`, `Damaged`, `Expired`). Only the transitions required by
 * API-INV-001..007 are reachable in this increment.
 */
enum InventoryState: string
{
    case ReceivedPendingPutAway = 'received_pending_putaway';
    case Available = 'available';
    case Blocked = 'blocked';
    case Quarantine = 'quarantine';
    case Damaged = 'damaged';
    case Expired = 'expired';

    /**
     * BR-003: only eligible stock participates in commercial availability.
     * BR-004: blocked or quarantined stock is withdrawn from standard eligibility.
     */
    public function isCommerciallyEligible(): bool
    {
        return $this === self::Available;
    }

    /** Stock that physically exists in a Warehouse reception Location before put-away. */
    public function isInReception(): bool
    {
        return $this === self::ReceivedPendingPutAway;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $state): string => $state->value, self::cases());
    }

    public static function fromStateValue(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Unknown inventory state [{$value}].");
    }
}
