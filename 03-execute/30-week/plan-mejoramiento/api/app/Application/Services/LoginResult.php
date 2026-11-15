<?php

namespace App\Application\Services;

use App\Application\Ports\IssuedToken;
use App\Domain\Role;

final readonly class LoginResult
{
    public function __construct(public IssuedToken $token, public Role $role)
    {
    }
}
