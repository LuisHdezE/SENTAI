<?php

namespace Sentai\Modules\Inventory\Application\DTO;

/**
 * Mutation context for Inventory operations: actor, request correlation and the
 * caller-supplied durable idempotency key.
 */
final readonly class InventoryMutationContext
{
    public function __construct(
        public string $actorId,
        public string $correlationId,
        public string $idempotencyKey,
        public string $sourceSurface,
    ) {}
}
