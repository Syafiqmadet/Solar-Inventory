<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryExport implements WithMultipleSheets
{
    public function __construct(
        private ?string $search   = null,
        private ?string $category = null,
        private ?string $stock    = null,
        private ?int    $projectId = null,
    ) {}

    public function sheets(): array
    {
        return [
            new InventoryItemsSheet($this->search, $this->category, $this->stock, $this->projectId),
            new InventoryIsolatedSheet($this->projectId),
        ];
    }
}
