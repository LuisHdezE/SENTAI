<?php

namespace Sentai\Modules\Audit\Application\DTO;

final readonly class AuditAccessContext
{
    /** @param list<string> $actorRoleSnapshot */
    public function __construct(
        public string $actorId,
        public array $actorRoleSnapshot,
        public string $correlationId,
        public string $sourceSurface,
    ) {}
}
