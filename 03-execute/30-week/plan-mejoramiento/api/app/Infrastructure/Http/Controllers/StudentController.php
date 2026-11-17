<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\CreateStudentService;
use App\Application\Services\SearchStudentsService;
use App\Infrastructure\Http\Presenters;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-05: list, read and create students. */
final readonly class StudentController
{
    public function __construct(
        private SearchStudentsService $search,
        private CreateStudentService $create,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->search->search(RequestInput::query($request, 'q'), RequestInput::page($request));

        return new JsonResponse(Presenters::page($page, Presenters::student(...)), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function show(string $id): JsonResponse
    {
        return new JsonResponse(Presenters::studentDetail($this->search->get($id)), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request): JsonResponse
    {
        $input = new RequestInput($request);
        $fullName = $input->string('full_name');
        $documentNumber = $input->string('document_number');
        $input->validate();

        return new JsonResponse(Presenters::student($this->create->create($fullName, $documentNumber)), 201, [], JSON_UNESCAPED_UNICODE);
    }
}
