<?php

namespace Tests\Fakes;

use App\Application\Ports\PasswordHasher;

/** Reversible-looking but never equal to the plain text, so tests can prove the plain password is not stored. */
final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'fake-hash:'.hash('sha256', $plainPassword);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return hash_equals($this->hash($plainPassword), $hash);
    }
}
