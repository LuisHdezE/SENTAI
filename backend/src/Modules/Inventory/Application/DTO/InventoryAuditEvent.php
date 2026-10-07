<?php

namespace Sentai\Modules\Inventory\Application\DTO;

/** Durable audit evidence for one Inventory business mutation. */
final readonly class InventoryAuditEvent
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $eventType,
        public string $aggregateType,
        public string $aggregateId,
        public string $operation,
        public string $actorId,
        public string $correlationId,
        public string $sourceSurface,
        public array $context,
    ) {}
}
