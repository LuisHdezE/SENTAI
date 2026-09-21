<?php

namespace Sentai\Modules\Identity\Application\DTO;

use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;

final readonly class AuthenticatedIdentity
{
    /**
     * @param list<string> $roleCodes
     * @param list<string> $capabilityCodes
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $roleCodes,
        public array $capabilityCodes,
    ) {
    }

    public function hasRole(string $roleCode): bool
    {
        return in_array($roleCode, $this->roleCodes, true);
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilityCodes, true);
    }

    public function webSurface(): string
    {
        return $this->hasRole(RoleCodes::CUSTOMER) ? 'customer_portal' : 'backoffice';
    }
}
