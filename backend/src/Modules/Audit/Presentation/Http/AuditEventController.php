<?php

namespace Sentai\Modules\Audit\Presentation\Http;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentai\Modules\Audit\Application\DTO\AuditAccessContext;
use Sentai\Modules\Audit\Application\Services\AuditQueryService;

final readonly class AuditEventController
{
    public function __construct(private AuditQueryService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'event_type' => ['nullable', 'string', 'max:120'],
            'actor_id' => ['nullable', 'ulid'],
            'aggregate_type' => ['nullable', 'string', 'max:120'],
            'aggregate_id' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $actorId = $request->attributes->get('sentai.actor_id');

        if (! is_string($actorId) || $actorId === '') {
            throw new AuthenticationException;
        }

        $roles = $request->attributes->get('sentai.actor_roles', []);

        if (! is_array($roles)) {
            $roles = [];
        }

        $filters = [
            'from' => $this->normalizeTime($validated['from'] ?? null),
            'to' => $this->normalizeTime($validated['to'] ?? null),
            'event_type' => $validated['event_type'] ?? null,
            'actor_id' => $validated['actor_id'] ?? null,
            'aggregate_type' => $validated['aggregate_type'] ?? null,
            'aggregate_id' => $validated['aggregate_id'] ?? null,
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
        ];

        return response()->json($this->service->list(
            $filters,
            new AuditAccessContext(
                $actorId,
                array_values(array_map(static fn (mixed $role): string => (string) $role, $roles)),
                (string) $request->attributes->get('correlation_id', 'unavailable'),
                (string) $request->attributes->get('sentai.source_surface', 'backoffice'),
            ),
        ));
    }

    private function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return (new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s.v');
    }
}
