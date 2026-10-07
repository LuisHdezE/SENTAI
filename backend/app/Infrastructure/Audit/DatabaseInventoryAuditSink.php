<?php

namespace App\Infrastructure\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Inventory\Application\Contracts\InventoryAuditSink;
use Sentai\Modules\Inventory\Application\DTO\InventoryAuditEvent;

final class DatabaseInventoryAuditSink implements InventoryAuditSink
{
    public function record(InventoryAuditEvent $event): void
    {
        $roles = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $event->actorId)
            ->orderBy('roles.code')
            ->pluck('roles.code')
            ->map(static fn (mixed $role): string => (string) $role)
            ->all();

        DB::table('audit_events')->insert([
            'id' => (string) Str::ulid(),
            'event_type' => $event->eventType,
            'actor_id' => $event->actorId,
            'actor_role_snapshot' => json_encode($roles, JSON_THROW_ON_ERROR),
            'aggregate_type' => $event->aggregateType,
            'aggregate_id' => $event->aggregateId,
            'operation' => $event->operation,
            'outcome' => 'success',
            'correlation_id' => $event->correlationId,
            'source_surface' => $event->sourceSurface,
            'context' => json_encode($event->context, JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
