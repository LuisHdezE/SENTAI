<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

/**
 * Read-only projection of a structural warehouse Location as other modules may consume it.
 *
 * Operational block/unblock state deliberately does NOT appear here: it belongs to
 * Inventory and is persisted separately from `locations`.
 */
final readonly class LocationDirectoryEntry
{
    public function __construct(
        public string $locationId,
        public string $warehouseId,
        public string $zoneId,
        public string $code,
        public bool $isActive,
    ) {}
}
