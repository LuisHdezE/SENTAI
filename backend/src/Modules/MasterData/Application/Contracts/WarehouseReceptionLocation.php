<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

/**
 * Read-only projection of the structural Warehouse -> reception Location mapping.
 *
 * This is the only representation of reception configuration that other modules
 * (Inventory) may consume. It intentionally exposes no MasterData internals.
 */
final readonly class WarehouseReceptionLocation
{
    public function __construct(
        public string $warehouseId,
        public string $locationId,
        public string $zoneId,
    ) {}
}
