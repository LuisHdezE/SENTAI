<?php

namespace Sentai\Modules\MasterData\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectory;
use Sentai\Modules\MasterData\Application\Contracts\LocationDirectoryEntry;

final class DatabaseLocationDirectory implements LocationDirectory
{
    public function find(string $locationId): ?LocationDirectoryEntry
    {
        return $this->hydrate(DB::table('locations')->where('id', $locationId)->first());
    }

    public function lock(string $locationId): ?LocationDirectoryEntry
    {
        return $this->hydrate(
            DB::table('locations')
                ->where('id', $locationId)
                ->lockForUpdate()
                ->first(),
        );
    }

    private function hydrate(?object $row): ?LocationDirectoryEntry
    {
        if ($row === null) {
            return null;
        }

        return new LocationDirectoryEntry(
            (string) $row->id,
            (string) $row->warehouse_id,
            (string) $row->zone_id,
            (string) $row->code,
            (bool) $row->is_active,
        );
    }
}
