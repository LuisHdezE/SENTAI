<?php

namespace Sentai\Modules\Identity\Infrastructure\Authentication;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Identity\Application\Contracts\CredentialVerifier;
use Sentai\Modules\Identity\Application\Contracts\MobileTokenStore;
use Sentai\Modules\Identity\Application\DTO\AuthenticatedIdentity;
use Sentai\Modules\Identity\Application\DTO\MobileAccessContext;
use Sentai\Modules\Identity\Application\DTO\MobileTokenPair;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;

final readonly class DatabaseMobileTokenStore implements MobileTokenStore
{
    public function __construct(private CredentialVerifier $credentials)
    {
    }

    public function issue(AuthenticatedIdentity $identity): MobileTokenPair
    {
        return DB::transaction(function () use ($identity): MobileTokenPair {
            $now = $this->now();
            $sessionId = (string) Str::ulid();
            $refreshSecret = $this->secret();
            $refreshExpiresAt = $now->modify('+'.max(1, (int) config('sentai.security.mobile_refresh_ttl_days', 30)).' days');

            DB::table('mobile_sessions')->insert([
                'id' => $sessionId,
                'user_id' => $identity->id,
                'refresh_token_hash' => hash('sha256', $refreshSecret),
                'refresh_expires_at' => $refreshExpiresAt,
                'revoked_at' => null,
                'last_rotated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $this->issuePairForSession($sessionId, $refreshSecret, $refreshExpiresAt, $now);
        });
    }

    public function rotate(string $refreshToken): ?MobileTokenPair
    {
        [$sessionId, $secret] = $this->parse($refreshToken);

        if ($sessionId === null || $secret === null) {
            return null;
        }

        return DB::transaction(function () use ($sessionId, $secret): ?MobileTokenPair {
            $session = DB::table('mobile_sessions')
                ->where('id', $sessionId)
                ->lockForUpdate()
                ->first();

            $now = $this->now();

            if ($session === null
                || $session->revoked_at !== null
                || new DateTimeImmutable((string) $session->refresh_expires_at, new DateTimeZone('UTC')) <= $now
                || ! hash_equals((string) $session->refresh_token_hash, hash('sha256', $secret))) {
                return null;
            }

            $identity = $this->credentials->byId((string) $session->user_id);

            if ($identity === null || ! $identity->hasRole(RoleCodes::WAREHOUSE_OPERATOR)) {
                return null;
            }

            $newRefreshSecret = $this->secret();
            $refreshExpiresAt = $now->modify('+'.max(1, (int) config('sentai.security.mobile_refresh_ttl_days', 30)).' days');

            DB::table('mobile_sessions')
                ->where('id', $sessionId)
                ->update([
                    'refresh_token_hash' => hash('sha256', $newRefreshSecret),
                    'refresh_expires_at' => $refreshExpiresAt,
                    'last_rotated_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('mobile_access_tokens')
                ->where('mobile_session_id', $sessionId)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            return $this->issuePairForSession($sessionId, $newRefreshSecret, $refreshExpiresAt, $now);
        }, 3);
    }

    public function authenticateAccess(string $accessToken): ?MobileAccessContext
    {
        [$tokenId, $secret] = $this->parse($accessToken);

        if ($tokenId === null || $secret === null) {
            return null;
        }

        $row = DB::table('mobile_access_tokens')
            ->join('mobile_sessions', 'mobile_sessions.id', '=', 'mobile_access_tokens.mobile_session_id')
            ->where('mobile_access_tokens.id', $tokenId)
            ->select([
                'mobile_access_tokens.id as token_id',
                'mobile_access_tokens.token_hash',
                'mobile_access_tokens.expires_at as access_expires_at',
                'mobile_access_tokens.revoked_at as access_revoked_at',
                'mobile_sessions.id as session_id',
                'mobile_sessions.user_id',
                'mobile_sessions.refresh_expires_at',
                'mobile_sessions.revoked_at as session_revoked_at',
            ])
            ->first();

        $now = $this->now();

        if ($row === null
            || $row->access_revoked_at !== null
            || $row->session_revoked_at !== null
            || new DateTimeImmutable((string) $row->access_expires_at, new DateTimeZone('UTC')) <= $now
            || new DateTimeImmutable((string) $row->refresh_expires_at, new DateTimeZone('UTC')) <= $now
            || ! hash_equals((string) $row->token_hash, hash('sha256', $secret))) {
            return null;
        }

        $identity = $this->credentials->byId((string) $row->user_id);

        if ($identity === null || ! $identity->hasRole(RoleCodes::WAREHOUSE_OPERATOR)) {
            return null;
        }

        return new MobileAccessContext($identity, (string) $row->session_id, (string) $row->token_id);
    }

    public function revokeSession(string $sessionId): void
    {
        DB::transaction(function () use ($sessionId): void {
            $now = $this->now();

            DB::table('mobile_sessions')
                ->where('id', $sessionId)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            DB::table('mobile_access_tokens')
                ->where('mobile_session_id', $sessionId)
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);
        }, 3);
    }

    private function issuePairForSession(
        string $sessionId,
        string $refreshSecret,
        DateTimeImmutable $refreshExpiresAt,
        DateTimeImmutable $now,
    ): MobileTokenPair {
        $accessTokenId = (string) Str::ulid();
        $accessSecret = $this->secret();
        $accessExpiresAt = $now->modify('+'.max(1, (int) config('sentai.security.mobile_access_ttl_minutes', 15)).' minutes');

        DB::table('mobile_access_tokens')->insert([
            'id' => $accessTokenId,
            'mobile_session_id' => $sessionId,
            'token_hash' => hash('sha256', $accessSecret),
            'expires_at' => $accessExpiresAt,
            'revoked_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return new MobileTokenPair(
            accessToken: $accessTokenId.'.'.$accessSecret,
            accessExpiresAt: $accessExpiresAt,
            refreshToken: $sessionId.'.'.$refreshSecret,
            refreshExpiresAt: $refreshExpiresAt,
        );
    }

    /** @return array{0: ?string, 1: ?string} */
    private function parse(string $token): array
    {
        $parts = explode('.', trim($token), 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    private function secret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
