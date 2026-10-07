<?php

namespace Sentai\Modules\Inventory\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Sentai\Modules\Inventory\Application\Contracts\InventoryLocationRepository;

final class DatabaseInventoryLocationRepository implements InventoryLocationRepository
{
    public function blockedStatus(array $locationIds): array
    {
        if ($locationIds === []) {
            return [];
        }

        $blocked = DB::table('inventory_location_blocks')
            ->whereIn('location_id', $locationIds)
            ->pluck('location_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        $status = [];

        foreach ($locationIds as $locationId) {
            $status[$locationId] = in_array($locationId, $blocked, true);
        }

        return $status;
    }

    public function isBlocked(string $locationId): bool
    {
        return DB::table('inventory_location_blocks')->where('location_id', $locationId)->exists();
    }

    public function lockBlock(string $locationId): ?string
    {
        $row = DB::table('inventory_location_blocks')
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();

        return $row === null ? null : (string) $row->location_id;
    }

    public function storeBlock(string $locationId, string $reason, string $actorId, string $correlationId): void
    {
        $now = now();

        DB::table('inventory_location_blocks')->insert([
            'location_id' => $locationId,
            'reason' => $reason,
            'blocked_by' => $actorId,
            'blocked_correlation_id' => $correlationId,
            'blocked_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function releaseBlock(string $locationId): void
    {
        DB::table('inventory_location_blocks')->where('location_id', $locationId)->delete();
    }
}
