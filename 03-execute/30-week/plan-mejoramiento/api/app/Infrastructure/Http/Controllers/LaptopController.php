<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\CreateLaptopService;
use App\Application\Services\RetireLaptopService;
use App\Application\Services\SearchLaptopsService;
use App\Application\Services\UpdateLaptopService;
use App\Infrastructure\Http\Presenters;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** HU-03 and HU-04: search, read, create, replace and retire laptops. */
final readonly class LaptopController
{
    public function __construct(
        private SearchLaptopsService $search,
        private CreateLaptopService $create,
        private UpdateLaptopService $update,
        private RetireLaptopService $retire,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->search->search(
            RequestInput::query($request, 'q'),
            RequestInput::query($request, 'category_id'),
            RequestInput::page($request),
        );

        return self::json(Presenters::page($page, Presenters::laptop(...)));
    }

    public function show(string $id): JsonResponse
    {
        return self::json(Presenters::laptop($this->search->get($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $input = new RequestInput($request);
        $name = $input->string('name');
        $brand = $input->string('brand');
        $modelCode = $input->string('model_code');
        $categoryId = $input->string('category_id');
        $totalUnits = $input->integer('total_units');
        $input->validate();

        return self::json(Presenters::laptop($this->create->create($name, $brand, $modelCode, $categoryId, $totalUnits)), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $input = new RequestInput($request);
        $name = $input->string('name');
        $brand = $input->string('brand');
        $categoryId = $input->string('category_id');
        $totalUnits = $input->integer('total_units');
        $input->validate();

        return self::json(Presenters::laptop($this->update->update($id, $name, $brand, $categoryId, $totalUnits)));
    }

    public function destroy(string $id): Response
    {
        $this->retire->retire($id);

        return new Response(null, 204);
    }

    /** @param array<string, mixed> $body */
    private static function json(array $body, int $status = 200): JsonResponse
    {
        return new JsonResponse($body, $status, [], JSON_UNESCAPED_UNICODE);
    }
}
