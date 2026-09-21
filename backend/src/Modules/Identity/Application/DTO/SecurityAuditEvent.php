<?php

namespace Sentai\Modules\Identity\Application\DTO;

use DateTimeImmutable;

final readonly class SecurityAuditEvent
{
    /**
     * @param list<string> $actorRoleSnapshot
     * @param array<string, scalar|null> $context
     */
    public function __construct(
        public string $eventType,
        public ?string $actorId,
        public array $actorRoleSnapshot,
        public string $operation,
        public string $outcome,
        public string $correlationId,
        public string $sourceSurface,
        public DateTimeImmutable $occurredAt,
        public ?string $aggregateType = null,
        public ?string $aggregateId = null,
        public array $context = [],
    ) {
    }
}
