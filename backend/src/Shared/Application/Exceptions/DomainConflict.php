<?php

namespace Sentai\Shared\Application\Exceptions;

use RuntimeException;

/**
 * A deterministic domain/business conflict. Never retried as a transient failure.
 *
 * Optional structured context is surfaced in the RFC 9457 problem document so that
 * clients can distinguish e.g. a missing configuration from a quantity violation.
 */
final class DomainConflict extends RuntimeException
{
    /** @param array<string, mixed> $context */
    public function __construct(string $message, private readonly array $context = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return $this->context;
    }
}
