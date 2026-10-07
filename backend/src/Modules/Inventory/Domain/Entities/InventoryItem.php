<?php

namespace Sentai\Modules\Inventory\Domain\Entities;

use InvalidArgumentException;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;

/**
 * InventoryItem aggregate: the transactional grouping of Stock (EVD-ARCH-001).
 *
 * Its consistency boundary preserves granular traceability by
 * Product x Location x Lot? x Serial? x State (FR-004) and the critical
 * quantity invariant `0 <= Reserved <= OnHand` (BR-001).
 */
final readonly class InventoryItem
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $locationId,
        public ?string $lotRef,
        public ?string $serialRef,
        public InventoryState $state,
        public string $onHandQty,
        public string $reservedQty,
        public bool $locationBlocked = false,
    ) {
        if (trim($this->id) === '' || trim($this->productId) === '' || trim($this->locationId) === '') {
            throw new InvalidArgumentException('Inventory item identity, product and location are required.');
        }

        if ($this->compare($this->reservedQty, '0') < 0 || $this->compare($this->onHandQty, '0') < 0) {
            throw new InvalidArgumentException('Inventory quantities may not be negative.');
        }

        if ($this->compare($this->reservedQty, $this->onHandQty) > 0) {
            throw new InvalidArgumentException('Reserved quantity may not exceed OnHand quantity.');
        }
    }

    /**
     * Commercial availability (BR-003): non-eligible states and operational-location
     * blocking withdraw stock from commercial availability even though it remains OnHand.
     */
    public function availableQty(): string
    {
        if (! $this->state->isCommerciallyEligible() || $this->locationBlocked) {
            return '0.0000';
        }

        return $this->subtract($this->onHandQty, $this->reservedQty);
    }

    public function canBeCommerciallyAllocated(): bool
    {
        return $this->state->isCommerciallyEligible() && ! $this->locationBlocked;
    }

    public function isInReception(): bool
    {
        return $this->state->isInReception();
    }

    /**
     * Natural identity of the aggregate: Product x Location x Lot? x Serial? x State.
     *
     * @return array<string, string|null>
     */
    public function identity(): array
    {
        return [
            'product_id' => $this->productId,
            'location_id' => $this->locationId,
            'lot_ref' => $this->lotRef,
            'serial_ref' => $this->serialRef,
            'state' => $this->state->value,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'location_id' => $this->locationId,
            'lot_ref' => $this->lotRef,
            'serial_ref' => $this->serialRef,
            'state' => $this->state->value,
            'on_hand_qty' => $this->onHandQty,
            'reserved_qty' => $this->reservedQty,
            'available_qty' => $this->availableQty(),
            'commercially_eligible' => $this->canBeCommerciallyAllocated(),
            'location_blocked' => $this->locationBlocked,
        ];
    }

    private function compare(string $left, string $right): int
    {
        return bccomp($left, $right, 4);
    }

    private function subtract(string $left, string $right): string
    {
        return bcsub($left, $right, 4);
    }
}
