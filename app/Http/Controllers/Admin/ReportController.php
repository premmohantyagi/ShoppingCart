<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService)
    {
    }

    public function sales(Request $request): View
    {
        $filters = $request->only(['from', 'to']);
        $data = $this->reportService->salesReport($filters);

        return view('admin.reports.sales', compact('data', 'filters'));
    }

    public function products(Request $request): View
    {
        $filters = $request->only(['from', 'to']);
        $data = $this->reportService->productReport($filters);

        return view('admin.reports.products', compact('data', 'filters'));
    }

    public function vendors(Request $request): View
    {
        $filters = $request->only(['from', 'to']);
        $data = $this->reportService->vendorReport($filters);

        return view('admin.reports.vendors', compact('data', 'filters'));
    }

    public function stock(): View
    {
        $data = $this->reportService->stockReport();

        return view('admin.reports.stock', compact('data'));
    }

    public function exportSalesCsv(Request $request): Response
    {
        $filters = $request->only(['from', 'to']);
        $rows = $this->reportService->getSalesExportData($filters);

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($v) => '"' . str_replace('"', '""', $v ?? '') . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales-report-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }
}
