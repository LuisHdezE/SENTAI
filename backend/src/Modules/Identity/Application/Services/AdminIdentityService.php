<?php

namespace Sentai\Modules\Identity\Application\Services;

use DateTimeImmutable;
use Sentai\Modules\Identity\Application\Contracts\AdminIdentityRepository;
use Sentai\Modules\Identity\Application\Contracts\SecurityAuditSink;
use Sentai\Modules\Identity\Application\DTO\AdminMutationContext;
use Sentai\Modules\Identity\Application\DTO\SecurityAuditEvent;
use Sentai\Modules\Identity\Domain\Entities\AdminUser;
use Sentai\Shared\Application\Contracts\IdempotencyGate;
use Sentai\Shared\Application\DTO\IdempotentResponse;

final readonly class AdminIdentityService
{
    public function __construct(
        private AdminIdentityRepository $repository,
        private SecurityAuditSink $audit,
        private IdempotencyGate $idempotency,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function list(array $filters): array
    {
        $page = $this->repository->list($filters);

        return [
            'data' => array_map(
                static fn (AdminUser $user): array => $user->toArray(),
                $page['items'],
            ),
            'meta' => [
                'page' => $page['page'],
                'per_page' => $page['per_page'],
                'total' => $page['total'],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload, AdminMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            'createAdminUser',
            $context->idempotencyKey,
            $this->hashRequest(['payload' => $payload]),
            function () use ($payload, $context): IdempotentResponse {
                $user = $this->repository->create($payload);
                $this->record(
                    'admin.user.created',
                    'createAdminUser',
                    $user->id,
                    $context,
                    [
                        'created_user_id' => $user->id,
                        'created_by' => $context->actorId,
                    ],
                );

                return new IdempotentResponse(['data' => $user->toArray()], 201);
            },
        );
    }

    /** @param array<string, mixed> $payload */
    public function update(string $id, array $payload, AdminMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            'updateAdminUser',
            $context->idempotencyKey,
            $this->hashRequest(['id' => $id, 'payload' => $payload]),
            function () use ($id, $payload, $context): IdempotentResponse {
                $user = $this->repository->update($id, $payload);
                $this->record(
                    'admin.user.updated',
                    'updateAdminUser',
                    $user->id,
                    $context,
                    [
                        'updated_user_id' => $user->id,
                        'fields_changed' => $this->changedFields($payload),
                        'updated_by' => $context->actorId,
                    ],
                );

                if (array_key_exists('password', $payload)) {
                    $this->recordRevocations(
                        $user->id,
                        'updateAdminUser',
                        $context,
                        $this->repository->revokeSessions($user->id),
                    );
                }

                return new IdempotentResponse(['data' => $user->toArray()], 200);
            },
        );
    }

    public function disable(string $id, ?string $reason, AdminMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            'disableAdminUser',
            $context->idempotencyKey,
            $this->hashRequest(['id' => $id, 'reason' => $reason]),
            function () use ($id, $context): IdempotentResponse {
                $user = $this->repository->disable($id);
                $this->record(
                    'admin.user.disabled',
                    'disableAdminUser',
                    $user->id,
                    $context,
                    [
                        'disabled_user_id' => $user->id,
                        'disabled_by' => $context->actorId,
                    ],
                );
                $this->recordRevocations(
                    $user->id,
                    'disableAdminUser',
                    $context,
                    $this->repository->revokeSessions($user->id),
                );

                return new IdempotentResponse(['data' => $user->toArray()], 200);
            },
        );
    }

    public function assignRole(string $userId, string $roleId, AdminMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            'assignUserRole',
            $context->idempotencyKey,
            $this->hashRequest(['user_id' => $userId, 'role_id' => $roleId]),
            function () use ($userId, $roleId, $context): IdempotentResponse {
                $assignment = $this->repository->assignRole($userId, $roleId);
                $this->record(
                    'authz.role.assigned',
                    'assignUserRole',
                    $userId,
                    $context,
                    [
                        'target_user_id' => $userId,
                        'role_id' => $roleId,
                        'role_code' => $assignment['role_code'],
                        'assigned_by' => $context->actorId,
                    ],
                );
                $this->recordRevocations(
                    $userId,
                    'assignUserRole',
                    $context,
                    $this->repository->revokeSessions($userId),
                );

                return new IdempotentResponse(['data' => $assignment], 200);
            },
        );
    }

    public function revokeRole(string $userId, string $roleId, AdminMutationContext $context): IdempotentResponse
    {
        return $this->idempotency->execute(
            'revokeUserRole',
            $context->idempotencyKey,
            $this->hashRequest(['user_id' => $userId, 'role_id' => $roleId]),
            function () use ($userId, $roleId, $context): IdempotentResponse {
                $assignment = $this->repository->revokeRole($userId, $roleId);
                $this->record(
                    'authz.role.revoked',
                    'revokeUserRole',
                    $userId,
                    $context,
                    [
                        'target_user_id' => $userId,
                        'role_id' => $roleId,
                        'role_code' => $assignment['role_code'],
                        'revoked_by' => $context->actorId,
                    ],
                );
                $this->recordRevocations(
                    $userId,
                    'revokeUserRole',
                    $context,
                    $this->repository->revokeSessions($userId),
                );

                return new IdempotentResponse(['data' => $assignment + ['revoked' => true]], 200);
            },
        );
    }

    /**
     * @param array{web_sessions: int, mobile_sessions: int, mobile_tokens: int} $revocations
     */
    private function recordRevocations(
        string $targetUserId,
        string $operation,
        AdminMutationContext $context,
        array $revocations,
    ): void {
        if ($revocations['web_sessions'] > 0 || $revocations['mobile_sessions'] > 0) {
            $this->record(
                'auth.session.revoked',
                $operation,
                $targetUserId,
                $context,
                [
                    'revoked_session_ref' => 'all_active_sessions',
                    'revoked_by' => $context->actorId,
                    'web_session_count' => $revocations['web_sessions'],
                    'mobile_session_count' => $revocations['mobile_sessions'],
                ],
            );
        }

        if ($revocations['mobile_tokens'] > 0) {
            $this->record(
                'auth.token.revoked',
                $operation,
                $targetUserId,
                $context,
                [
                    'token_ref' => 'all_active_tokens',
                    'revoked_by' => $context->actorId,
                    'token_count' => $revocations['mobile_tokens'],
                ],
            );
        }
    }

    /** @param array<string, scalar|null> $eventContext */
    private function record(
        string $eventType,
        string $operation,
        string $targetUserId,
        AdminMutationContext $context,
        array $eventContext = [],
    ): void {
        $this->audit->record(new SecurityAuditEvent(
            eventType: $eventType,
            actorId: $context->actorId,
            actorRoleSnapshot: $context->actorRoleSnapshot,
            operation: $operation,
            outcome: 'success',
            correlationId: $context->correlationId,
            sourceSurface: 'backoffice',
            occurredAt: new DateTimeImmutable('now'),
            aggregateType: 'User',
            aggregateId: $targetUserId,
            context: $eventContext,
        ));
    }

    /** @param array<string, mixed> $payload */
    private function changedFields(array $payload): string
    {
        $fields = array_map(
            static fn (string $field): string => $field === 'password' ? 'credential' : $field,
            array_keys($payload),
        );

        sort($fields);

        return implode(',', $fields);
    }

    /** @param array<string, mixed> $payload */
    private function hashRequest(array $payload): string
    {
        return hash('sha256', json_encode($this->canonicalize($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (is_array($value) === false) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
