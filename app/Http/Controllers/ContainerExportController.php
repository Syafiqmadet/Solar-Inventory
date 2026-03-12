<?php
namespace App\Http\Controllers;

use App\Exports\ContainerReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ContainerExportController extends Controller
{
    use HasProjectScope;

    public function export()
    {
        if (!$this->pid()) return back()->with('error', 'No project selected.');
        $filename = 'Container_Manifest_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new ContainerReportExport($this->pid()), $filename);
    }
}
