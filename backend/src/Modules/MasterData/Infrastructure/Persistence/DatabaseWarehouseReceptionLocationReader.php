<?php

namespace Sentai\Modules\MasterData\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Sentai\Modules\MasterData\Application\Contracts\WarehouseReceptionLocation;
use Sentai\Modules\MasterData\Application\Contracts\WarehouseReceptionLocationReader;

/**
 * Reads the structural reception mapping owned by Master Data.
 *
 * The mapping itself is structural Master Data: a Warehouse has at most one
 * designated reception Location. It carries no operational block/unblock state,
 * which belongs to Inventory.
 */
final class DatabaseWarehouseReceptionLocationReader implements WarehouseReceptionLocationReader
{
    public function forWarehouse(string $warehouseId): ?WarehouseReceptionLocation
    {
        $row = DB::table('warehouse_reception_locations')
            ->join('locations', 'locations.id', '=', 'warehouse_reception_locations.location_id')
            ->where('warehouse_reception_locations.warehouse_id', $warehouseId)
            ->select([
                'warehouse_reception_locations.warehouse_id',
                'warehouse_reception_locations.location_id',
                'locations.zone_id',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return new WarehouseReceptionLocation(
            (string) $row->warehouse_id,
            (string) $row->location_id,
            (string) $row->zone_id,
        );
    }
}
