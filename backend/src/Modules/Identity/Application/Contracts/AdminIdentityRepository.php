<?php

namespace Sentai\Modules\Identity\Application\Contracts;

use Sentai\Modules\Identity\Domain\Entities\AdminUser;

interface AdminIdentityRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<AdminUser>, page: int, per_page: int, total: int}
     */
    public function list(array $filters): array;

    /** @param array<string, mixed> $payload */
    public function create(array $payload): AdminUser;

    /** @param array<string, mixed> $payload */
    public function update(string $id, array $payload): AdminUser;

    public function disable(string $id): AdminUser;

    /** @return array{user_id: string, role_id: string, role_code: string} */
    public function assignRole(string $userId, string $roleId): array;

    /** @return array{user_id: string, role_id: string, role_code: string} */
    public function revokeRole(string $userId, string $roleId): array;

    /** @return array{web_sessions: int, mobile_sessions: int, mobile_tokens: int} */
    public function revokeSessions(string $userId): array;
}
