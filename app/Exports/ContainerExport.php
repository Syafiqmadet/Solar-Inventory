<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ContainerExport implements WithMultipleSheets
{
    public function __construct(
        private ?string $search    = null,
        private ?string $status    = null,
        private ?string $batch     = null,
        private ?string $itemName  = null,
        private ?string $dateFrom  = null,
        private ?string $dateTo    = null,
        private ?int    $projectId = null,
    ) {}

    public function sheets(): array
    {
        return [
            new ContainerSummarySheet(
                $this->search, $this->status, $this->batch,
                $this->itemName, $this->dateFrom, $this->dateTo, $this->projectId
            ),
            new ContainerItemsSheet(
                $this->search, $this->status, $this->batch,
                $this->itemName, $this->dateFrom, $this->dateTo, $this->projectId
            ),
        ];
    }
}
