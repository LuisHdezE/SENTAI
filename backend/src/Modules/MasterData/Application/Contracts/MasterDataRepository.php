<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

use Sentai\Modules\MasterData\Domain\Entities\MasterDataRecord;
use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;

interface MasterDataRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<MasterDataRecord>, page: int, per_page: int, total: int}
     */
    public function list(MasterType $type, array $filters): array;

    /** @param array<string, mixed> $payload */
    public function create(MasterType $type, array $payload): MasterDataRecord;

    /** @param array<string, mixed> $payload */
    public function update(MasterType $type, string $id, array $payload): MasterDataRecord;
}
