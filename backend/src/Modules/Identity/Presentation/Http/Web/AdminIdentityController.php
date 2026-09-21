<?php

namespace Sentai\Modules\Identity\Presentation\Http\Web;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Sentai\Modules\Identity\Application\DTO\AdminMutationContext;
use Sentai\Modules\Identity\Application\Services\AdminIdentityService;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Sentai\Shared\Application\DTO\IdempotentResponse;

final readonly class AdminIdentityController
{
    public function __construct(private AdminIdentityService $service) {}

    public function listUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:254'],
            'active' => ['nullable', 'in:0,1'],
            'role_code' => ['nullable', 'string', Rule::in(RoleCodes::ALL)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->service->list([
            'q' => $validated['q'] ?? null,
            'active' => array_key_exists('active', $validated) ? ((int) $validated['active'] === 1) : null,
            'role_code' => $validated['role_code'] ?? null,
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ]));
    }

    public function createUser(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        return $this->mutationResponse($this->service->create($payload, $this->mutationContext($request)));
    }

    public function updateUser(Request $request, string $id): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['sometimes', 'required', 'string', 'email:rfc', 'max:254'],
            'password' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        if ($payload === []) {
            throw ValidationException::withMessages([
                'user' => ['At least one mutable user attribute is required.'],
            ]);
        }

        return $this->mutationResponse($this->service->update($id, $payload, $this->mutationContext($request)));
    }

    public function disableUser(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->mutationResponse($this->service->disable(
            $id,
            isset($validated['reason']) ? trim((string) $validated['reason']) : null,
            $this->mutationContext($request),
        ));
    }

    public function assignRole(Request $request, string $userId): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'ulid'],
        ]);

        return $this->mutationResponse($this->service->assignRole(
            $userId,
            (string) $validated['role_id'],
            $this->mutationContext($request),
        ));
    }

    public function revokeRole(Request $request, string $userId, string $roleId): JsonResponse
    {
        return $this->mutationResponse($this->service->revokeRole(
            $userId,
            $roleId,
            $this->mutationContext($request),
        ));
    }

    private function mutationContext(Request $request): AdminMutationContext
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

        $roles = $request->attributes->get('sentai.actor_roles', []);

        if (! is_array($roles)) {
            $roles = [];
        }

        return new AdminMutationContext(
            (string) $user->getAuthIdentifier(),
            array_values(array_map(static fn (mixed $role): string => (string) $role, $roles)),
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
