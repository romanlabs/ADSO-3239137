<?php

namespace App\Infrastructure\Persistence;

use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;

/** Q10. The password hash is read only to verify a login and is never selected for any listing. */
final readonly class DbUserRepository implements UserRepository
{
    public function __construct(private ConnectionInterface $connection)
    {
    }

    public function findByUsername(string $username): ?User
    {
        $row = $this->table()->where('username', $username)->first(['id', 'username', 'password_hash', 'role']);

        return $row === null ? null : User::restore($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function usernameExists(string $username): bool
    {
        return $this->table()->where('username', $username)->exists();
    }

    public function add(User $user): void
    {
        try {
            // A savepoint keeps the caller's transaction usable after a unique violation.
            $this->connection->transaction(fn () => $this->table()->insert([
                'id' => $user->id,
                'username' => $user->username,
                'password_hash' => $user->passwordHash,
                'role' => $user->role->value,
            ]));
        } catch (QueryException $error) {
            if (SqlValues::isUniqueViolation($error)) {
                throw UsernameTaken::create($user->username);
            }
            throw $error;
        }
    }

    private function table(): Builder
    {
        return $this->connection->table('laptops.user');
    }
}
