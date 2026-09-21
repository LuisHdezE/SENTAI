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
                    ['changed_fields' => $this->changedFields($payload)],
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
                    ['changed_fields' => $this->changedFields($payload)],
                );

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
            function () use ($id, $reason, $context): IdempotentResponse {
                $user = $this->repository->disable($id);
                $this->record(
                    'admin.user.disabled',
                    'disableAdminUser',
                    $user->id,
                    $context,
                    $reason === null ? [] : ['reason' => $reason],
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
                    ['role_id' => $roleId, 'role_code' => $assignment['role_code']],
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
                    ['role_id' => $roleId, 'role_code' => $assignment['role_code']],
                );

                return new IdempotentResponse(['data' => $assignment + ['revoked' => true]], 200);
            },
        );
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
