<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\CreateAssistantService;
use App\Infrastructure\Http\Presenters;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-02: POST /users creates an assistant; the answer never carries the password or its hash. */
final readonly class UserController
{
    public function __construct(private CreateAssistantService $assistants)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $input = new RequestInput($request);
        $username = $input->string('username');
        $password = $input->string('password');
        $role = $input->string('role');
        $input->validate();

        return new JsonResponse(Presenters::user($this->assistants->create($username, $password, $role)), 201);
    }
}
