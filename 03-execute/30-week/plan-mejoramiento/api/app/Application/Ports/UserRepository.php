<?php

namespace App\Application\Ports;

use App\Domain\User;

interface UserRepository
{
    /** Expects an already normalized username (Q10). */
    public function findByUsername(string $username): ?User;

    public function usernameExists(string $username): bool;

    /** Throws UsernameTaken if the unique index rejects the row. */
    public function add(User $user): void;
}
