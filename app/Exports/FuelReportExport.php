<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FuelReportExport implements WithMultipleSheets
{
    public function __construct(private ?int $projectId = null) {}

    public function sheets(): array
    {
        return [
            new SummarySheet($this->projectId),
            new FuelUsageSheet('petrol', $this->projectId),
            new FuelUsageSheet('diesel', $this->projectId),
            new FuelPurchasesSheet($this->projectId),
        ];
    }
}
