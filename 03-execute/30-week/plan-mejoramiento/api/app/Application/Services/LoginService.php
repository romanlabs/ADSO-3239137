<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\InvalidCredentials;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\User;

/**
 * HU-01. Every failure ends in the same InvalidCredentials error so the caller never learns
 * whether the username or the password was wrong (CA-01.2).
 */
final readonly class LoginService
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenIssuer $tokens,
    ) {
    }

    public function login(string $username, string $password): LoginResult
    {
        try {
            $normalized = User::normalizeUsername($username);
        } catch (ValidationFailed) {
            throw InvalidCredentials::create();
        }

        $user = $this->users->findByUsername($normalized);

        if ($user === null || $password === '' || ! $this->hasher->verify($password, $user->passwordHash)) {
            throw InvalidCredentials::create();
        }

        return new LoginResult($this->tokens->issue($user), $user->role);
    }
}
