<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceWebAbsoluteSessionLifetime;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class AdminAuditApiTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    private UserRecord $administrator;

    private UserRecord $auditViewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();
        $this->administrator = $this->createUserWithRole('admin-i4@example.test', RoleCodes::ADMINISTRATOR);
        $this->auditViewer = $this->createUserWithRole('audit-i4@example.test', RoleCodes::AUDIT_VIEWER);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->asWeb($this->administrator);
    }

    public function test_all_seven_admin_and_audit_contract_routes_are_registered(): void
    {
        $routes = [
            'api.v1.listAdminUsers' => 'GET',
            'api.v1.createAdminUser' => 'POST',
            'api.v1.updateAdminUser' => 'PUT',
            'api.v1.disableAdminUser' => 'PUT',
            'api.v1.assignUserRole' => 'POST',
            'api.v1.revokeUserRole' => 'DELETE',
            'api.v1.listAuditEvents' => 'GET',
        ];

        foreach ($routes as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, $name);
            self::assertContains($method, $route->methods(), $name);
        }
    }

    public function test_admin_user_lifecycle_is_idempotent_audited_and_never_echoes_passwords(): void
    {
        $payload = [
            'email' => 'managed-user@example.test',
            'password' => 'NeverEchoThisCredential!23',
            'is_active' => true,
        ];

        $created = $this->postJson('/api/v1/admin/users', $payload, ['Idempotency-Key' => 'admin-user-create-001'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('data.email', 'managed-user@example.test')
            ->assertJsonPath('data.is_active', true);
        $userId = (string) $created->json('data.id');

        self::assertStringNotContainsString('NeverEchoThisCredential!23', $created->getContent());

        $this->postJson('/api/v1/admin/users', $payload, ['Idempotency-Key' => 'admin-user-create-001'])
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $userId);

        $this->postJson('/api/v1/admin/users', [
            'email' => 'changed-request@example.test',
            'password' => 'NeverEchoThisCredential!23',
            'is_active' => true,
        ], ['Idempotency-Key' => 'admin-user-create-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/idempotency_conflict');

        $this->putJson('/api/v1/admin/users/'.$userId, [
            'email' => 'managed-user-updated@example.test',
            'password' => 'ReplacementCredential!45',
        ], ['Idempotency-Key' => 'admin-user-update-001'])
            ->assertOk()
            ->assertJsonPath('data.email', 'managed-user-updated@example.test');

        $this->getJson('/api/v1/admin/users?q=managed-user-updated&active=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $userId);

        $this->putJson('/api/v1/admin/users/'.$userId.'/disable', ['reason' => 'employment ended'], ['Idempotency-Key' => 'admin-user-disable-001'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->putJson('/api/v1/admin/users/'.$userId.'/disable', ['reason' => 'employment ended'], ['Idempotency-Key' => 'admin-user-disable-001'])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        self::assertSame(1, DB::table('audit_events')->where('event_type', 'admin.user.created')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'admin.user.updated')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'admin.user.disabled')->count());

        $auditJson = DB::table('audit_events')
            ->whereIn('event_type', ['admin.user.created', 'admin.user.updated', 'admin.user.disabled'])
            ->pluck('context')
            ->implode(' ');
        self::assertStringNotContainsString('NeverEchoThisCredential!23', $auditJson);
        self::assertStringNotContainsString('ReplacementCredential!45', $auditJson);
    }

    public function test_password_change_disable_and_role_changes_revoke_target_sessions_with_durable_evidence(): void
    {
        $target = $this->createUserWithRole('session-target@example.test', RoleCodes::WAREHOUSE_OPERATOR);
        $targetId = (string) $target->getKey();

        $this->seedSessionsFor($targetId, 'before-password');

        $this->putJson('/api/v1/admin/users/'.$targetId, [
            'password' => 'RotatedCredential!67',
        ], ['Idempotency-Key' => 'password-update-001'])->assertOk();

        $this->assertSessionsRevoked($targetId);

        $this->seedSessionsFor($targetId, 'before-role');
        $auditRoleId = (string) DB::table('roles')->where('code', RoleCodes::AUDIT_VIEWER)->value('id');

        $this->postJson('/api/v1/admin/users/'.$targetId.'/roles', ['role_id' => $auditRoleId], ['Idempotency-Key' => 'role-assign-001'])
            ->assertOk()
            ->assertJsonPath('data.role_code', RoleCodes::AUDIT_VIEWER);

        $this->assertSessionsRevoked($targetId);
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'authz.role.assigned')->count());

        $this->seedSessionsFor($targetId, 'before-revoke');

        $this->deleteJson('/api/v1/admin/users/'.$targetId.'/roles/'.$auditRoleId, [], ['Idempotency-Key' => 'role-revoke-001'])
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertSessionsRevoked($targetId);
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'authz.role.revoked')->count());
        self::assertSame(3, DB::table('audit_events')->where('event_type', 'auth.session.revoked')->count());
        self::assertSame(3, DB::table('audit_events')->where('event_type', 'auth.token.revoked')->count());

        $revocationAudit = DB::table('audit_events')
            ->whereIn('event_type', ['auth.session.revoked', 'auth.token.revoked'])
            ->pluck('context')
            ->implode(' ');
        self::assertStringNotContainsString('before-password-refresh', $revocationAudit);
        self::assertStringNotContainsString('before-role-access', $revocationAudit);
    }

    public function test_disable_revokes_existing_target_sessions_and_records_revocation_events(): void
    {
        $target = $this->createUserWithRole('disable-target@example.test', RoleCodes::WAREHOUSE_OPERATOR);
        $targetId = (string) $target->getKey();
        $this->seedSessionsFor($targetId, 'before-disable');

        $this->putJson('/api/v1/admin/users/'.$targetId.'/disable', ['reason' => 'access revoked'], ['Idempotency-Key' => 'disable-with-session-001'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertSessionsRevoked($targetId);
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'admin.user.disabled')->where('aggregate_id', $targetId)->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'auth.session.revoked')->where('aggregate_id', $targetId)->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'auth.token.revoked')->where('aggregate_id', $targetId)->count());
    }

    public function test_audit_global_read_is_exclusive_to_audit_viewer_and_access_is_itself_audited(): void
    {
        $this->postJson('/api/v1/admin/users', [
            'email' => 'audit-target@example.test',
            'password' => 'CredentialForAudit!89',
            'is_active' => true,
        ], ['Idempotency-Key' => 'audit-target-create-001'])->assertCreated();

        $this->getJson('/api/v1/audit/events')
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');

        $this->asWeb($this->auditViewer);

        $response = $this->getJson('/api/v1/audit/events?event_type=admin.user.created')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_type', 'admin.user.created');

        self::assertStringNotContainsString('CredentialForAudit!89', $response->getContent());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'authz.audit.access')->where('actor_id', (string) $this->auditViewer->getKey())->count());

        $this->getJson('/api/v1/admin/users')
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');
    }

    public function test_admin_mutation_and_audit_roll_back_together(): void
    {
        $this->app->instance(SecurityAuditSink::class, new class implements SecurityAuditSink
        {
            public function record(SecurityAuditEvent $event): void
            {
                throw new RuntimeException('synthetic security audit failure');
            }
        });

        $this->postJson('/api/v1/admin/users', [
            'email' => 'rollback-admin@example.test',
            'password' => 'RollbackCredential!90',
            'is_active' => true,
        ], ['Idempotency-Key' => 'rollback-admin-create-001'])
            ->assertStatus(500)
            ->assertJsonPath('type', '/problems/transient_infrastructure');

        $this->assertDatabaseMissing('users', ['email' => 'rollback-admin@example.test']);
        $this->assertDatabaseMissing('idempotency_records', ['idempotency_key' => 'rollback-admin-create-001']);
    }

    private function asWeb(UserRecord $user): void
    {
        $this->actingAs($user, 'web');
        $this->withSession([
            EnforceWebAbsoluteSessionLifetime::AUTHENTICATED_AT => time(),
        ]);
    }

    private function seedSessionsFor(string $userId, string $prefix): void
    {
        DB::table('sessions')->insert([
            'id' => $prefix.'-web-session',
            'user_id' => $userId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $mobileSessionId = (string) Str::ulid();
        DB::table('mobile_sessions')->insert([
            'id' => $mobileSessionId,
            'user_id' => $userId,
            'refresh_token_hash' => hash('sha256', $prefix.'-refresh'),
            'refresh_expires_at' => now()->addHour(),
            'revoked_at' => null,
            'last_rotated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mobile_access_tokens')->insert([
            'id' => (string) Str::ulid(),
            'mobile_session_id' => $mobileSessionId,
            'token_hash' => hash('sha256', $prefix.'-access'),
            'expires_at' => now()->addMinutes(15),
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertSessionsRevoked(string $userId): void
    {
        self::assertSame(0, DB::table('sessions')->where('user_id', $userId)->count());
        self::assertSame(0, DB::table('mobile_sessions')->where('user_id', $userId)->whereNull('revoked_at')->count());

        $mobileIds = DB::table('mobile_sessions')->where('user_id', $userId)->pluck('id');
        self::assertSame(0, DB::table('mobile_access_tokens')->whereIn('mobile_session_id', $mobileIds)->whereNull('revoked_at')->count());
    }
}
