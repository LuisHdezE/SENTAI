<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

/**
 * Read seam for the structural Warehouse -> reception Location mapping.
 *
 * Inventory resolves the reception Location exclusively through this contract,
 * never through MasterData Domain or Infrastructure internals.
 */
interface WarehouseReceptionLocationReader
{
    public function forWarehouse(string $warehouseId): ?WarehouseReceptionLocation;
}
