<?php
namespace App\Http\Controllers;

use App\Exports\FuelReportExport;
use Maatwebsite\Excel\Facades\Excel;

class FuelExportController extends Controller
    use HasProjectScope;
    public function export()
    {
        $pid = session('current_project_id');
        if (!$pid) return back()->with('error', 'No project selected.');
        $filename = 'Fuel_Report_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new FuelReportExport($pid), $filename);
    }
}
