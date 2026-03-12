<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContainerReportExport implements WithMultipleSheets
{
    public function __construct(private ?int $projectId = null) {}

    public function sheets(): array
    {
        return [
            new ContainerSummarySheet(null,null,null,null,null,null,$this->projectId),
            new ContainerItemsSheet(null,null,null,null,null,null,$this->projectId),
        ];
    }
}
