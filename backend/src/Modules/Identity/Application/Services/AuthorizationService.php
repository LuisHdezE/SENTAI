<?php

namespace Sentai\Modules\Identity\Application\Services;

use Sentai\Modules\Identity\Application\Contracts\CapabilityLookup;
use Sentai\Modules\Identity\Application\Exceptions\AuthorizationDenied;

final readonly class AuthorizationService
{
    public function __construct(private CapabilityLookup $capabilities)
    {
    }

    public function assertCapability(string $userId, string $capability): void
    {
        if (! $this->capabilities->has($userId, $capability)) {
            throw new AuthorizationDenied($capability);
        }
    }
}
