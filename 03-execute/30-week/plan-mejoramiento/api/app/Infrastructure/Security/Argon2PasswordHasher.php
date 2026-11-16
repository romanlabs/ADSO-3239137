<?php

namespace App\Infrastructure\Security;

use App\Application\Ports\PasswordHasher;
use LogicException;
use SensitiveParameter;

/**
 * Argon2id through PHP's own password API (plan D-09). The plain password is marked as sensitive, so it is replaced by
 * a placeholder in any stack trace that reaches a log (RNF-3).
 */
final class Argon2PasswordHasher implements PasswordHasher
{
    public function hash(#[SensitiveParameter] string $plainPassword): string
    {
        if (! defined('PASSWORD_ARGON2ID')) {
            throw new LogicException('This PHP build has no Argon2id support.');
        }

        return password_hash($plainPassword, PASSWORD_ARGON2ID);
    }

    public function verify(#[SensitiveParameter] string $plainPassword, #[SensitiveParameter] string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}
