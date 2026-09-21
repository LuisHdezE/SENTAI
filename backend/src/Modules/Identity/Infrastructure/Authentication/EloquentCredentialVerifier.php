<?php

namespace Sentai\Modules\Identity\Infrastructure\Authentication;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;

final class EloquentCredentialVerifier implements CredentialVerifier
{
    private const DUMMY_BCRYPT_HASH = '$2y$12$9xSC.hi4vGrWppAXFXm7UuehAoA6/MwoD/8EuRHvM6vrgZYkoykE2';

    public function verify(string $email, string $password): ?AuthenticatedIdentity
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $user = UserRecord::query()
            ->where('email', $normalizedEmail)
            ->where('is_active', true)
            ->first();

        if ($user === null) {
            Hash::check($password, self::DUMMY_BCRYPT_HASH);

            return null;
        }

        if (! Hash::check($password, (string) $user->getAuthPassword())) {
            return null;
        }

        return $this->identityFor((string) $user->getAuthIdentifier(), (string) $user->email);
    }

    public function byId(string $userId): ?AuthenticatedIdentity
    {
        $user = UserRecord::query()
            ->whereKey($userId)
            ->where('is_active', true)
            ->first();

        if ($user === null) {
            return null;
        }

        return $this->identityFor((string) $user->getAuthIdentifier(), (string) $user->email);
    }

    private function identityFor(string $userId, string $email): AuthenticatedIdentity
    {
        $roles = DB::table('roles')
            ->join('user_roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $userId)
            ->orderBy('roles.code')
            ->pluck('roles.code')
            ->map(static fn ($value): string => (string) $value)
            ->all();

        $capabilities = DB::table('permissions')
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->join('user_roles', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->where('user_roles.user_id', $userId)
            ->distinct()
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->map(static fn ($value): string => (string) $value)
            ->all();

        return new AuthenticatedIdentity($userId, $email, $roles, $capabilities);
    }
}
