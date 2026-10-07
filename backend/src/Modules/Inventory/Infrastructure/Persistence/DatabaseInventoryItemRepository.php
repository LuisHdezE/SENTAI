<?php

namespace Sentai\Modules\Inventory\Infrastructure\Persistence;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Inventory\Application\Contracts\InventoryItemRepository;
use Sentai\Modules\Inventory\Domain\Entities\InventoryItem;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use stdClass;

final class DatabaseInventoryItemRepository implements InventoryItemRepository
{
    public function list(array $filters): array
    {
        $query = DB::table('inventory_items')
            ->leftJoin('inventory_location_blocks', 'inventory_location_blocks.location_id', '=', 'inventory_items.location_id')
            ->select('inventory_items.*')
            ->selectRaw('(inventory_location_blocks.location_id IS NOT NULL) AS location_blocked');

        if (($filters['product_id'] ?? null) !== null) {
            $query->where('inventory_items.product_id', (string) $filters['product_id']);
        }

        if (($filters['location_id'] ?? null) !== null) {
            $query->where('inventory_items.location_id', (string) $filters['location_id']);
        }

        if (($filters['lot_ref'] ?? null) !== null) {
            $query->where('inventory_items.lot_ref', (string) $filters['lot_ref']);
        }

        if (($filters['serial_ref'] ?? null) !== null) {
            $query->where('inventory_items.serial_ref', (string) $filters['serial_ref']);
        }

        if (($filters['state'] ?? null) !== null) {
            $query->where('inventory_items.state', (string) $filters['state']);
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = (clone $query)->count();
        $rows = $query
            ->orderBy('inventory_items.product_id')
            ->orderBy('inventory_items.location_id')
            ->orderBy('inventory_items.id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'items' => $rows->map(fn (stdClass $row): InventoryItem => $this->hydrate($row))->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function find(string $id): ?InventoryItem
    {
        return $this->hydrateOne($this->baseQuery()->where('inventory_items.id', $id)->first());
    }

    public function lock(string $id): ?InventoryItem
    {
        return $this->hydrateOne($this->baseQuery()->where('inventory_items.id', $id)->lockForUpdate()->first());
    }

    public function findIdentity(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
    ): ?InventoryItem {
        return $this->hydrateOne($this->identityQuery($productId, $locationId, $lotRef, $serialRef, $state)->first());
    }

    public function lockIdentity(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
        ?string $excludeId = null,
    ): ?InventoryItem {
        $query = $this->identityQuery($productId, $locationId, $lotRef, $serialRef, $state);

        if ($excludeId !== null) {
            $query->where('inventory_items.id', '!=', $excludeId);
        }

        return $this->hydrateOne($query->orderBy('inventory_items.id')->lockForUpdate()->first());
    }

    public function store(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
        string $onHandQty,
        string $reservedQty,
    ): InventoryItem {
        $id = (string) Str::ulid();
        $now = now();

        DB::table('inventory_items')->insert([
            'id' => $id,
            'product_id' => $productId,
            'location_id' => $locationId,
            'lot_ref' => $lotRef,
            'serial_ref' => $serialRef,
            'state' => $state->value,
            'on_hand_qty' => $this->decimal($onHandQty),
            'reserved_qty' => $this->decimal($reservedQty),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $item = $this->find($id);

        if ($item === null) {
            throw new \RuntimeException('The stored inventory item could not be read back.');
        }

        return $item;
    }

    public function incrementOnHand(string $id, string $delta): void
    {
        DB::update(
            'UPDATE inventory_items SET on_hand_qty = on_hand_qty + ?, updated_at = ? WHERE id = ?',
            [$this->decimal($delta), now(), $id],
        );
    }

    public function reduceOnHand(string $id, string $delta, string $reservedQty, InventoryState $state): string
    {
        DB::update(
            'UPDATE inventory_items SET on_hand_qty = on_hand_qty - ?, reserved_qty = ?, state = ?, updated_at = ? WHERE id = ?',
            [$this->decimal($delta), $this->decimal($reservedQty), $state->value, now(), $id],
        );

        $remaining = DB::table('inventory_items')->where('id', $id)->value('on_hand_qty');

        if ($remaining === null) {
            throw new \RuntimeException("Inventory item {$id} does not exist.");
        }

        // Exhausted aggregates are retained at zero rather than deleted: relocation and
        // adjustment evidence references them and must stay traceable (FR-019).
        return $this->decimal((string) $remaining);
    }

    public function replaceQuantitiesAndState(string $id, string $onHandQty, string $reservedQty, InventoryState $state): void
    {
        DB::table('inventory_items')
            ->where('id', $id)
            ->update([
                'on_hand_qty' => $this->decimal($onHandQty),
                'reserved_qty' => $this->decimal($reservedQty),
                'state' => $state->value,
                'updated_at' => now(),
            ]);
    }

    public function storeAdjustment(
        string $inventoryItemId,
        string $operation,
        ?string $quantityDelta,
        ?string $onHandBefore,
        ?string $onHandAfter,
        ?string $stateBefore,
        ?string $stateAfter,
        string $reason,
        string $actorId,
        string $correlationId,
    ): void {
        $now = now();
        $adjustmentId = (string) Str::ulid();

        DB::table('inventory_adjustments')->insert([
            'id' => $adjustmentId,
            'kind' => $operation,
            'reason' => $reason,
            'actor_id' => $actorId,
            'correlation_id' => $correlationId,
            'occurred_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory_adjustment_lines')->insert([
            'id' => (string) Str::ulid(),
            'adjustment_id' => $adjustmentId,
            'inventory_item_id' => $inventoryItemId,
            'source_location_id' => null,
            'destination_location_id' => null,
            'quantity_delta' => $this->decimal($quantityDelta ?? '0'),
            'on_hand_before' => $onHandBefore === null ? null : $this->decimal($onHandBefore),
            'on_hand_after' => $onHandAfter === null ? null : $this->decimal($onHandAfter),
            'state_before' => $stateBefore,
            'state_after' => $stateAfter,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function storeMovement(
        string $sourceInventoryItemId,
        string $destinationInventoryItemId,
        string $sourceLocationId,
        string $destinationLocationId,
        string $quantity,
        string $sourceOnHandBefore,
        string $sourceOnHandAfter,
        string $destinationOnHandBefore,
        string $destinationOnHandAfter,
        string $sourceState,
        string $destinationState,
        string $reason,
        string $actorId,
        string $correlationId,
    ): void {
        $now = now();
        $adjustmentId = (string) Str::ulid();
        $quantity = $this->decimal($quantity);

        DB::table('inventory_adjustments')->insert([
            'id' => $adjustmentId,
            'kind' => 'move',
            'reason' => $reason,
            'actor_id' => $actorId,
            'correlation_id' => $correlationId,
            'occurred_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory_adjustment_lines')->insert([
            [
                'id' => (string) Str::ulid(),
                'adjustment_id' => $adjustmentId,
                'inventory_item_id' => $sourceInventoryItemId,
                'source_location_id' => $sourceLocationId,
                'destination_location_id' => $destinationLocationId,
                'quantity_delta' => $this->decimal('-'.$quantity),
                'on_hand_before' => $this->decimal($sourceOnHandBefore),
                'on_hand_after' => $this->decimal($sourceOnHandAfter),
                'state_before' => $sourceState,
                'state_after' => $sourceState,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => (string) Str::ulid(),
                'adjustment_id' => $adjustmentId,
                'inventory_item_id' => $destinationInventoryItemId,
                'source_location_id' => $sourceLocationId,
                'destination_location_id' => $destinationLocationId,
                'quantity_delta' => $quantity,
                'on_hand_before' => $this->decimal($destinationOnHandBefore),
                'on_hand_after' => $this->decimal($destinationOnHandAfter),
                'state_before' => $destinationState,
                'state_after' => $destinationState,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function baseQuery(): Builder
    {
        return DB::table('inventory_items')
            ->leftJoin('inventory_location_blocks', 'inventory_location_blocks.location_id', '=', 'inventory_items.location_id')
            ->select('inventory_items.*')
            ->selectRaw('(inventory_location_blocks.location_id IS NOT NULL) AS location_blocked');
    }

    private function identityQuery(
        string $productId,
        string $locationId,
        ?string $lotRef,
        ?string $serialRef,
        InventoryState $state,
    ): Builder {
        $query = $this->baseQuery()
            ->where('inventory_items.product_id', $productId)
            ->where('inventory_items.location_id', $locationId)
            ->where('inventory_items.state', $state->value);

        $lotRef === null
            ? $query->whereNull('inventory_items.lot_ref')
            : $query->where('inventory_items.lot_ref', $lotRef);

        $serialRef === null
            ? $query->whereNull('inventory_items.serial_ref')
            : $query->where('inventory_items.serial_ref', $serialRef);

        return $query;
    }

    private function hydrateOne(?stdClass $row): ?InventoryItem
    {
        return $row === null ? null : $this->hydrate($row);
    }

    private function hydrate(stdClass $row): InventoryItem
    {
        return new InventoryItem(
            (string) $row->id,
            (string) $row->product_id,
            (string) $row->location_id,
            $row->lot_ref === null ? null : (string) $row->lot_ref,
            $row->serial_ref === null ? null : (string) $row->serial_ref,
            InventoryState::fromStateValue((string) $row->state),
            $this->decimal((string) $row->on_hand_qty),
            $this->decimal((string) $row->reserved_qty),
            (bool) ($row->location_blocked ?? false),
        );
    }

    private function decimal(string $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
