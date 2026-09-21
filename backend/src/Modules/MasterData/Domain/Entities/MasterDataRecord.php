<?php

namespace Sentai\Modules\MasterData\Domain\Entities;

use InvalidArgumentException;
use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;

final readonly class MasterDataRecord
{
    public function __construct(
        public string $id,
        public MasterType $type,
        public string $code,
        public string $name,
        public bool $isActive,
        public ?string $warehouseId = null,
        public ?string $zoneId = null,
    ) {
        if (trim($this->id) === '' || trim($this->code) === '' || trim($this->name) === '') {
            throw new InvalidArgumentException('Master data identity, code and name are required.');
        }

        if ($this->type === MasterType::Zone && $this->warehouseId === null) {
            throw new InvalidArgumentException('Zone requires a warehouse reference.');
        }

        if ($this->type === MasterType::Location && ($this->warehouseId === null || $this->zoneId === null)) {
            throw new InvalidArgumentException('Location requires warehouse and zone references.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => $this->isActive,
        ];

        if ($this->warehouseId !== null) {
            $data['warehouse_id'] = $this->warehouseId;
        }

        if ($this->zoneId !== null) {
            $data['zone_id'] = $this->zoneId;
        }

        return $data;
    }
}
