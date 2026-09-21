<?php

namespace Sentai\Modules\Identity\Application\Services;

use DateTimeImmutable;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;

final readonly class AuthorizationAudit
{
    public function __construct(private SecurityAuditSink $sink)
    {
    }

    public function denied(
        AuthenticatedIdentity $identity,
        string $operation,
        string $capability,
        string $resourceRef,
        string $correlationId,
        string $surface,
    ): void {
        $this->sink->record(new SecurityAuditEvent(
            eventType: 'authz.denial.significant',
            actorId: $identity->id,
            actorRoleSnapshot: $identity->roleCodes,
            operation: $operation,
            outcome: 'failure',
            correlationId: $correlationId,
            sourceSurface: $surface,
            occurredAt: new DateTimeImmutable('now'),
            context: [
                'attempted_capability' => $capability,
                'resource_ref' => $resourceRef,
                'reason' => 'missing_capability',
            ],
        ));
    }
}
