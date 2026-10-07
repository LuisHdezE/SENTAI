<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceWebAbsoluteSessionLifetime;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Sentai\Modules\Identity\Infrastructure\Persistence\Eloquent\UserRecord;
use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Tests\Support\CreatesIdentityFixtures;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

/**
 * API-INV-003 listInventory, API-INV-004 adjustInventory, API-INV-005 moveInventory,
 * API-INV-006 blockLocation and API-INV-007 unblockLocation (ACT-003 / Web).
 */
final class InventoryApiTest extends TestCase
{
    use CreatesIdentityFixtures;
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    private UserRecord $supervisor;

    private string $warehouseId;

    private string $receptionLocationId;

    private string $storageLocationId;

    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();
        $this->supervisor = $this->createUserWithRole('warehouse-supervisor@example.test', RoleCodes::WAREHOUSE_SUPERVISOR);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->actingAs($this->supervisor, 'web');
        $this->withSession([
            EnforceWebAbsoluteSessionLifetime::AUTHENTICATED_AT => time(),
        ]);

        $warehouse = $this->createLocation('WH-01', 'REC-01', 'ZONE-REC');
        $this->warehouseId = $warehouse['warehouse_id'];
        $this->receptionLocationId = $warehouse['location_id'];
        $this->designateReceptionLocation($warehouse['warehouse_id'], $warehouse['location_id']);

