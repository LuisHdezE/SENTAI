<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Durable reason recorded for adjustment, movement and location block operations.
 *
 * FR-005 and the API-INV-004..007 contracts require a mandatory reason. The value is
 * persisted as an opaque, traceable reference; no reason taxonomy is invented here.
 */
final readonly class InventoryReason
{
    public function __construct(public string $value)
    {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException('An inventory operation reason is required.');
        }

        if (mb_strlen($this->value) > 255) {
            throw new InvalidArgumentException('An inventory operation reason may not exceed 255 characters.');
        }
    }

    public static function fromRequest(string $value): self
    {
        return new self(trim($value));
    }
}
