<?php
namespace App\Exports;

use App\Models\Subcon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SubconMifExport implements WithMultipleSheets
{
    public function __construct(
        private Subcon $subcon,
        private $mifs,
    ) {}

    public function sheets(): array
    {
        $sheets = [new SubconMifSummarySheet($this->subcon, $this->mifs)];
        foreach ($this->mifs as $mif) {
            $sheets[] = new SubconMifDetailSheet($this->subcon, $mif);
        }
        return $sheets;
    }
}
