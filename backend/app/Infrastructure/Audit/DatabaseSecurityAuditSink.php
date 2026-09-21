<?php

namespace App\Infrastructure\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;

final class DatabaseSecurityAuditSink implements SecurityAuditSink
{
    public function record(SecurityAuditEvent $event): void
    {
        DB::table('audit_events')->insert([
            'id' => (string) Str::ulid(),
            'event_type' => $event->eventType,
            'actor_id' => $event->actorId,
            'actor_role_snapshot' => json_encode($event->actorRoleSnapshot, JSON_THROW_ON_ERROR),
            'aggregate_type' => $event->aggregateType,
            'aggregate_id' => $event->aggregateId,
            'operation' => $event->operation,
            'outcome' => $event->outcome,
            'correlation_id' => $event->correlationId,
            'source_surface' => $event->sourceSurface,
            'context' => json_encode($event->context, JSON_THROW_ON_ERROR),
            'occurred_at' => $event->occurredAt,
        ]);
    }
}
