<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class MobileAuthenticationTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();

        Route::get('/api/v1/test/mobile-authenticated', static function (Request $request) {
            $identity = $request->attributes->get('sentai.identity');

            return response()->json(['user_id' => $identity?->id]);
        })->middleware('mobile.auth');
    }

    public function test_mobile_login_refresh_rotation_and_logout_revoke_credentials(): void
    {
        $user = $this->createUserWithRole('operator@example.test', RoleCodes::WAREHOUSE_OPERATOR);
        $correlationId = $this->correlationId();

        $login = $this->withHeader('X-Correlation-ID', $correlationId)
            ->postJson('/api/v1/auth/mobile/login', [
                'email' => 'operator@example.test',
                'password' => 'Secret123!',
            ]);

        $login->assertOk()->assertJsonStructure([
            'token_type',
            'access_token',
            'access_expires_at',
            'refresh_token',
            'refresh_expires_at',
        ]);

        $oldAccess = (string) $login->json('access_token');
        $oldRefresh = (string) $login->json('refresh_token');
        [, $oldAccessSecret] = explode('.', $oldAccess, 2);
        [, $oldRefreshSecret] = explode('.', $oldRefresh, 2);

        self::assertFalse(DB::table('mobile_access_tokens')->where('token_hash', $oldAccess)->exists());
        self::assertTrue(DB::table('mobile_access_tokens')->where('token_hash', hash('sha256', $oldAccessSecret))->exists());
        self::assertFalse(DB::table('mobile_sessions')->where('refresh_token_hash', $oldRefresh)->exists());
        self::assertTrue(DB::table('mobile_sessions')->where('refresh_token_hash', hash('sha256', $oldRefreshSecret))->exists());

        $this->withToken($oldAccess)
            ->getJson('/api/v1/test/mobile-authenticated')
            ->assertOk()
            ->assertJsonPath('user_id', (string) $user->getKey());

        $refresh = $this->postJson('/api/v1/auth/mobile/refresh', ['refresh_token' => $oldRefresh]);
        $refresh->assertOk();

        $newAccess = (string) $refresh->json('access_token');
        $newRefresh = (string) $refresh->json('refresh_token');
        self::assertNotSame($oldAccess, $newAccess);
        self::assertNotSame($oldRefresh, $newRefresh);

        $this->postJson('/api/v1/auth/mobile/refresh', ['refresh_token' => $oldRefresh])
            ->assertStatus(401)
            ->assertJsonPath('type', '/problems/authentication');

        $this->withToken($oldAccess)
            ->getJson('/api/v1/test/mobile-authenticated')
            ->assertStatus(401);

        $this->withToken($newAccess)
            ->postJson('/api/v1/auth/mobile/logout')
            ->assertNoContent();

        $this->withToken($newAccess)
            ->getJson('/api/v1/test/mobile-authenticated')
            ->assertStatus(401);

        foreach (['auth.login.success', 'auth.logout', 'auth.token.revoked'] as $eventType) {
            $this->assertDatabaseHas('audit_events', [
                'event_type' => $eventType,
                'actor_id' => (string) $user->getKey(),
            ]);
        }
    }

    public function test_non_warehouse_operator_cannot_obtain_mobile_credentials(): void
    {
        $this->createUserWithRole('customer@example.test', RoleCodes::CUSTOMER);

        $this->postJson('/api/v1/auth/mobile/login', [
            'email' => 'customer@example.test',
            'password' => 'Secret123!',
        ])->assertStatus(401)
            ->assertJsonPath('type', '/problems/authentication');
    }
}
