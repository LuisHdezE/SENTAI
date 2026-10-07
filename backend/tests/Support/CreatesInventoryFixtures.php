<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;

/**
 * Seeds Master Data and Inventory fixtures directly through SQL.
 *
 * The Master Data HTTP surface has no operation that designates a Warehouse reception
 * Location (API-MAST-014 is a separate operability dependency), so reception
 * configuration is seeded as data exactly as an administration operation would persist it.
 */
trait CreatesInventoryFixtures
{
    /** @return array{warehouse_id: string, zone_id: string, location_id: string} */
    protected function createLocation(string $warehouseCode, string $locationCode, string $zoneCode = 'ZONE-01', bool $active = true): array
    {
        $warehouseId = DB::table('warehouses')->where('code', $warehouseCode)->value('id');

        if ($warehouseId === null) {
            $warehouseId = (string) Str::ulid();
            $now = now();
            DB::table('warehouses')->insert([
                'id' => $warehouseId,
                'code' => $warehouseCode,
                'name' => $warehouseCode,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $zoneId = DB::table('zones')->where('warehouse_id', $warehouseId)->where('code', $zoneCode)->value('id');

        if ($zoneId === null) {
            $zoneId = (string) Str::ulid();
            $now = now();
            DB::table('zones')->insert([
                'id' => $zoneId,
                'warehouse_id' => $warehouseId,
                'code' => $zoneCode,
                'name' => $zoneCode,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $locationId = (string) Str::ulid();
        $now = now();
        DB::table('locations')->insert([
            'id' => $locationId,
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'code' => $locationCode,
            'name' => $locationCode,
            'is_active' => $active,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'warehouse_id' => (string) $warehouseId,
            'zone_id' => (string) $zoneId,
            'location_id' => $locationId,
        ];
    }

    protected function designateReceptionLocation(string $warehouseId, string $locationId): void
    {
        $now = now();

        DB::table('warehouse_reception_locations')->insert([
            'warehouse_id' => $warehouseId,
            'location_id' => $locationId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function createProduct(string $code, string $name = 'Product'): string
    {
        $id = (string) Str::ulid();
        $now = now();

        DB::table('products')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    protected function createAsn(string $warehouseId, string $reference, string $status = 'approved'): string
    {
        $id = (string) Str::ulid();
        $now = now();

        DB::table('asns')->insert([
            'id' => $id,
            'reference' => $reference,
            'warehouse_id' => $warehouseId,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    protected function createAsnLine(string $asnId, string $productId, string $expectedQty, ?string $lotRef = null, ?string $serialRef = null): string
    {
        $id = (string) Str::ulid();
        $now = now();

        DB::table('asn_lines')->insert([
            'id' => $id,
            'asn_id' => $asnId,
            'product_id' => $productId,
            'lot_ref' => $lotRef,
            'serial_ref' => $serialRef,
            'expected_qty' => $expectedQty,
            'received_qty' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    /**
     * Seeds an InventoryItem directly, for scenarios that start from existing stock.
     */
    protected function createInventoryItem(
        string $productId,
        string $locationId,
        string $onHandQty,
        string $reservedQty = '0.0000',
        InventoryState $state = InventoryState::Available,
        ?string $lotRef = null,
        ?string $serialRef = null,
    ): string {
        $id = (string) Str::ulid();
        $now = now();

        DB::table('inventory_items')->insert([
            'id' => $id,
            'product_id' => $productId,
            'location_id' => $locationId,
            'lot_ref' => $lotRef,
            'serial_ref' => $serialRef,
            'state' => $state->value,
            'on_hand_qty' => $onHandQty,
            'reserved_qty' => $reservedQty,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    /** @return array<string, mixed> */
    protected function inventoryRow(string $id): array
    {
        $row = DB::table('inventory_items')->where('id', $id)->first();

        if ($row === null) {
            throw new \RuntimeException("Inventory item {$id} does not exist.");
        }

        return (array) $row;
    }
}
