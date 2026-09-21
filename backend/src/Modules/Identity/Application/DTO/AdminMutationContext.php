<?php

namespace Sentai\Modules\Identity\Application\DTO;

final readonly class AdminMutationContext
{
    /** @param list<string> $actorRoleSnapshot */
    public function __construct(
        public string $actorId,
        public array $actorRoleSnapshot,
        public string $correlationId,
        public string $idempotencyKey,
    ) {}
}
