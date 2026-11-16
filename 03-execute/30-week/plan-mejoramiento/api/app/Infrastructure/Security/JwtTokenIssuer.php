<?php

namespace App\Infrastructure\Security;

use App\Application\Ports\Clock;
use App\Application\Ports\IssuedToken;
use App\Application\Ports\TokenIssuer;
use App\Domain\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use SensitiveParameter;
use Throwable;

/**
 * HS256 tokens that last eight hours and carry only the user id and the role (contract §4, plan D-11).
 * Nothing is stored server side, so no session or token table exists.
 */
final readonly class JwtTokenIssuer implements TokenIssuer
{
    private const ALGORITHM = 'HS256';

    /** HS256 needs a key of at least 256 bits. */
    private const MIN_SECRET_BYTES = 32;

    public function __construct(
        #[SensitiveParameter] private ?string $secret,
        private Clock $clock,
    ) {
    }

    public function issue(User $user): IssuedToken
    {
        $issuedAt = $this->clock->now()->getTimestamp();

        $token = JWT::encode([
            'sub' => $user->id,
            'role' => $user->role->value,
            'iat' => $issuedAt,
            'exp' => $issuedAt + IssuedToken::LIFETIME_SECONDS,
        ], $this->secret(), self::ALGORITHM);

        return new IssuedToken($token);
    }

    /**
     * Claims of a valid token, or null when the signature, the algorithm or the expiry is wrong.
     *
     * @return array{sub: string, role: string}|null
     */
    public function claimsOf(string $token): ?array
    {
        try {
            JWT::$timestamp = $this->clock->now()->getTimestamp();
            $claims = (array) JWT::decode($token, new Key($this->secret(), self::ALGORITHM));
        } catch (Throwable) {
            return null;
        } finally {
            JWT::$timestamp = null;
        }

        if (! is_string($claims['sub'] ?? null) || ! is_string($claims['role'] ?? null)) {
            return null;
        }

        return ['sub' => $claims['sub'], 'role' => $claims['role']];
    }

    private function secret(): string
    {
        if ($this->secret === null || strlen($this->secret) < self::MIN_SECRET_BYTES) {
            throw new LogicException('JWT_SECRET must be set and have at least '.self::MIN_SECRET_BYTES.' bytes.');
        }

        return $this->secret;
    }
}
