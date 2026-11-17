<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\LendLaptopService;
use App\Application\Services\ListLoansService;
use App\Application\Services\LoanView;
use App\Application\Services\OverdueService;
use App\Application\Services\ReturnItemService;
use App\Infrastructure\Http\Middleware\Authenticate;
use App\Infrastructure\Http\Presenters;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-06 to HU-09: lend, list, read, return one item and list overdue items. */
final readonly class LoanController
{
    public function __construct(
        private LendLaptopService $lend,
        private ListLoansService $loans,
        private ReturnItemService $returns,
        private OverdueService $overdue,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $input = new RequestInput($request);
        $studentId = $input->string('student_id');
        $laptopIds = [];
        foreach ($input->list('items') as $index => $item) {
            $laptopId = is_array($item) ? ($item['laptop_id'] ?? null) : null;
            if (! is_string($laptopId)) {
                $input->fail("items[{$index}].laptop_id", 'obligatorio, texto');

                continue;
            }
            $laptopIds[] = $laptopId;
        }
        $input->validate();

        $loan = $this->lend->lend($studentId, $laptopIds, (string) $request->attributes->get(Authenticate::USER_ID));

        return self::json(Presenters::loan($this->loans->get($loan->id)), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->loans->list(
            RequestInput::query($request, 'from'),
            RequestInput::query($request, 'to'),
            RequestInput::query($request, 'status'),
            RequestInput::query($request, 'student_id'),
            RequestInput::page($request),
        );

        return self::json(Presenters::page($page, fn (LoanView $view) => Presenters::loan($view)));
    }

    public function show(string $id): JsonResponse
    {
        return self::json(Presenters::loan($this->loans->get($id)));
    }

    public function returnItem(string $loanId, string $itemId): JsonResponse
    {
        return self::json(Presenters::item($this->returns->returnItem($loanId, $itemId)));
    }

    public function overdue(): JsonResponse
    {
        return self::json(['items' => array_map(Presenters::overdue(...), $this->overdue->list())]);
    }

    /** @param array<string, mixed> $body */
    private static function json(array $body, int $status = 200): JsonResponse
    {
        return new JsonResponse($body, $status, [], JSON_UNESCAPED_UNICODE);
    }
}
