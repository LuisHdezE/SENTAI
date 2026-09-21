<?php

namespace Sentai\Modules\Identity\Infrastructure\Authorization;

use Illuminate\Support\Facades\DB;
use Sentai\Modules\Identity\Application\Contracts\CapabilityLookup;

final class EloquentCapabilityLookup implements CapabilityLookup
{
    public function has(string $userId, string $capability): bool
    {
        return DB::table('permissions')
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->join('user_roles', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('permissions.code', $capability)
            ->exists();
    }
}
