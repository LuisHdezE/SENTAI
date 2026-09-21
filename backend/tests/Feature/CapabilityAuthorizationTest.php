<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class CapabilityAuthorizationTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();

        Route::get('/api/v1/test/inventory-read', static fn () => response()->json(['ok' => true]))
            ->middleware(['auth:web', 'capability:'.Capabilities::INVENTORY_READ])
            ->name('test.inventory.read');

        Route::get('/api/v1/test/audit-read', static fn () => response()->json(['ok' => true]))
            ->middleware(['auth:web', 'capability:'.Capabilities::AUDIT_GLOBAL_READ])
            ->name('test.audit.read');
    }

    public function test_capabilities_are_enforced_without_implicit_admin_bypass(): void
    {
        $supervisor = $this->createUserWithRole('supervisor@example.test', RoleCodes::WAREHOUSE_SUPERVISOR);
        $administrator = $this->createUserWithRole('admin@example.test', RoleCodes::ADMINISTRATOR);
        $auditViewer = $this->createUserWithRole('audit@example.test', RoleCodes::AUDIT_VIEWER);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/v1/test/inventory-read')
            ->assertOk();

        $this->actingAs($administrator, 'web')
            ->getJson('/api/v1/test/inventory-read')
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'authz.denial.significant',
            'actor_id' => (string) $administrator->getKey(),
            'operation' => 'test.inventory.read',
        ]);

        $this->actingAs($administrator, 'web')
            ->getJson('/api/v1/test/audit-read')
            ->assertStatus(403);

        $this->actingAs($auditViewer, 'web')
            ->getJson('/api/v1/test/audit-read')
            ->assertOk();

        self::assertGreaterThanOrEqual(
            2,
            DB::table('audit_events')
                ->where('event_type', 'authz.denial.significant')
                ->where('actor_id', $administrator->getKey())
                ->count(),
        );
    }
}
