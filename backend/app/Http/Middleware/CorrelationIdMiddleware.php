<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CorrelationIdMiddleware
{
    public const HEADER = 'X-Correlation-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $provided = trim((string) $request->headers->get(self::HEADER, ''));
        $correlationId = $this->isValid($provided) ? $provided : (string) Str::uuid();

        $request->attributes->set('correlation_id', $correlationId);
        Log::withContext(['correlation_id' => $correlationId]);

        $response = $next($request);
        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }

    private function isValid(string $value): bool
    {
        if ($value === '' || strlen($value) > 128) {
            return false;
        }

        if (Str::isUuid($value)) {
            return true;
        }

        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $value) === 1;
    }
}
