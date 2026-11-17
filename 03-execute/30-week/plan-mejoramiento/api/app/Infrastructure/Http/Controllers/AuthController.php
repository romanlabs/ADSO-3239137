<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\LoginService;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-01: POST /auth/login. */
final readonly class AuthController
{
    public function __construct(private LoginService $login)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $input = new RequestInput($request);
        $username = $input->string('username');
        $password = $input->string('password');
        $input->validate();

        $result = $this->login->login($username, $password);

        return new JsonResponse([
            'access_token' => $result->token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $result->token->expiresIn,
            'role' => $result->role->value,
        ]);
    }
}
