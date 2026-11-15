<?php

namespace App\Application\Ports;

final readonly class IssuedToken
{
    public const LIFETIME_SECONDS = 28800;

    public function __construct(public string $accessToken, public int $expiresIn = self::LIFETIME_SECONDS)
    {
    }
}
