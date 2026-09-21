<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;
use Tests\Support\CreatesIdentityFixtures;
use Tests\TestCase;

final class WebAuthenticationTest extends TestCase
{
    use CreatesIdentityFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCanonicalAuthorization();
    }

    public function test_web_csrf_login_and_logout_follow_backend_session_contract(): void
    {
        $user = $this->createUserWithRole('admin@example.test', RoleCodes::ADMINISTRATOR);
        $correlationId = $this->correlationId();

        $csrf = $this->withHeader('X-Correlation-ID', $correlationId)
            ->getJson('https://localhost/api/v1/auth/web/csrf');

        $csrf->assertOk()
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonStructure(['csrf_token']);

        $login = $this->withHeader('X-Correlation-ID', $correlationId)
            ->postJson('https://localhost/api/v1/auth/web/login', [
                'email' => 'admin@example.test',
                'password' => 'Secret123!',
            ]);

        $login->assertNoContent()->assertHeader('X-Correlation-ID', $correlationId);
        $this->assertAuthenticatedAs($user, 'web');

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.login.success',
            'actor_id' => (string) $user->getKey(),
            'correlation_id' => $correlationId,
        ]);

        $logout = $this->withHeader('X-Correlation-ID', $correlationId)
            ->postJson('https://localhost/api/v1/auth/web/logout');

        $logout->assertNoContent();
        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'auth.logout',
            'actor_id' => (string) $user->getKey(),
        ]);
    }

    public function test_wrong_password_and_unknown_account_return_same_authentication_problem(): void
    {
        $this->createUserWithRole('known@example.test', RoleCodes::ADMINISTRATOR);

        $known = $this->postJson('https://localhost/api/v1/auth/web/login', [
            'email' => 'known@example.test',
            'password' => 'WrongPassword!',
        ]);

        $unknown = $this->postJson('https://localhost/api/v1/auth/web/login', [
            'email' => 'unknown@example.test',
            'password' => 'WrongPassword!',
        ]);

        foreach ([$known, $unknown] as $response) {
            $response->assertStatus(401)
                ->assertHeader('Content-Type', 'application/problem+json')
                ->assertJsonPath('type', '/problems/authentication')
                ->assertJsonPath('detail', 'Authentication credentials are invalid, expired, or revoked.');
        }

        self::assertSame(2, DB::table('audit_events')->where('event_type', 'auth.login.failure')->count());
    }

    public function test_disabled_user_is_rejected_on_next_authenticated_web_request(): void
    {
        $user = $this->createUserWithRole('disable@example.test', RoleCodes::ADMINISTRATOR);

        $this->postJson('https://localhost/api/v1/auth/web/login', [
            'email' => 'disable@example.test',
            'password' => 'Secret123!',
        ])->assertNoContent();

        DB::table('users')->where('id', $user->getKey())->update(['is_active' => false]);

        $response = $this->getJson('https://localhost/api/v1/auth/web/csrf');

        $response->assertStatus(401)
            ->assertJsonPath('type', '/problems/authentication');
        $this->assertGuest('web');
    }
}
