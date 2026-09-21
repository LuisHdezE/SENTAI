<?php

namespace App\Infrastructure\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\MasterData\Application\Contracts\MasterDataAuditSink;
use Sentai\Modules\MasterData\Application\DTO\MasterDataAuditEvent;

final class DatabaseMasterDataAuditSink implements MasterDataAuditSink
{
    public function record(MasterDataAuditEvent $event): void
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
            'event_type' => 'admin.masters.changed',
            'actor_id' => $event->actorId,
            'actor_role_snapshot' => json_encode($roles, JSON_THROW_ON_ERROR),
            'aggregate_type' => $event->type->aggregateName(),
            'aggregate_id' => $event->aggregateId,
            'operation' => $event->operation,
            'outcome' => 'success',
            'correlation_id' => $event->correlationId,
            'source_surface' => 'backoffice',
            'context' => json_encode([
                'master_type' => $event->type->value,
                'changed_fields' => $event->changedFields,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
