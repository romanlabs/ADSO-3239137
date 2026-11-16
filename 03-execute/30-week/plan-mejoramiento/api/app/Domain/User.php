<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;
use SensitiveParameter;

/**
 * Internal operator. The domain only ever sees the password hash, never the plain password.
 */
final readonly class User
{
    private function __construct(
        public string $id,
        public string $username,
        public string $passwordHash,
        public Role $role,
    ) {
    }

    /** HU-02: the API can only create assistants; asking for admin is a validation error. */
    public static function createAssistant(string $id, string $username, #[SensitiveParameter] string $passwordHash, string $requestedRole): self
    {
        if ($requestedRole !== Role::Assistant->value) {
            throw ValidationFailed::field('role', 'solo se puede crear el rol assistant');
        }

        return new self($id, self::normalizeUsername($username), $passwordHash, Role::Assistant);
    }

    /** The initial admin is provisioned from the environment at boot, never through the API. */
    public static function provisionAdmin(string $id, string $username, #[SensitiveParameter] string $passwordHash): self
    {
        return new self($id, self::normalizeUsername($username), $passwordHash, Role::Admin);
    }

    public static function restore(string $id, string $username, #[SensitiveParameter] string $passwordHash, string $role): self
    {
        return new self($id, $username, $passwordHash, Role::from($role));
    }

    public static function normalizeUsername(string $username): string
    {
        $normalized = mb_strtolower(trim($username));

        if (preg_match('/^[a-z0-9._-]{3,120}$/', $normalized) !== 1) {
            throw ValidationFailed::field('username', 'de 3 a 120 caracteres: letras, dígitos, punto, guion o guion bajo');
        }

        return $normalized;
    }
}
