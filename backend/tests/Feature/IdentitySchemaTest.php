<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class IdentitySchemaTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    public function test_identity_schema_and_canonical_authorization_map_are_persisted(): void
    {
        foreach ([
            'users',
            'roles',
            'permissions',
            'user_roles',
            'role_permissions',
            'sessions',
            'mobile_sessions',
            'mobile_access_tokens',
            'audit_events',
        ] as $table) {
            self::assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->seedCanonicalAuthorization();

        self::assertSame(count(Capabilities::ALL), DB::table('permissions')->count());
        self::assertSame(23, DB::table('permissions')->count());
        self::assertSame(count(RoleCodes::ALL), DB::table('roles')->count());
        self::assertSame(8, DB::table('roles')->count());

        $supervisorCapabilities = $this->capabilitiesForRole(RoleCodes::WAREHOUSE_SUPERVISOR);
        self::assertContains(Capabilities::FULFILLMENT_ALLOCATE, $supervisorCapabilities);

        $adminCapabilities = $this->capabilitiesForRole(RoleCodes::ADMINISTRATOR);
        self::assertNotContains(Capabilities::AUDIT_GLOBAL_READ, $adminCapabilities);

        self::assertSame(
            [Capabilities::AUDIT_GLOBAL_READ],
            $this->capabilitiesForRole(RoleCodes::AUDIT_VIEWER),
        );
    }

    /** @return list<string> */
    private function capabilitiesForRole(string $roleCode): array
    {
        return DB::table('permissions')
            ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
            ->where('roles.code', $roleCode)
            ->orderBy('permissions.code')
            ->pluck('permissions.code')
            ->map(static fn ($value): string => (string) $value)
            ->all();
    }
}
