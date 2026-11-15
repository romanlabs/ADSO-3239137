<?php

namespace Tests\Fakes;

use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> keyed by username */
    private array $users = [];

    public function findByUsername(string $username): ?User
    {
        return $this->users[$username] ?? null;
    }

    public function usernameExists(string $username): bool
    {
        return isset($this->users[$username]);
    }

    public function add(User $user): void
    {
        if ($this->usernameExists($user->username)) {
            throw UsernameTaken::create($user->username);
        }

        $this->users[$user->username] = $user;
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->users);
    }
}
