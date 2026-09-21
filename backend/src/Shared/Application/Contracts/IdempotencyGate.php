<?php

namespace Sentai\Shared\Application\Contracts;

use Sentai\Shared\Application\DTO\IdempotentResponse;

interface IdempotencyGate
{
    /** @param callable(): IdempotentResponse $operation */
    public function execute(
        string $operationScope,
        string $idempotencyKey,
        string $requestHash,
        callable $operation,
    ): IdempotentResponse;
}
