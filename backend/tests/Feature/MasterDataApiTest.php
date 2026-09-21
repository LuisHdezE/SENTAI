<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataAuditSink;
use Sentai\Modules\MasterData\Application\DTO\MasterDataAuditEvent;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class MasterDataApiTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    private UserRecord $administrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();
        $this->administrator = $this->createUserWithRole('master-admin@example.test', RoleCodes::ADMINISTRATOR);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->actingAs($this->administrator, 'web');
    }

    public function test_all_fifteen_master_data_contract_routes_are_registered(): void
    {
        $routes = [
            'api.v1.listProducts' => 'GET',
            'api.v1.createProduct' => 'POST',
            'api.v1.updateProduct' => 'PUT',
            'api.v1.listCustomers' => 'GET',
            'api.v1.createCustomer' => 'POST',
            'api.v1.updateCustomer' => 'PUT',
            'api.v1.listWarehouses' => 'GET',
            'api.v1.createWarehouse' => 'POST',
            'api.v1.updateWarehouse' => 'PUT',
            'api.v1.listZones' => 'GET',
            'api.v1.createZone' => 'POST',
            'api.v1.updateZone' => 'PUT',
            'api.v1.listLocations' => 'GET',
            'api.v1.createLocation' => 'POST',
            'api.v1.updateLocation' => 'PUT',
        ];

        foreach ($routes as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, $name);
            self::assertContains($method, $route->methods(), $name);
        }
    }

    public function test_product_and_customer_contracts_are_idempotent_audited_and_filterable(): void
    {
        $productPayload = ['code' => 'SKU-001', 'name' => 'Primary Product', 'is_active' => true];
        $createdProduct = $this->postJson('/api/v1/products', $productPayload, ['Idempotency-Key' => 'product-create-001'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed');
        $productId = (string) $createdProduct->json('data.id');

        $this->postJson('/api/v1/products', $productPayload, ['Idempotency-Key' => 'product-create-001'])
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $productId);

        $this->postJson('/api/v1/products', ['code' => 'SKU-001', 'name' => 'Changed Replay', 'is_active' => true], ['Idempotency-Key' => 'product-create-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/idempotency_conflict');

        $this->postJson('/api/v1/products', ['code' => 'SKU-001', 'name' => 'Duplicate Product', 'is_active' => true], ['Idempotency-Key' => 'product-create-002'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');

        $this->putJson('/api/v1/products/'.$productId, ['code' => 'SKU-001', 'name' => 'Primary Product Updated', 'is_active' => false], ['Idempotency-Key' => 'product-update-001'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $customer = $this->postJson('/api/v1/customers', ['code' => 'CUST-001', 'name' => 'Customer One', 'is_active' => true], ['Idempotency-Key' => 'customer-create-001'])
            ->assertCreated();
        $customerId = (string) $customer->json('data.id');

        $this->putJson('/api/v1/customers/'.$customerId, ['code' => 'CUST-001', 'name' => 'Customer One Updated', 'is_active' => true], ['Idempotency-Key' => 'customer-update-001'])
            ->assertOk();

        $this->getJson('/api/v1/products?q=Primary&active=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $productId)
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/customers?q=Customer')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        self::assertSame(4, DB::table('audit_events')->where('event_type', 'admin.masters.changed')->count());
        self::assertSame(4, DB::table('idempotency_records')->whereNotNull('completed_at')->count());
    }

    public function test_warehouse_zone_location_hierarchy_supports_create_list_and_update(): void
    {
        $warehouse = $this->postJson('/api/v1/warehouses', ['code' => 'WH-01', 'name' => 'Main Warehouse', 'is_active' => true], ['Idempotency-Key' => 'warehouse-create-001'])
            ->assertCreated();
        $warehouseId = (string) $warehouse->json('data.id');

        $zone = $this->postJson('/api/v1/zones', ['warehouse_id' => $warehouseId, 'code' => 'ZONE-A', 'name' => 'Zone A', 'is_active' => true], ['Idempotency-Key' => 'zone-create-001'])
            ->assertCreated();
        $zoneId = (string) $zone->json('data.id');

        $location = $this->postJson('/api/v1/locations', ['warehouse_id' => $warehouseId, 'zone_id' => $zoneId, 'code' => 'A-01-01', 'name' => 'Aisle 1 Bay 1', 'is_active' => true], ['Idempotency-Key' => 'location-create-001'])
            ->assertCreated();
        $locationId = (string) $location->json('data.id');

        $this->getJson('/api/v1/warehouses')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/zones?warehouse_id='.$warehouseId)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/locations?warehouse_id='.$warehouseId.'&zone_id='.$zoneId)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $locationId);

        $this->putJson('/api/v1/warehouses/'.$warehouseId, ['code' => 'WH-01', 'name' => 'Main Warehouse Updated', 'is_active' => true], ['Idempotency-Key' => 'warehouse-update-001'])->assertOk();
        $this->putJson('/api/v1/zones/'.$zoneId, ['warehouse_id' => $warehouseId, 'code' => 'ZONE-A', 'name' => 'Zone A Updated', 'is_active' => true], ['Idempotency-Key' => 'zone-update-001'])->assertOk();
        $this->putJson('/api/v1/locations/'.$locationId, ['warehouse_id' => $warehouseId, 'zone_id' => $zoneId, 'code' => 'A-01-01', 'name' => 'Aisle 1 Bay 1 Updated', 'is_active' => false], ['Idempotency-Key' => 'location-update-001'])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $otherWarehouse = $this->postJson('/api/v1/warehouses', ['code' => 'WH-02', 'name' => 'Other Warehouse', 'is_active' => true], ['Idempotency-Key' => 'warehouse-create-002'])
            ->assertCreated();

        $this->postJson('/api/v1/locations', [
            'warehouse_id' => (string) $otherWarehouse->json('data.id'),
            'zone_id' => $zoneId,
            'code' => 'BAD-01',
            'name' => 'Invalid Cross Warehouse Location',
            'is_active' => true,
        ], ['Idempotency-Key' => 'location-create-bad-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');

        $this->putJson('/api/v1/locations/01AAAAAAAAAAAAAAAAAAAAAAAA', [
            'warehouse_id' => $warehouseId,
            'zone_id' => $zoneId,
            'code' => 'MISSING',
            'name' => 'Missing',
            'is_active' => true,
        ], ['Idempotency-Key' => 'location-update-missing-001'])
            ->assertStatus(404)
            ->assertJsonPath('type', '/problems/resource_not_found');

        self::assertSame(7, DB::table('audit_events')->where('event_type', 'admin.masters.changed')->count());
    }

    public function test_master_routes_require_the_exact_admin_master_capability_and_mutations_require_idempotency_key(): void
    {
        $supervisor = $this->createUserWithRole('supervisor-master-test@example.test', RoleCodes::WAREHOUSE_SUPERVISOR);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/v1/products')
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');

        $this->actingAs($this->administrator, 'web')
            ->postJson('/api/v1/products', ['code' => 'NO-IDEM', 'name' => 'Missing Idempotency', 'is_active' => true])
            ->assertStatus(422)
            ->assertJsonPath('type', '/problems/validation');

        $this->assertDatabaseMissing('products', ['code' => 'NO-IDEM']);
    }

    public function test_master_mutation_and_audit_are_rolled_back_together(): void
    {
        $this->app->instance(MasterDataAuditSink::class, new class implements MasterDataAuditSink
        {
            public function record(MasterDataAuditEvent $event): void
            {
                throw new RuntimeException('synthetic audit failure');
            }
        });

        $this->postJson('/api/v1/products', ['code' => 'ROLLBACK-01', 'name' => 'Rollback Product', 'is_active' => true], ['Idempotency-Key' => 'rollback-product-001'])
            ->assertStatus(500)
            ->assertJsonPath('type', '/problems/transient_infrastructure');

        $this->assertDatabaseMissing('products', ['code' => 'ROLLBACK-01']);
        $this->assertDatabaseMissing('idempotency_records', ['idempotency_key' => 'rollback-product-001']);
    }
}