        $this->storageLocationId = $this->createLocation('WH-01', 'A-01-01', 'ZONE-A')['location_id'];
        $this->productId = $this->createProduct('SKU-001', 'Primary Product');
    }

    public function test_all_five_inventory_contract_routes_are_registered(): void
    {
        $expected = [
            'api.v1.listInventory' => 'GET',
            'api.v1.adjustInventory' => 'POST',
            'api.v1.moveInventory' => 'POST',
            'api.v1.blockLocation' => 'POST',
            'api.v1.unblockLocation' => 'POST',
        ];

        foreach ($expected as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, $name);
            self::assertContains($method, $route->methods(), $name);
        }

        self::assertSame('api/v1/locations/{id}/block', Route::getRoutes()->getByName('api.v1.blockLocation')->uri());
        self::assertSame('api/v1/locations/{id}/unblock', Route::getRoutes()->getByName('api.v1.unblockLocation')->uri());
    }

    public function test_list_inventory_exposes_on_hand_reserved_and_eligible_availability(): void
    {
        $availableId = $this->createInventoryItem($this->productId, $this->storageLocationId, '10.0000', '4.0000');
        $this->createInventoryItem($this->productId, $this->storageLocationId, '3.0000', '0.0000', InventoryState::Quarantine, 'LOT-Q');
        $receptionItemId = $this->createInventoryItem(
            $this->productId,
            $this->receptionLocationId,
            '6.0000',
            '0.0000',
            InventoryState::ReceivedPendingPutAway,
            'LOT-R',
        );

        $response = $this->getJson('/api/v1/inventory')->assertOk()->assertJsonPath('meta.total', 3);

        $rows = collect($response->json('data'))->keyBy('id');

        self::assertSame('10.0000', $rows[$availableId]['on_hand_qty']);
        self::assertSame('4.0000', $rows[$availableId]['reserved_qty']);
        self::assertSame('6.0000', $rows[$availableId]['available_qty']);
        self::assertTrue($rows[$availableId]['commercially_eligible']);

        // Quarantine stock remains OnHand but is not commercially available (BR-003).
        $quarantine = collect($response->json('data'))->firstWhere('state', InventoryState::Quarantine->value);
        self::assertSame('3.0000', $quarantine['on_hand_qty']);
        self::assertSame('0.0000', $quarantine['available_qty']);
        self::assertFalse($quarantine['commercially_eligible']);

        // Stock received but not yet put away counts as OnHand and is not eligible.
        self::assertSame('6.0000', $rows[$receptionItemId]['on_hand_qty']);
        self::assertSame('0.0000', $rows[$receptionItemId]['available_qty']);
        self::assertFalse($rows[$receptionItemId]['commercially_eligible']);

        $this->getJson('/api/v1/inventory?state='.InventoryState::Quarantine->value)
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/v1/inventory?location_id='.$this->receptionLocationId)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $receptionItemId);

        $this->getJson('/api/v1/inventory?lot_ref=LOT-R')->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_list_inventory_reports_blocked_locations_as_ineligible_while_keeping_on_hand(): void
    {
        $itemId = $this->createInventoryItem($this->productId, $this->storageLocationId, '7.0000');

        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/block', ['reason' => 'cycle-count'], ['Idempotency-Key' => 'block-001'])
            ->assertOk()
            ->assertJsonPath('data.blocked', true);

        $this->getJson('/api/v1/inventory?location_id='.$this->storageLocationId)
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId)
            ->assertJsonPath('data.0.on_hand_qty', '7.0000')
            ->assertJsonPath('data.0.available_qty', '0.0000')
            ->assertJsonPath('data.0.location_blocked', true)
            ->assertJsonPath('data.0.commercially_eligible', false);
    }

    public function test_adjust_inventory_increases_decreases_and_changes_state_with_audit_evidence(): void
    {
        $itemId = $this->createInventoryItem($this->productId, $this->storageLocationId, '10.0000', '2.0000');

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'increase',
            'quantity' => '5',
            'reason' => 'found-stock',
        ], ['Idempotency-Key' => 'adjust-001'])
            ->assertOk()
            ->assertJsonPath('data.on_hand_after', '15.0000');

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'decrease',
            'quantity' => '3',
            'reason' => 'damaged',
        ], ['Idempotency-Key' => 'adjust-002'])
            ->assertOk()
            ->assertJsonPath('data.on_hand_after', '12.0000');

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'state_change',
            'quantity' => '0',
            'target_state' => InventoryState::Blocked->value,
            'reason' => 'quality-hold',
        ], ['Idempotency-Key' => 'adjust-003'])
            ->assertOk()
            ->assertJsonPath('data.state_after', InventoryState::Blocked->value);

        $row = $this->inventoryRow($itemId);
        self::assertSame('12.0000', (string) $row['on_hand_qty']);
        self::assertSame(InventoryState::Blocked->value, (string) $row['state']);
        self::assertSame(3, DB::table('inventory_adjustments')->count());
        self::assertSame(3, DB::table('audit_events')->where('event_type', 'inventory.adjustment.applied')->count());

        self::assertSame('increase', (string) DB::table('inventory_adjustments')->orderBy('id')->value('kind'));
    }

    public function test_adjust_inventory_enforces_invariants_and_idempotency(): void
    {
        $itemId = $this->createInventoryItem($this->productId, $this->storageLocationId, '5.0000', '4.0000');

        // Decreasing below the reserved quantity would violate Reserved <= OnHand (BR-001).
        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'decrease',
            'quantity' => '2',
            'reason' => 'shrinkage',
        ], ['Idempotency-Key' => 'adjust-reserved-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');

        // Decreasing below zero is rejected.
        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'decrease',
            'quantity' => '99',
            'reason' => 'shrinkage',
        ], ['Idempotency-Key' => 'adjust-negative-001'])
            ->assertStatus(409);

        $payload = [
            'inventory_item_id' => $itemId,
            'operation_type' => 'decrease',
            'quantity' => '1',
            'reason' => 'shrinkage',
        ];

        $this->postJson('/api/v1/inventory/adjust', $payload, ['Idempotency-Key' => 'adjust-replay-001'])
            ->assertOk()
            ->assertJsonPath('data.on_hand_after', '4.0000');

        $this->postJson('/api/v1/inventory/adjust', $payload, ['Idempotency-Key' => 'adjust-replay-001'])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        self::assertSame(1, DB::table('inventory_adjustments')->count());

        // Stock pending put-away cannot change state before confirmPutAway.
        $receptionItemId = $this->createInventoryItem(
            $this->productId,
            $this->receptionLocationId,
            '1.0000',
            '0.0000',
            InventoryState::ReceivedPendingPutAway,
        );

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $receptionItemId,
            'operation_type' => 'state_change',
            'quantity' => '0',
            'target_state' => InventoryState::Available->value,
            'reason' => 'shortcut',
        ], ['Idempotency-Key' => 'adjust-reception-001'])
            ->assertStatus(409);

        self::assertSame('4.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'increase',
            'quantity' => '1',
            'reason' => 'missing-key',
        ])->assertStatus(422)->assertJsonPath('type', '/problems/validation');
    }

    public function test_move_inventory_relocates_and_merges_without_duplicating_physical_quantity(): void
    {
        $sourceId = $this->createInventoryItem($this->productId, $this->storageLocationId, '10.0000');
        $targetId = $this->createInventoryItem(
            $this->productId,
            $this->createLocation('WH-01', 'A-01-02', 'ZONE-A')['location_id'],
            '2.0000',
        );
        $targetLocationId = (string) DB::table('inventory_items')->where('id', $targetId)->value('location_id');

        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $sourceId,
            'destination_location_id' => $targetLocationId,
            'quantity' => '6',
            'reason' => 'consolidation',
        ], ['Idempotency-Key' => 'move-001'])
            ->assertOk()
            ->assertJsonPath('data.quantity', '6.0000')
            ->assertJsonPath('data.source_on_hand_after', '4.0000');

        self::assertSame('4.0000', (string) DB::table('inventory_items')->where('id', $sourceId)->value('on_hand_qty'));
        self::assertSame('8.0000', (string) DB::table('inventory_items')->where('id', $targetId)->value('on_hand_qty'));
        self::assertSame(2, DB::table('inventory_items')->count());
        self::assertSame('12.0000', (string) $this->totalOnHand());
        self::assertSame(1, DB::table('inventory_adjustments')->where('kind', 'move')->count());
        self::assertSame(2, DB::table('inventory_adjustment_lines')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.move.completed')->count());

        // Moving the whole remaining quantity exhausts the source aggregate at zero.
        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $sourceId,
            'destination_location_id' => $targetLocationId,
            'quantity' => '4',
            'reason' => 'consolidation',
        ], ['Idempotency-Key' => 'move-002'])
            ->assertOk();

        self::assertSame('0.0000', (string) DB::table('inventory_items')->where('id', $sourceId)->value('on_hand_qty'));
        self::assertSame('12.0000', (string) DB::table('inventory_items')->where('id', $targetId)->value('on_hand_qty'));
        self::assertSame('12.0000', (string) $this->totalOnHand());
        self::assertSame(2, DB::table('inventory_adjustments')->where('kind', 'move')->count());
        self::assertSame(4, DB::table('inventory_adjustment_lines')->count());
    }

    public function test_move_inventory_rejects_blocked_destinations_reception_stock_and_excess_quantity(): void
    {
        $sourceId = $this->createInventoryItem($this->productId, $this->storageLocationId, '10.0000');
        $blocked = $this->createLocation('WH-01', 'A-01-03', 'ZONE-A');
        $blockedLocationId = $blocked['location_id'];

        $this->postJson('/api/v1/locations/'.$blockedLocationId.'/block', ['reason' => 'maintenance'], ['Idempotency-Key' => 'block-move-001'])
            ->assertOk();

        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $sourceId,
            'destination_location_id' => $blockedLocationId,
            'quantity' => '1',
            'reason' => 'overflow',
        ], ['Idempotency-Key' => 'move-blocked-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');

        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $sourceId,
            'destination_location_id' => $blockedLocationId,
            'quantity' => '99',
            'reason' => 'overflow',
        ], ['Idempotency-Key' => 'move-excess-001'])
            ->assertStatus(409);

        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $sourceId,
            'destination_location_id' => $this->storageLocationId,
            'quantity' => '1',
            'reason' => 'same-place',
        ], ['Idempotency-Key' => 'move-same-001'])
            ->assertStatus(409);

        $receptionItemId = $this->createInventoryItem(
            $this->productId,
            $this->receptionLocationId,
            '1.0000',
            '0.0000',
            InventoryState::ReceivedPendingPutAway,
        );

        $this->postJson('/api/v1/inventory/move', [
            'inventory_item_id' => $receptionItemId,
            'destination_location_id' => $blockedLocationId,
            'quantity' => '1',
            'reason' => 'shortcut',
        ], ['Idempotency-Key' => 'move-reception-001'])
            ->assertStatus(409);

        self::assertSame('10.0000', (string) DB::table('inventory_items')->where('id', $sourceId)->value('on_hand_qty'));
        self::assertSame(0, DB::table('inventory_adjustments')->where('kind', 'move')->count());
    }

    public function test_block_and_unblock_location_are_idempotent_audited_and_reject_invalid_targets(): void
    {
        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/block', ['reason' => 'cycle-count'], ['Idempotency-Key' => 'block-audit-001'])
            ->assertOk()
            ->assertJsonPath('data.blocked', true);

        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/block', ['reason' => 'cycle-count'], ['Idempotency-Key' => 'block-audit-001'])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/block', ['reason' => 'again'], ['Idempotency-Key' => 'block-audit-002'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');

        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/unblock', ['reason' => 'recount-ok'], ['Idempotency-Key' => 'unblock-audit-001'])
            ->assertOk()
            ->assertJsonPath('data.blocked', false);

        $this->postJson('/api/v1/locations/'.$this->storageLocationId.'/unblock', ['reason' => 'recount-ok'], ['Idempotency-Key' => 'unblock-audit-002'])
            ->assertStatus(409);

        $this->postJson('/api/v1/locations/01AAAAAAAAAAAAAAAAAAAAAAAA/block', ['reason' => 'missing'], ['Idempotency-Key' => 'block-missing-001'])
            ->assertStatus(404)
            ->assertJsonPath('type', '/problems/resource_not_found');

        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.location.blocked')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.location.unblocked')->count());
        self::assertSame(0, DB::table('inventory_location_blocks')->count());
        self::assertSame('1', (string) DB::table('locations')->where('id', $this->storageLocationId)->value('is_active'));
    }

    public function test_inventory_routes_require_their_exact_capability(): void
    {
        $administrator = $this->createUserWithRole('admin-inventory-test@example.test', RoleCodes::ADMINISTRATOR);
        $itemId = $this->createInventoryItem($this->productId, $this->storageLocationId, '1.0000');

        $this->actingAs($administrator, 'web')
            ->getJson('/api/v1/inventory')
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');

        $this->actingAs($administrator, 'web')
            ->postJson('/api/v1/inventory/adjust', [
                'inventory_item_id' => $itemId,
                'operation_type' => 'increase',
                'quantity' => '1',
                'reason' => 'not-allowed',
            ], ['Idempotency-Key' => 'adjust-forbidden-001'])
            ->assertStatus(403);

        $this->actingAs($administrator, 'web')
            ->postJson('/api/v1/locations/'.$this->storageLocationId.'/block', ['reason' => 'not-allowed'], ['Idempotency-Key' => 'block-forbidden-001'])
            ->assertStatus(403);

        self::assertSame(0, DB::table('inventory_adjustments')->count());
        self::assertSame(0, DB::table('inventory_location_blocks')->count());
    }

    public function test_inventory_mutation_and_audit_are_rolled_back_together(): void
    {
        $this->app->instance(InventoryAuditSink::class, new class implements InventoryAuditSink
        {
            public function record(InventoryAuditEvent $event): void
            {
                throw new RuntimeException('synthetic audit failure');
            }
        });

        $itemId = $this->createInventoryItem($this->productId, $this->storageLocationId, '1.0000');

        $this->postJson('/api/v1/inventory/adjust', [
            'inventory_item_id' => $itemId,
            'operation_type' => 'increase',
            'quantity' => '5',
            'reason' => 'rollback',
        ], ['Idempotency-Key' => 'adjust-rollback-001'])
            ->assertStatus(500)
            ->assertJsonPath('type', '/problems/transient_infrastructure');

        self::assertSame('1.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));
        self::assertSame(0, DB::table('inventory_adjustments')->count());
        self::assertFalse(DB::table('idempotency_records')->where('idempotency_key', 'adjust-rollback-001')->exists());
    }

    private function totalOnHand(): string
    {
        return (string) DB::table('inventory_items')->sum('on_hand_qty');
    }
}
