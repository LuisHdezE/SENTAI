<?php

namespace Sentai\Modules\MasterData\Application\DTO;

final readonly class MasterMutationContext
{
    public function __construct(
        public string $actorId,
        public string $correlationId,
        public string $idempotencyKey,
    ) {}
}
