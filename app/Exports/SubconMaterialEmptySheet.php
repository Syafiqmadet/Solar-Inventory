<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SubconMaterialEmptySheet implements WithTitle, WithEvents
{
    public function title(): string { return 'No Data'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $e->sheet->getDelegate()->setCellValue('A1', 'No material records found for this project.');
        }];
    }
}
