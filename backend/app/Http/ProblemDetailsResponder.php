<?php

namespace App\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Sentai\Modules\Identity\Application\Exceptions\AuthenticationFailed;
use Sentai\Modules\Identity\Application\Exceptions\AuthorizationDenied;
use Sentai\Shared\Application\Exceptions\DomainConflict;
use Sentai\Shared\Application\Exceptions\IdempotencyConflict;
use Sentai\Shared\Application\Exceptions\ResourceNotFound;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ProblemDetailsResponder
{
    public static function fromThrowable(Throwable $throwable, Request $request): JsonResponse
    {
        if ($throwable instanceof ValidationException) {
            return self::response($request, 'validation', 422, 'Validation failed', 'The request payload is invalid.', [
                'errors' => $throwable->errors(),
            ]);
        }

        if ($throwable instanceof AuthenticationFailed || $throwable instanceof AuthenticationException) {
            return self::response($request, 'authentication', 401, 'Authentication required', 'Authentication credentials are invalid, expired, or revoked.');
        }

        if ($throwable instanceof AuthorizationDenied || $throwable instanceof AuthorizationException || $throwable instanceof TokenMismatchException) {
            return self::response($request, 'authorization', 403, 'Authorization denied', 'The authenticated request is not authorized for this operation.');
        }

        if ($throwable instanceof IdempotencyConflict) {
            return self::response($request, 'idempotency_conflict', 409, 'Idempotency conflict', $throwable->getMessage());
        }

        if ($throwable instanceof DomainConflict) {
            return self::response($request, 'domain_conflict', 409, 'Domain conflict', $throwable->getMessage());
        }

        if ($throwable instanceof ResourceNotFound || $throwable instanceof ModelNotFoundException || $throwable instanceof NotFoundHttpException) {
            return self::response($request, 'resource_not_found', 404, 'Resource not found', 'The requested resource was not found.');
        }

        return self::response($request, 'transient_infrastructure', 500, 'Internal server error', 'An unexpected server error occurred.');
    }

    public static function response(
        Request $request,
        string $family,
        int $status,
        string $title,
        string $detail,
        array $extensions = [],
    ): JsonResponse {
        $correlationId = (string) $request->attributes->get('correlation_id', 'unavailable');

        return new JsonResponse([
            'type' => '/problems/'.$family,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => 'urn:sentai:problem-instance:'.$correlationId,
            'correlation_id' => $correlationId,
            ...$extensions,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
