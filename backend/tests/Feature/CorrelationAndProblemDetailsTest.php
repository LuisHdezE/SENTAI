<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class CorrelationAndProblemDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/v1/test/correlation', static function (Request $request) {
            return response()->json([
                'correlation_id' => $request->attributes->get('correlation_id'),
            ]);
        });

        Route::get('/api/v1/test/validation-problem', static function (): never {
            throw ValidationException::withMessages(['field' => ['Invalid field.']]);
        });

        Route::get('/api/v1/test/internal-problem', static function (): never {
            throw new RuntimeException('secret internal path /srv/sentai and SQLSTATE[99999]');
        });
    }

    public function test_server_generates_correlation_id_when_client_omits_it(): void
    {
        $response = $this->getJson('/api/v1/test/correlation');

        $response->assertOk();
        $header = (string) $response->headers->get('X-Correlation-ID');

        self::assertTrue(Str::isUuid($header));
        $response->assertJsonPath('correlation_id', $header);
    }

    public function test_valid_incoming_correlation_id_is_preserved(): void
    {
        $correlationId = (string) Str::uuid();

        $response = $this->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/test/correlation');

        $response->assertOk()
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonPath('correlation_id', $correlationId);
    }

    public function test_invalid_incoming_correlation_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Correlation-ID', '../invalid')
            ->getJson('/api/v1/test/correlation');

        $response->assertOk();
        $header = (string) $response->headers->get('X-Correlation-ID');

        self::assertNotSame('../invalid', $header);
        self::assertTrue(Str::isUuid($header));
    }

    public function test_validation_error_uses_rfc_9457_shape_and_correlation(): void
    {
        $correlationId = (string) Str::uuid();

        $response = $this->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('/api/v1/test/validation-problem');

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonPath('type', '/problems/validation')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('correlation_id', $correlationId)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'correlation_id', 'errors']);
    }

    public function test_internal_error_does_not_leak_exception_detail(): void
    {
        $response = $this->getJson('/api/v1/test/internal-problem');

        $response->assertStatus(500)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', '/problems/transient_infrastructure')
            ->assertJsonMissingExact(['detail' => 'secret internal path /srv/sentai and SQLSTATE[99999]']);

        self::assertStringNotContainsString('/srv/sentai', $response->getContent());
        self::assertStringNotContainsString('SQLSTATE', $response->getContent());
    }
}
