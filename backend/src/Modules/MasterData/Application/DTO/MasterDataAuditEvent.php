<?php

namespace Sentai\Modules\MasterData\Application\DTO;

use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;

final readonly class MasterDataAuditEvent
{
    /** @param list<string> $changedFields */
    public function __construct(
        public MasterType $type,
        public string $aggregateId,
        public string $operation,
        public string $actorId,
        public string $correlationId,
        public array $changedFields,
    ) {}
}
