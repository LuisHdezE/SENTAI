<?php

namespace Sentai\Modules\Audit\Domain\Entities;

final readonly class AuditEvent
{
    /**
     * @param  list<string>  $actorRoleSnapshot
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $id,
        public string $eventType,
        public ?string $actorId,
        public array $actorRoleSnapshot,
        public ?string $aggregateType,
        public ?string $aggregateId,
        public string $operation,
        public string $outcome,
        public string $correlationId,
        public string $sourceSurface,
        public array $context,
        public string $occurredAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->eventType,
            'actor_id' => $this->actorId,
            'actor_roles' => $this->actorRoleSnapshot,
            'aggregate_type' => $this->aggregateType,
            'aggregate_id' => $this->aggregateId,
            'operation' => $this->operation,
            'outcome' => $this->outcome,
            'correlation_id' => $this->correlationId,
            'source_surface' => $this->sourceSurface,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
