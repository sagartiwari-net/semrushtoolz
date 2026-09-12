<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminEarningsReportService;
use Illuminate\Http\Request;

class EarningsReportController extends Controller
{
    public function __construct(
        protected AdminEarningsReportService $earnings,
    ) {}

    public function index(Request $request)
    {
        $data = $this->earnings->report($request);

        return view('admin.earnings.index', $data);
    }

    public function export(Request $request)
    {
        return $this->earnings->exportCsv($request);
    }
}
