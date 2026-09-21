<?php

namespace Sentai\Modules\MasterData\Presentation\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Sentai\Modules\MasterData\Application\DTO\MasterMutationContext;
use Sentai\Modules\MasterData\Application\Services\MasterDataService;
use Sentai\Modules\MasterData\Domain\ValueObjects\MasterType;
use Sentai\Shared\Application\DTO\IdempotentResponse;

final readonly class MasterDataController
{
    public function __construct(private MasterDataService $service) {}

    public function listProducts(Request $request): JsonResponse
    {
        return response()->json($this->service->list(MasterType::Product, $this->filters($request)));
    }

    public function createProduct(Request $request): JsonResponse
    {
        return $this->createMaster($request, MasterType::Product, 'createProduct');
    }

    public function updateProduct(Request $request, string $id): JsonResponse
    {
        return $this->updateMaster($request, MasterType::Product, 'updateProduct', $id);
    }

    public function listCustomers(Request $request): JsonResponse
    {
        return response()->json($this->service->list(MasterType::Customer, $this->filters($request)));
    }

    public function createCustomer(Request $request): JsonResponse
    {
        return $this->createMaster($request, MasterType::Customer, 'createCustomer');
    }

    public function updateCustomer(Request $request, string $id): JsonResponse
    {
        return $this->updateMaster($request, MasterType::Customer, 'updateCustomer', $id);
    }

    public function listWarehouses(Request $request): JsonResponse
    {
        return response()->json($this->service->list(MasterType::Warehouse, $this->filters($request)));
    }

    public function createWarehouse(Request $request): JsonResponse
    {
        return $this->createMaster($request, MasterType::Warehouse, 'createWarehouse');
    }

    public function updateWarehouse(Request $request, string $id): JsonResponse
    {
        return $this->updateMaster($request, MasterType::Warehouse, 'updateWarehouse', $id);
    }

    public function listZones(Request $request): JsonResponse
    {
        return response()->json($this->service->list(MasterType::Zone, $this->filters($request, true)));
    }

    public function createZone(Request $request): JsonResponse
    {
        return $this->createMaster($request, MasterType::Zone, 'createZone');
    }

    public function updateZone(Request $request, string $id): JsonResponse
    {
        return $this->updateMaster($request, MasterType::Zone, 'updateZone', $id);
    }

    public function listLocations(Request $request): JsonResponse
    {
        return response()->json($this->service->list(MasterType::Location, $this->filters($request, true, true)));
    }

    public function createLocation(Request $request): JsonResponse
    {
        return $this->createMaster($request, MasterType::Location, 'createLocation');
    }

    public function updateLocation(Request $request, string $id): JsonResponse
    {
        return $this->updateMaster($request, MasterType::Location, 'updateLocation', $id);
    }

    private function createMaster(Request $request, MasterType $type, string $operationId): JsonResponse
    {
        $result = $this->service->create(
            $type,
            $operationId,
            $this->mutationPayload($request, $type),
            $this->mutationContext($request),
        );

        return $this->mutationResponse($result);
    }

    private function updateMaster(Request $request, MasterType $type, string $operationId, string $id): JsonResponse
    {
        $result = $this->service->update(
            $type,
            $operationId,
            $id,
            $this->mutationPayload($request, $type),
            $this->mutationContext($request),
        );

        return $this->mutationResponse($result);
    }

    /** @return array<string, mixed> */
    private function mutationPayload(Request $request, MasterType $type): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];

        if (in_array($type, [MasterType::Zone, MasterType::Location], true)) {
            $rules['warehouse_id'] = ['required', 'ulid'];
        }

        if ($type === MasterType::Location) {
            $rules['zone_id'] = ['required', 'ulid'];
        }

        $payload = $request->validate($rules);
        $payload['is_active'] = (bool) $payload['is_active'];

        return $payload;
    }

    /** @return array<string, mixed> */
    private function filters(Request $request, bool $warehouse = false, bool $zone = false): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];

        if ($warehouse) {
            $rules['warehouse_id'] = ['nullable', 'ulid'];
        }

        if ($zone) {
            $rules['zone_id'] = ['nullable', 'ulid'];
        }

        $validated = $request->validate($rules);

        return [
            'q' => $validated['q'] ?? null,
            'active' => array_key_exists('active', $validated) ? ((int) $validated['active'] === 1) : null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'zone_id' => $validated['zone_id'] ?? null,
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ];
    }

    private function mutationContext(Request $request): MasterMutationContext
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));

        if ($idempotencyKey === '' || mb_strlen($idempotencyKey) > 255) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => ['A non-empty Idempotency-Key header up to 255 characters is required.'],
            ]);
        }

        return new MasterMutationContext(
            (string) $user->getAuthIdentifier(),
            (string) $request->attributes->get('correlation_id', 'unavailable'),
            $idempotencyKey,
        );
    }

    private function mutationResponse(IdempotentResponse $response): JsonResponse
    {
        return response()->json(
            $response->body,
            $response->status,
            $response->replayed ? ['Idempotency-Replayed' => 'true'] : [],
        );
    }
}
