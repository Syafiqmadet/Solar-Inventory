<?php
namespace App\Exports;

use App\Models\Subcon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SubconMrfExport implements WithMultipleSheets
{
    public function __construct(
        private Subcon $subcon,
        private $mrfs,
    ) {}

    public function sheets(): array
    {
        $sheets = [new SubconMrfSummarySheet($this->subcon, $this->mrfs)];
        foreach ($this->mrfs as $mrf) {
            $sheets[] = new SubconMrfDetailSheet($this->subcon, $mrf);
        }
        return $sheets;
    }
}
