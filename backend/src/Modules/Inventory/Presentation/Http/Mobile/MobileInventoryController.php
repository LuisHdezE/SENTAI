<?php

namespace Sentai\Modules\Inventory\Presentation\Http\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentai\Modules\Inventory\Application\DTO\InventoryMutationContext;
use Sentai\Modules\Inventory\Application\Services\ConfirmPutAwayService;
use Sentai\Modules\Inventory\Application\Services\ReceiveAsnService;
use Sentai\Shared\Presentation\Http\IdempotentMutationHttp;

/**
 * Mobile Inventory operations: API-INV-001 receiveAsn, API-INV-002 confirmPutAway.
 *
 * Both are ACT-002 operations and are therefore mounted under the mobile access
 * credential middleware with their exact capability.
 */
final readonly class MobileInventoryController
{
    use IdempotentMutationHttp;

    public function __construct(
        private ReceiveAsnService $receiveAsn,
        private ConfirmPutAwayService $putAway,
    ) {}

    public function receiveAsn(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.asn_line_id' => ['nullable', 'ulid'],
            'lines.*.product_id' => ['required', 'ulid'],
            'lines.*.received_qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_ref' => ['nullable', 'string', 'max:64'],
            'lines.*.serial_ref' => ['nullable', 'string', 'max:64'],
            'lines.*.discrepancy_note' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var list<array{asn_line_id?: ?string, product_id: string, received_qty: string, lot_ref?: ?string, serial_ref?: ?string, discrepancy_note?: ?string}> $lines */
        $lines = array_map(
            static fn (array $line): array => [
                'asn_line_id' => $line['asn_line_id'] ?? null,
                'product_id' => (string) $line['product_id'],
                'received_qty' => (string) $line['received_qty'],
                'lot_ref' => $line['lot_ref'] ?? null,
                'serial_ref' => $line['serial_ref'] ?? null,
                'discrepancy_note' => $line['discrepancy_note'] ?? null,
            ],
            $validated['lines'],
        );

        return $this->idempotentResponse($this->receiveAsn->receive($id, $lines, $this->context($request)));
    }

    public function confirmPutAway(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt_id' => ['required', 'ulid'],
            'destination_location_id' => ['required', 'ulid'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.receipt_line_id' => ['required', 'ulid'],
            'lines.*.inventory_item_id' => ['required', 'ulid'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        /** @var list<array{receipt_line_id: string, inventory_item_id: string, quantity: string}> $lines */
        $lines = array_map(
            static fn (array $line): array => [
                'receipt_line_id' => (string) $line['receipt_line_id'],
                'inventory_item_id' => (string) $line['inventory_item_id'],
                'quantity' => (string) $line['quantity'],
            ],
            $validated['lines'],
        );

        return $this->idempotentResponse($this->putAway->confirm(
            (string) $validated['receipt_id'],
            (string) $validated['destination_location_id'],
            $lines,
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
