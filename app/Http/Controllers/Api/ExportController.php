<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Phase;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly CsvExportService $csvExportService) {}

    public function claims(): StreamedResponse
    {
        return $this->csvExportService->claims();
    }

    public function students(): StreamedResponse
    {
        return $this->csvExportService->students();
    }

    public function institutions(): StreamedResponse
    {
        return $this->csvExportService->institutions();
    }

    public function auditLog(): StreamedResponse
    {
        return $this->csvExportService->auditLog();
    }

    public function institutionLeaderboard(Request $request): StreamedResponse
    {
        $phase = Phase::findOrFail($request->integer('phase_id'));

        return $this->csvExportService->institutionLeaderboard($phase);
    }

    public function individualLeaderboard(Request $request): StreamedResponse
    {
        $phase = Phase::findOrFail($request->integer('phase_id'));

        return $this->csvExportService->individualLeaderboard($phase);
    }
}
