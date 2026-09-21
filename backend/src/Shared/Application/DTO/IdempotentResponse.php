<?php

namespace Sentai\Shared\Application\DTO;

final readonly class IdempotentResponse
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public array $body,
        public int $status,
        public bool $replayed = false,
    ) {}
}
