<?php

namespace App\Application\Ports;

use App\Domain\User;

interface TokenIssuer
{
    /** Issues a signed token carrying only the user id and role (contract §4). */
    public function issue(User $user): IssuedToken;
}
