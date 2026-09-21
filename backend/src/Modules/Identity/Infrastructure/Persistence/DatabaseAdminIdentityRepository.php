<?php

namespace Sentai\Modules\Identity\Infrastructure\Persistence;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Sentai\Modules\Identity\Application\Contracts\AdminIdentityRepository;
use Sentai\Modules\Identity\Domain\Entities\AdminUser;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;
use stdClass;

final class DatabaseAdminIdentityRepository implements AdminIdentityRepository
{
    public function list(array $filters): array
    {
        $query = UserRecord::query();

        if (($filters['q'] ?? null) !== null) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['q']).'%';
            $query->where('email', 'like', $term);
        }

        if (($filters['active'] ?? null) !== null) {
            $query->where('is_active', (bool) $filters['active']);
        }

        if (($filters['role_code'] ?? null) !== null) {
            $roleCode = (string) $filters['role_code'];
            $query->whereExists(function ($subquery) use ($roleCode): void {
                $subquery->selectRaw('1')
                    ->from('user_roles')
                    ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->whereColumn('user_roles.user_id', 'users.id')
                    ->where('roles.code', $roleCode);
            });
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = (clone $query)->count();
        $users = $query
            ->orderBy('email')
            ->orderBy('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'items' => $users->map(fn (UserRecord $user): AdminUser => $this->toEntity($user))->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function create(array $payload): AdminUser
    {
        $email = mb_strtolower(trim((string) $payload['email']));
        $this->assertEmailAvailable($email);

        try {
            $user = UserRecord::query()->create([
                'email' => $email,
                'password' => (string) $payload['password'],
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ]);
        } catch (QueryException $exception) {
            $this->rethrowDuplicateEmail($exception);
            throw $exception;
        }

        return $this->toEntity($user);
    }

    public function update(string $id, array $payload): AdminUser
    {
        $user = UserRecord::query()->whereKey($id)->lockForUpdate()->first();

        if (! $user instanceof UserRecord) {
            throw new ResourceNotFound('User not found.');
        }

        if (array_key_exists('email', $payload)) {
            $email = mb_strtolower(trim((string) $payload['email']));
            $this->assertEmailAvailable($email, $id);
            $user->email = $email;
        }

        $credentialChanged = array_key_exists('password', $payload);

        if ($credentialChanged) {
            $user->password = (string) $payload['password'];
        }

        try {
            $user->save();
        } catch (QueryException $exception) {
            $this->rethrowDuplicateEmail($exception);
            throw $exception;
        }

        if ($credentialChanged) {
            $this->revokeSessions($id);
        }

        return $this->toEntity($user->fresh() ?? $user);
    }

    public function disable(string $id): AdminUser
    {
        $user = UserRecord::query()->whereKey($id)->lockForUpdate()->first();

        if (! $user instanceof UserRecord) {
            throw new ResourceNotFound('User not found.');
        }

        if (! $user->is_active) {
            throw new DomainConflict('User is already disabled.');
        }

        $user->is_active = false;
        $user->save();
        $this->revokeSessions($id);

        return $this->toEntity($user);
    }

    public function assignRole(string $userId, string $roleId): array
    {
        $this->lockUser($userId);
        $role = $this->lockRole($roleId);

        $exists = DB::table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->exists();

        if ($exists) {
            throw new DomainConflict('The role is already assigned to this user.');
        }

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);

        $this->revokeSessions($userId);

        return [
            'user_id' => $userId,
            'role_id' => $roleId,
            'role_code' => (string) $role->code,
        ];
    }

    public function revokeRole(string $userId, string $roleId): array
    {
        $this->lockUser($userId);
        $role = $this->lockRole($roleId);

        $deleted = DB::table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->delete();

        if ($deleted !== 1) {
            throw new DomainConflict('The role is not assigned to this user.');
        }

        $this->revokeSessions($userId);

        return [
            'user_id' => $userId,
            'role_id' => $roleId,
            'role_code' => (string) $role->code,
        ];
    }

    private function toEntity(UserRecord $user): AdminUser
    {
        $roles = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', (string) $user->getKey())
            ->orderBy('roles.code')
            ->pluck('roles.code')
            ->map(static fn (mixed $role): string => (string) $role)
            ->values()
            ->all();

        return new AdminUser(
            (string) $user->getKey(),
            (string) $user->email,
            (bool) $user->is_active,
            $roles,
        );
    }

    private function assertEmailAvailable(string $email, ?string $exceptId = null): void
    {
        $query = UserRecord::query()->where('email', $email);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw new DomainConflict('An account with that email already exists.');
        }
    }

    private function rethrowDuplicateEmail(QueryException $exception): void
    {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
            throw new DomainConflict('An account with that email already exists.', previous: $exception);
        }
    }

    private function lockUser(string $userId): void
    {
        $user = UserRecord::query()->whereKey($userId)->lockForUpdate()->first();

        if (! $user instanceof UserRecord) {
            throw new ResourceNotFound('User not found.');
        }
    }

    private function lockRole(string $roleId): stdClass
    {
        $role = DB::table('roles')->where('id', $roleId)->lockForUpdate()->first();

        if (! $role instanceof stdClass) {
            throw new ResourceNotFound('Role not found.');
        }

        return $role;
    }

    private function revokeSessions(string $userId): void
    {
        DB::table('sessions')->where('user_id', $userId)->delete();

        $mobileSessionIds = DB::table('mobile_sessions')
            ->where('user_id', $userId)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        if ($mobileSessionIds === []) {
            return;
        }

        $now = now();

        DB::table('mobile_access_tokens')
            ->whereIn('mobile_session_id', $mobileSessionIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now, 'updated_at' => $now]);

        DB::table('mobile_sessions')
            ->whereIn('id', $mobileSessionIds)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now, 'updated_at' => $now]);
    }
}
