<?php

namespace Sentai\Modules\MasterData\Infrastructure\Persistence;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataRepository;
use Sentai\Modules\MasterData\Domain\Entities\MasterDataRecord;
use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;
use stdClass;

final class DatabaseMasterDataRepository implements MasterDataRepository
{
    public function list(MasterType $type, array $filters): array
    {
        $query = DB::table($this->table($type));

        if (($filters['q'] ?? null) !== null) {
            $term = '%'.(string) $filters['q'].'%';
            $query->where(static function ($builder) use ($term): void {
                $builder->where('code', 'like', $term)->orWhere('name', 'like', $term);
            });
        }

        if (($filters['active'] ?? null) !== null) {
            $query->where('is_active', (bool) $filters['active']);
        }

        if (($filters['warehouse_id'] ?? null) !== null && in_array($type, [MasterType::Zone, MasterType::Location], true)) {
            $query->where('warehouse_id', (string) $filters['warehouse_id']);
        }

        if (($filters['zone_id'] ?? null) !== null && $type === MasterType::Location) {
            $query->where('zone_id', (string) $filters['zone_id']);
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = (clone $query)->count();
        $rows = $query
            ->orderBy('code')
            ->forPage($page, $perPage)
            ->get();

        return [
            'items' => $rows->map(fn (stdClass $row): MasterDataRecord => $this->hydrate($type, $row))->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function create(MasterType $type, array $payload): MasterDataRecord
    {
        $this->assertRelations($type, $payload);

        $id = (string) Str::ulid();
        $row = $this->rowForWrite($type, $payload);
        $row['id'] = $id;
        $row['created_at'] = now();
        $row['updated_at'] = now();

        try {
            DB::table($this->table($type))->insert($row);
        } catch (QueryException $exception) {
            $this->translateWriteException($exception);
        }

        return $this->getRequired($type, $id);
    }

    public function update(MasterType $type, string $id, array $payload): MasterDataRecord
    {
        $this->getRequired($type, $id);
        $this->assertRelations($type, $payload);

        $row = $this->rowForWrite($type, $payload);
        $row['updated_at'] = now();

        try {
            DB::table($this->table($type))->where('id', $id)->update($row);
        } catch (QueryException $exception) {
            $this->translateWriteException($exception);
        }

        return $this->getRequired($type, $id);
    }

    private function getRequired(MasterType $type, string $id): MasterDataRecord
    {
        $row = DB::table($this->table($type))->where('id', $id)->first();

        if ($row === null) {
            throw new ResourceNotFound('The requested master data record was not found.');
        }

        return $this->hydrate($type, $row);
    }

    /** @param array<string, mixed> $payload */
    private function assertRelations(MasterType $type, array $payload): void
    {
        if (in_array($type, [MasterType::Zone, MasterType::Location], true)) {
            $warehouseId = (string) $payload['warehouse_id'];

            if (! DB::table('warehouses')->where('id', $warehouseId)->exists()) {
                throw new DomainConflict('Referenced warehouse does not exist.');
            }
        }

        if ($type === MasterType::Location) {
            $zone = DB::table('zones')->where('id', (string) $payload['zone_id'])->first();

            if ($zone === null) {
                throw new DomainConflict('Referenced zone does not exist.');
            }

            if ((string) $zone->warehouse_id !== (string) $payload['warehouse_id']) {
                throw new DomainConflict('Location zone must belong to the referenced warehouse.');
            }
        }
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function rowForWrite(MasterType $type, array $payload): array
    {
        $row = [
            'code' => (string) $payload['code'],
            'name' => (string) $payload['name'],
            'is_active' => (bool) $payload['is_active'],
        ];

        if (in_array($type, [MasterType::Zone, MasterType::Location], true)) {
            $row['warehouse_id'] = (string) $payload['warehouse_id'];
        }

        if ($type === MasterType::Location) {
            $row['zone_id'] = (string) $payload['zone_id'];
        }

        return $row;
    }

    private function hydrate(MasterType $type, stdClass $row): MasterDataRecord
    {
        return new MasterDataRecord(
            (string) $row->id,
            $type,
            (string) $row->code,
            (string) $row->name,
            (bool) $row->is_active,
            isset($row->warehouse_id) ? (string) $row->warehouse_id : null,
            isset($row->zone_id) ? (string) $row->zone_id : null,
        );
    }

    private function table(MasterType $type): string
    {
        return match ($type) {
            MasterType::Product => 'products',
            MasterType::Customer => 'customers',
            MasterType::Warehouse => 'warehouses',
            MasterType::Zone => 'zones',
            MasterType::Location => 'locations',
        };
    }

    private function translateWriteException(QueryException $exception): never
    {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            throw new DomainConflict('A master data code already exists in the required scope.');
        }

        throw $exception;
    }
}
