<?php

namespace Sentai\Modules\Identity\Domain\Entities;

final readonly class AdminUser
{
    /** @param list<string> $roleCodes */
    public function __construct(
        public string $id,
        public string $email,
        public bool $isActive,
        public array $roleCodes,
    ) {}

    /** @return array{id: string, email: string, is_active: bool, roles: list<string>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'roles' => $this->roleCodes,
        ];
    }
}
