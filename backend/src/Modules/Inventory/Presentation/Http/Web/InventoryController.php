<?php

namespace Sentai\Modules\Inventory\Presentation\Http\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentai\Modules\Inventory\Application\DTO\InventoryMutationContext;
use Sentai\Modules\Inventory\Application\Services\InventoryService;
use Sentai\Modules\Inventory\Domain\ValueObjects\InventoryState;
use Sentai\Shared\Presentation\Http\IdempotentMutationHttp;

/**
 * Backoffice Inventory operations: API-INV-003..007.
 *
 * Read access is `inventory.read`; every mutation requires `inventory.adjust` except
 * location blocking, which requires `inventory.location.block`.
 */
final readonly class InventoryController
{
    use IdempotentMutationHttp;

    public function __construct(private InventoryService $service) {}

    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'ulid'],
            'location_id' => ['nullable', 'ulid'],
            'lot_ref' => ['nullable', 'string', 'max:64'],
            'serial_ref' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'in:'.implode(',', InventoryState::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->service->list([
            'product_id' => $validated['product_id'] ?? null,
            'location_id' => $validated['location_id'] ?? null,
            'lot_ref' => $validated['lot_ref'] ?? null,
            'serial_ref' => $validated['serial_ref'] ?? null,
            'state' => $validated['state'] ?? null,
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ]));
    }

    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => ['required', 'ulid'],
            'operation_type' => ['required', 'in:increase,decrease,state_change'],
            'quantity' => ['required', 'numeric', 'gte:0'],
            'target_state' => ['nullable', 'in:'.implode(',', InventoryState::values())],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return $this->idempotentResponse($this->service->adjust(
            (string) $validated['inventory_item_id'],
            (string) $validated['operation_type'],
            (string) $validated['quantity'],
            isset($validated['target_state']) ? (string) $validated['target_state'] : null,
            (string) $validated['reason'],
            $this->context($request),
        ));
    }

    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inventory_item_id' => ['required', 'ulid'],
            'destination_location_id' => ['required', 'ulid'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return $this->idempotentResponse($this->service->move(
            (string) $validated['inventory_item_id'],
            (string) $validated['destination_location_id'],
            (string) $validated['quantity'],
            (string) $validated['reason'],
            $this->context($request),
        ));
    }

    public function blockLocation(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return $this->idempotentResponse($this->service->block(
            $id,
            (string) $validated['reason'],
            $this->context($request),
        ));
    }

    public function unblockLocation(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return $this->idempotentResponse($this->service->unblock(
            $id,
            (string) $validated['reason'],
            $this->context($request),
        ));
    }

    private function context(Request $request): InventoryMutationContext
    {
        return new InventoryMutationContext(
            $this->actorId($request),
            $this->correlationId($request),
            $this->requireIdempotencyKey($request),
            $this->sourceSurface($request),
        );
    }
}
