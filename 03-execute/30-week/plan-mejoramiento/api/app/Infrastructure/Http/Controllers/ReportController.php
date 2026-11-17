<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\MostRequestedReportService;
use App\Infrastructure\Http\Presenters;
use App\Infrastructure\Http\RequestInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-10: GET /reports/most-requested?from=YYYY-MM-DD&to=YYYY-MM-DD, admin only. */
final readonly class ReportController
{
    public function __construct(private MostRequestedReportService $reports)
    {
    }

    public function mostRequested(Request $request): JsonResponse
    {
        $report = $this->reports->report(RequestInput::query($request, 'from'), RequestInput::query($request, 'to'));

        return new JsonResponse(Presenters::report($report), 200, [], JSON_UNESCAPED_UNICODE);
    }
}
