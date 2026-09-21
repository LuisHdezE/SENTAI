<?php

namespace Tests\Support;

use App\Database\Seeders\CanonicalAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;

trait CreatesIdentityFixtures
{
    protected function seedCanonicalAuthorization(): void
    {
        $this->seed(CanonicalAuthorizationSeeder::class);
    }

    protected function createUserWithRole(string $email, string $roleCode, string $password = 'Secret123!'): UserRecord
    {
        $user = UserRecord::query()->create([
            'email' => mb_strtolower($email),
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

        if ($roleId === null) {
            throw new \RuntimeException("Role {$roleCode} is not seeded.");
        }

        DB::table('user_roles')->insert([
            'user_id' => (string) $user->getKey(),
            'role_id' => (string) $roleId,
        ]);

        return $user;
    }

    protected function assignRole(UserRecord $user, string $roleCode): void
    {
        $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

        if ($roleId === null) {
            throw new \RuntimeException("Role {$roleCode} is not seeded.");
        }

        DB::table('user_roles')->insertOrIgnore([
            'user_id' => (string) $user->getKey(),
            'role_id' => (string) $roleId,
        ]);
    }

    protected function correlationId(): string
    {
        return (string) Str::uuid();
    }
}
