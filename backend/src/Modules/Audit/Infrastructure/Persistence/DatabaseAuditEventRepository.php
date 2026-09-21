<?php

namespace Sentai\Modules\Audit\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Audit\Application\Contracts\AuditEventRepository;
use Sentai\Modules\Audit\Application\DTO\AuditAccessContext;
use Sentai\Modules\Audit\Domain\Entities\AuditEvent;
use stdClass;

final class DatabaseAuditEventRepository implements AuditEventRepository
{
    public function list(array $filters): array
    {
        $query = DB::table('audit_events');

        foreach (['event_type', 'actor_id', 'aggregate_type', 'aggregate_id'] as $filter) {
            if (($filters[$filter] ?? null) !== null) {
                $query->where($filter, (string) $filters[$filter]);
            }
        }

        if (($filters['from'] ?? null) !== null) {
            $query->where('occurred_at', '>=', (string) $filters['from']);
        }

        if (($filters['to'] ?? null) !== null) {
            $query->where('occurred_at', '<=', (string) $filters['to']);
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 25);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        return [
            'items' => $rows->map(fn (stdClass $row): AuditEvent => $this->toEntity($row))->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function recordAccess(array $filters, AuditAccessContext $context): void
    {
        $scope = [];

        foreach (['from', 'to', 'event_type', 'actor_id', 'aggregate_type', 'aggregate_id'] as $key) {
            if (($filters[$key] ?? null) !== null) {
                $scope[$key] = (string) $filters[$key];
            }
        }

        DB::table('audit_events')->insert([
            'id' => (string) Str::ulid(),
            'event_type' => 'authz.audit.access',
            'actor_id' => $context->actorId,
            'actor_role_snapshot' => json_encode($context->actorRoleSnapshot, JSON_THROW_ON_ERROR),
            'aggregate_type' => null,
            'aggregate_id' => null,
            'operation' => 'listAuditEvents',
            'outcome' => 'success',
            'correlation_id' => $context->correlationId,
            'source_surface' => $context->sourceSurface,
            'context' => json_encode([
                'query_scope' => $scope === [] ? 'unfiltered' : json_encode($scope, JSON_THROW_ON_ERROR),
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }

    private function toEntity(stdClass $row): AuditEvent
    {
        $roles = $this->decodeArray((string) $row->actor_role_snapshot);
        $context = $this->sanitizeContext($this->decodeArray((string) $row->context));

        return new AuditEvent(
            (string) $row->id,
            (string) $row->event_type,
            $row->actor_id === null ? null : (string) $row->actor_id,
            array_values(array_map(static fn (mixed $role): string => (string) $role, $roles)),
            $row->aggregate_type === null ? null : (string) $row->aggregate_type,
            $row->aggregate_id === null ? null : (string) $row->aggregate_id,
            (string) $row->operation,
            (string) $row->outcome,
            (string) $row->correlation_id,
            (string) $row->source_surface,
            $context,
            (string) $row->occurred_at,
        );
    }

    /** @return array<string|int, mixed> */
    private function decodeArray(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string|int, mixed> $context @return array<string|int, mixed> */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_string($key) && preg_match('/password|token|secret|authorization|cookie|credential/i', $key) === 1) {
                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitizeContext($value) : $value;
        }

        return $sanitized;
    }
}
