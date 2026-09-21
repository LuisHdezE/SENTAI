<?php

namespace Sentai\Modules\Identity\Application\Contracts;

interface CapabilityLookup
{
    public function has(string $userId, string $capability): bool;
}
