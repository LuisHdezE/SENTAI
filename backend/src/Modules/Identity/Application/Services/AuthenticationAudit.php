<?php

namespace Sentai\Modules\Identity\Application\Services;

use DateTimeImmutable;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;

final readonly class AuthenticationAudit
{
    public function __construct(private SecurityAuditSink $sink) {}

    public function loginSuccess(AuthenticatedIdentity $identity, string $operation, string $surface, string $correlationId): void
    {
        $this->sink->record(new SecurityAuditEvent(
            eventType: 'auth.login.success',
            actorId: $identity->id,
            actorRoleSnapshot: $identity->roleCodes,
            operation: $operation,
            outcome: 'success',
            correlationId: $correlationId,
            sourceSurface: $surface,
            occurredAt: new DateTimeImmutable('now'),
            aggregateType: 'User',
            aggregateId: $identity->id,
        ));
    }

    public function loginFailure(string $attemptedIdentity, string $operation, string $surface, string $correlationId): void
    {
        $this->sink->record(new SecurityAuditEvent(
            eventType: 'auth.login.failure',
            actorId: null,
            actorRoleSnapshot: [],
            operation: $operation,
            outcome: 'failure',
            correlationId: $correlationId,
            sourceSurface: $surface,
            occurredAt: new DateTimeImmutable('now'),
            context: [
                'attempted_identity' => mb_strtolower(trim($attemptedIdentity)),
                'failure_reason' => 'invalid_credentials_or_access',
            ],
        ));
    }

    public function logout(AuthenticatedIdentity $identity, string $operation, string $surface, string $correlationId, string $sessionRef): void
    {
        $this->sink->record(new SecurityAuditEvent(
            eventType: 'auth.logout',
            actorId: $identity->id,
            actorRoleSnapshot: $identity->roleCodes,
            operation: $operation,
            outcome: 'success',
            correlationId: $correlationId,
            sourceSurface: $surface,
            occurredAt: new DateTimeImmutable('now'),
            aggregateType: 'User',
            aggregateId: $identity->id,
            context: ['session_ref' => $sessionRef],
        ));
    }

    public function tokenRevoked(AuthenticatedIdentity $identity, string $correlationId, string $tokenRef): void
    {
        $this->sink->record(new SecurityAuditEvent(
            eventType: 'auth.token.revoked',
            actorId: $identity->id,
            actorRoleSnapshot: $identity->roleCodes,
            operation: 'mobileLogout',
            outcome: 'success',
            correlationId: $correlationId,
            sourceSurface: 'mobile',
            occurredAt: new DateTimeImmutable('now'),
            aggregateType: 'User',
            aggregateId: $identity->id,
            context: ['token_ref' => $tokenRef],
        ));
    }
}
