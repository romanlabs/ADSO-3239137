<?php

namespace Tests\Fakes;

use App\Application\Ports\IssuedToken;
use App\Application\Ports\TokenIssuer;
use App\Domain\User;

final class FakeTokenIssuer implements TokenIssuer
{
    public function issue(User $user): IssuedToken
    {
        return new IssuedToken("token-for:{$user->id}:{$user->role->value}");
    }
}
