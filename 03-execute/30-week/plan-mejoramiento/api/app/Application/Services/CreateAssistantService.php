<?php

namespace App\Application\Services;

use App\Application\Ports\IdGenerator;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\User;
use SensitiveParameter;

/**
 * HU-02. Only assistants can be created through the API; the plain password is hashed here and never
 * stored, returned or placed inside an error (RNF-3).
 */
final readonly class CreateAssistantService
{
    public const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private IdGenerator $ids,
    ) {
    }

    public function create(string $username, #[SensitiveParameter] string $password, string $role): User
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw ValidationFailed::field('password', 'mínimo '.self::MIN_PASSWORD_LENGTH.' caracteres');
        }

        $user = User::createAssistant($this->ids->next(), $username, $this->hasher->hash($password), $role);

        if ($this->users->usernameExists($user->username)) {
            throw UsernameTaken::create($user->username);
        }

        $this->users->add($user);

        return $user;
    }
}
