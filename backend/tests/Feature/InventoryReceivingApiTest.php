<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Tests\Support\CreatesIdentityFixtures;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

/**
 * API-INV-001 receiveAsn and API-INV-002 confirmPutAway (ACT-002 / Mobile).
 */
final class InventoryReceivingApiTest extends TestCase
{
    use CreatesIdentityFixtures;
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    private string $accessToken;

    private string $warehouseId;

    private string $receptionLocationId;

    private string $destinationLocationId;

    private string $productId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();

        $operator = $this->createUserWithRole('warehouse-operator@example.test', RoleCodes::WAREHOUSE_OPERATOR);
        $this->assignRole($operator, RoleCodes::WAREHOUSE_SUPERVISOR);

        $this->accessToken = (string) $this->postJson('/api/v1/auth/mobile/login', [
            'email' => 'warehouse-operator@example.test',
            'password' => 'Secret123!',
        ])->assertOk()->json('access_token');

        $warehouse = $this->createLocation('WH-01', 'REC-01', 'ZONE-REC');
        $this->warehouseId = $warehouse['warehouse_id'];
        $this->receptionLocationId = $warehouse['location_id'];
        $this->designateReceptionLocation($warehouse['warehouse_id'], $warehouse['location_id']);

        $this->destinationLocationId = $this->createLocation('WH-01', 'A-01-01', 'ZONE-A')['location_id'];
        $this->productId = $this->createProduct('SKU-001', 'Primary Product');
    }

    public function test_inventory_contract_routes_for_receipt_and_put_away_are_registered(): void
    {
        $expected = [
            'api.v1.receiveAsn' => 'POST',
            'api.v1.confirmPutAway' => 'POST',
        ];

        foreach ($expected as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            self::assertNotNull($route, $name);
            self::assertContains($method, $route->methods(), $name);
        }

        self::assertSame('api/v1/reception/asn/{id}/receive', Route::getRoutes()->getByName('api.v1.receiveAsn')->uri());
        self::assertSame('api/v1/inventory/put-away', Route::getRoutes()->getByName('api.v1.confirmPutAway')->uri());
    }

    public function test_reception_mapping_rejects_a_location_from_another_warehouse(): void
    {
        $warehouseA = $this->createLocation('WH-02', 'REC-02', 'ZONE-REC');
        $warehouseB = $this->createLocation('WH-03', 'REC-03', 'ZONE-REC');

        $this->expectException(QueryException::class);

        $this->designateReceptionLocation(
            $warehouseA['warehouse_id'],
            $warehouseB['location_id'],
        );
    }

    public function test_receive_asn_commits_stock_into_the_reception_location_and_is_idempotent(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-001');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '10.0000', 'LOT-A');

        $payload = [
            'lines' => [[
                'asn_line_id' => $asnLineId,
                'product_id' => $this->productId,
                'received_qty' => '8',
            ]],
        ];

        $first = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', $payload, ['Idempotency-Key' => 'receive-asn-001'])
            ->assertCreated()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('data.asn_id', $asnId)
            ->assertJsonPath('data.reception_location_id', $this->receptionLocationId)
            ->assertJsonPath('data.lines.0.received_qty', '8.0000');

        $itemId = (string) $first->json('data.lines.0.inventory_item_id');
        $row = $this->inventoryRow($itemId);

        self::assertSame($this->receptionLocationId, (string) $row['location_id']);
        self::assertSame(InventoryState::ReceivedPendingPutAway->value, (string) $row['state']);
        self::assertSame('8.0000', (string) $row['on_hand_qty']);
        self::assertSame('0.0000', (string) $row['reserved_qty']);
        self::assertSame('LOT-A', (string) $row['lot_ref']);

        self::assertSame('8.0000', (string) DB::table('asn_lines')->where('id', $asnLineId)->value('received_qty'));
        self::assertSame('partially_received', (string) DB::table('asns')->where('id', $asnId)->value('status'));
        self::assertSame(1, DB::table('receipts')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.asn.received')->count());

        $replay = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', $payload, ['Idempotency-Key' => 'receive-asn-001'])
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');

        self::assertSame($itemId, (string) $replay->json('data.lines.0.inventory_item_id'));
        self::assertSame(1, DB::table('receipts')->count());
        self::assertSame(1, DB::table('inventory_items')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.asn.received')->count());

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '3',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-001'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/idempotency_conflict');
    }

    public function test_receive_asn_merges_into_the_existing_reception_identity_and_completes_the_asn(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-002');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '10.0000', 'LOT-B');

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '4',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-partial-001'])
            ->assertCreated();

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '6',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-partial-002'])
            ->assertCreated();

        self::assertSame(1, DB::table('inventory_items')->count());
        self::assertSame('10.0000', (string) DB::table('inventory_items')->value('on_hand_qty'));
        self::assertSame('received', (string) DB::table('asns')->where('id', $asnId)->value('status'));
        self::assertSame(2, DB::table('receipts')->count());
    }

    public function test_receive_asn_fails_closed_when_the_warehouse_has_no_reception_location(): void
    {
        $orphanWarehouse = $this->createLocation('WH-02', 'A-02-01', 'ZONE-B');
        $asnId = $this->createAsn($orphanWarehouse['warehouse_id'], 'ASN-003');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '5.0000');

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '5',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-missing-config'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict')
            ->assertJsonPath('conflict_context.reason', 'reception_location_not_configured');

        self::assertSame(0, DB::table('receipts')->count());
        self::assertSame(0, DB::table('inventory_items')->count());
        self::assertSame(0, DB::table('audit_events')->where('event_type', 'inventory.asn.received')->count());
        self::assertFalse(DB::table('idempotency_records')->where('idempotency_key', 'receive-asn-missing-config')->exists());
    }

    public function test_receive_asn_rejects_unknown_or_over_received_asn_lines_and_requires_credentials(): void
    {
        $this->postJson('/api/v1/reception/asn/01AAAAAAAAAAAAAAAAAAAAAAAA/receive', [
            'lines' => [[
                'product_id' => $this->productId,
                'received_qty' => '1',
            ]],
        ], ['Idempotency-Key' => 'receive-asn-unauthenticated'])
            ->assertStatus(401)
            ->assertJsonPath('type', '/problems/authentication');

        $asnId = $this->createAsn($this->warehouseId, 'ASN-004');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '2.0000');

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '3',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-over'])
            ->assertStatus(409)
            ->assertJsonPath('conflict_context.reason', 'quantity_discrepancy_exceeds_expected');

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'product_id' => $this->createProduct('SKU-999', 'Unexpected Product'),
                    'received_qty' => '1',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-unexpected'])
            ->assertStatus(409);

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '1',
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('type', '/problems/validation');

        self::assertSame(0, DB::table('receipts')->count());
        self::assertSame(0, DB::table('inventory_items')->count());
    }

    public function test_confirm_put_away_relocates_received_stock_to_the_destination_location(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-005');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '10.0000', 'LOT-C');

        $receipt = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '10',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-putaway-001'])
            ->assertCreated();

        $receiptId = (string) $receipt->json('data.receipt_id');
        $receiptLineId = (string) DB::table('receipt_lines')->where('receipt_id', $receiptId)->value('id');
        $itemId = (string) $receipt->json('data.lines.0.inventory_item_id');

        $payload = [
            'receipt_id' => $receiptId,
            'destination_location_id' => $this->destinationLocationId,
            'lines' => [[
                'receipt_line_id' => $receiptLineId,
                'inventory_item_id' => $itemId,
                'quantity' => '10',
            ]],
        ];

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', $payload, ['Idempotency-Key' => 'putaway-001'])
            ->assertOk()
            ->assertHeaderMissing('Idempotency-Replayed')
            ->assertJsonPath('data.destination_location_id', $this->destinationLocationId)
            ->assertJsonPath('data.status', 'put_away_completed');

        // The exhausted reception aggregate is retained at zero, and no stock remains in
        // the reception Location functionally (AC-003).
        self::assertSame('0.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));
        self::assertSame(0, DB::table('inventory_items')->where('location_id', $this->receptionLocationId)->where('on_hand_qty', '>', 0)->count());
        self::assertSame(1, DB::table('inventory_items')->where('location_id', $this->destinationLocationId)->count());

        $row = (array) DB::table('inventory_items')->where('location_id', $this->destinationLocationId)->first();
        self::assertSame(InventoryState::Available->value, (string) $row['state']);
        self::assertSame('10.0000', (string) $row['on_hand_qty']);
        self::assertSame('put_away_completed', (string) DB::table('receipts')->where('id', $receiptId)->value('status'));
        self::assertSame(1, DB::table('inventory_adjustments')->where('kind', 'move')->count());
        self::assertSame(1, DB::table('putaway_tasks')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.putaway.completed')->count());

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', $payload, ['Idempotency-Key' => 'putaway-001'])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');

        self::assertSame(1, DB::table('inventory_adjustments')->where('kind', 'move')->count());
        self::assertSame(1, DB::table('audit_events')->where('event_type', 'inventory.putaway.completed')->count());
    }

    public function test_confirm_put_away_merges_duplicate_identity_at_the_destination_and_rejects_invalid_targets(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-006');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '10.0000', 'LOT-D');

        $receipt = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '10',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-merge-001'])
            ->assertCreated();

        $receiptId = (string) $receipt->json('data.receipt_id');
        $receiptLineId = (string) DB::table('receipt_lines')->where('receipt_id', $receiptId)->value('id');
        $itemId = (string) $receipt->json('data.lines.0.inventory_item_id');

        $existingId = $this->createInventoryItem(
            $this->productId,
            $this->destinationLocationId,
            '5.0000',
            '0.0000',
            InventoryState::Available,
            'LOT-D',
        );

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => $receiptId,
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => $receiptLineId,
                    'inventory_item_id' => $itemId,
                    'quantity' => '10',
                ]],
            ], ['Idempotency-Key' => 'putaway-merge-001'])
            ->assertOk();

        self::assertSame(1, DB::table('inventory_items')->where('location_id', $this->destinationLocationId)->count());
        self::assertSame('15.0000', (string) DB::table('inventory_items')->where('id', $existingId)->value('on_hand_qty'));
        self::assertSame('0.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));

        // A second put-away against the same, already completed receipt is a domain conflict.
        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => $receiptId,
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => $receiptLineId,
                    'inventory_item_id' => $itemId,
                    'quantity' => '1',
                ]],
            ], ['Idempotency-Key' => 'putaway-merge-002'])
            ->assertStatus(409)
            ->assertJsonPath('type', '/problems/domain_conflict');
    }

    public function test_confirm_put_away_partially_moves_stock_and_keeps_the_receipt_open(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-007');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '10.0000', 'LOT-E');

        $receipt = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '10',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-partial-putaway-001'])
            ->assertCreated();

        $receiptId = (string) $receipt->json('data.receipt_id');
        $receiptLineId = (string) DB::table('receipt_lines')->where('receipt_id', $receiptId)->value('id');
        $itemId = (string) $receipt->json('data.lines.0.inventory_item_id');

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => $receiptId,
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => $receiptLineId,
                    'inventory_item_id' => $itemId,
                    'quantity' => '4',
                ]],
            ], ['Idempotency-Key' => 'putaway-partial-001'])
            ->assertOk()
            ->assertJsonPath('data.status', 'received');

        self::assertSame('6.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));
        self::assertSame('4.0000', (string) DB::table('inventory_items')->where('location_id', $this->destinationLocationId)->value('on_hand_qty'));
        self::assertSame('received', (string) DB::table('receipts')->where('id', $receiptId)->value('status'));

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => $receiptId,
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => $receiptLineId,
                    'inventory_item_id' => $itemId,
                    'quantity' => '7',
                ]],
            ], ['Idempotency-Key' => 'putaway-partial-002'])
            ->assertStatus(409);

        self::assertSame('6.0000', (string) DB::table('inventory_items')->where('id', $itemId)->value('on_hand_qty'));
    }

    public function test_put_away_into_a_blocked_destination_persists_stock_as_blocked(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-008');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '5.0000', 'LOT-F');

        $receipt = $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '5',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-blocked-001'])
            ->assertCreated();

        $receiptId = (string) $receipt->json('data.receipt_id');
        $receiptLineId = (string) DB::table('receipt_lines')->where('receipt_id', $receiptId)->value('id');
        $itemId = (string) $receipt->json('data.lines.0.inventory_item_id');

        // Operational blocking is Inventory state, persisted outside `locations`.
        DB::table('inventory_location_blocks')->insert([
            'location_id' => $this->destinationLocationId,
            'reason' => 'maintenance',
            'blocked_by' => (string) DB::table('users')->value('id'),
            'blocked_correlation_id' => $this->correlationId(),
            'blocked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => $receiptId,
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => $receiptLineId,
                    'inventory_item_id' => $itemId,
                    'quantity' => '5',
                ]],
            ], ['Idempotency-Key' => 'putaway-blocked-001'])
            ->assertOk()
            ->assertJsonPath('data.destination_blocked', true);

        self::assertSame(
            InventoryState::Blocked->value,
            (string) DB::table('inventory_items')->where('location_id', $this->destinationLocationId)->value('state'),
        );

        self::assertSame(
            '1',
            (string) DB::table('locations')->where('id', $this->destinationLocationId)->value('is_active'),
        );
    }

    public function test_mobile_inventory_operations_require_their_exact_capability(): void
    {
        $asnId = $this->createAsn($this->warehouseId, 'ASN-009');
        $asnLineId = $this->createAsnLine($asnId, $this->productId, '1.0000');

        // Mobile authentication itself is restricted to the warehouse operator identity.
        $planner = $this->createUserWithRole('planner@example.test', RoleCodes::DISPATCH_PLANNER);
        $this->postJson('/api/v1/auth/mobile/login', [
            'email' => (string) $planner->email,
            'password' => 'Secret123!',
        ])->assertStatus(401);

        // An operator without `warehouse.putaway` is denied the put-away operation.
        $operatorRoleId = (string) DB::table('roles')->where('code', RoleCodes::WAREHOUSE_OPERATOR)->value('id');
        $putAwayPermissionId = (string) DB::table('permissions')->where('code', Capabilities::WAREHOUSE_PUTAWAY)->value('id');
        DB::table('role_permissions')
            ->where('role_id', $operatorRoleId)
            ->where('permission_id', $putAwayPermissionId)
            ->delete();

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/inventory/put-away', [
                'receipt_id' => '01AAAAAAAAAAAAAAAAAAAAAAAA',
                'destination_location_id' => $this->destinationLocationId,
                'lines' => [[
                    'receipt_line_id' => '01BBBBBBBBBBBBBBBBBBBBBBBB',
                    'inventory_item_id' => '01CCCCCCCCCCCCCCCCCCCCCCCC',
                    'quantity' => '1',
                ]],
            ], ['Idempotency-Key' => 'putaway-forbidden-001'])
            ->assertStatus(403)
            ->assertJsonPath('type', '/problems/authorization');

        DB::table('role_permissions')->insert([
            'role_id' => $operatorRoleId,
            'permission_id' => $putAwayPermissionId,
        ]);

        $this->withToken($this->accessToken)
            ->postJson('/api/v1/reception/asn/'.$asnId.'/receive', [
                'lines' => [[
                    'asn_line_id' => $asnLineId,
                    'product_id' => $this->productId,
                    'received_qty' => '1',
                ]],
            ], ['Idempotency-Key' => 'receive-asn-allowed-001'])
            ->assertCreated();

        self::assertSame(0, DB::table('inventory_adjustments')->where('kind', 'move')->count());
        self::assertSame(1, DB::table('receipts')->count());
        self::assertSame(1, DB::table('inventory_items')->count());
    }
}
