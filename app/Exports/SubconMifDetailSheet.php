<?php
namespace App\Exports;

use App\Models\Subcon;
use App\Models\SubconMif;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SubconMifDetailSheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_RED    = 'DC3545';
    const C_LIGHT  = 'F8F9FA';

    public function __construct(private Subcon $subcon, private SubconMif $mif) {}

    public function title(): string
    {
        return substr($this->mif->mif_number, 0, 31); // Excel sheet name max 31 chars
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws    = $e->sheet->getDelegate();
            $items = $this->mif->items;
            $ws->setShowGridlines(false);

            // ── Header block ──
            $headerData = [
                ['MIF Number',  $this->mif->mif_number,                    'MIF Date',    $this->mif->date->format('d M Y')],
                ['Subcon',      $this->subcon->name,                       'Issued By',   $this->mif->issuedBy?->name ?? '—'],
                ['Zone',        $this->subcon->zone?->name ?? '—',         'Notes',       $this->mif->notes ?? '—'],
            ];

            $ws->mergeCells('A1:H1');
            $ws->getRowDimension(1)->setRowHeight(40);
            $ws->setCellValue('A1', '📤  MATERIAL ISSUE FORM — ' . $this->mif->mif_number);
            $ws->getStyle('A1:H1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>15,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            $row = 2;
            foreach ($headerData as $line) {
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->setCellValue('A'.$row, $line[0]);
                $ws->mergeCells('B'.$row.':C'.$row);
                $ws->setCellValue('B'.$row, $line[1]);
                $ws->setCellValue('E'.$row, $line[2]);
                $ws->mergeCells('F'.$row.':H'.$row);
                $ws->setCellValue('F'.$row, $line[3]);

                $ws->getStyle('A'.$row)->applyFromArray(['font'=>['bold'=>true,'name'=>'Arial','size'=>10]]);
                $ws->getStyle('E'.$row)->applyFromArray(['font'=>['bold'=>true,'name'=>'Arial','size'=>10]]);
                foreach (['B'.$row,'F'.$row] as $c) {
                    $ws->getStyle($c)->applyFromArray([
                        'font' => ['name'=>'Arial','size'=>10],
                        'fill' => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F5F5F5']],
                    ]);
                }
                $row++;
            }

            // Spacer
            $ws->getRowDimension($row)->setRowHeight(8);
            $row++;

            // ── Table Header ──
            $cols = [
                'A'=>['#',5], 'B'=>['Title',24], 'C'=>['Item Name',28], 'D'=>['Part No.',16],
                'E'=>['Qty',9], 'F'=>['Unit',10], 'G'=>['Issued By',18], 'H'=>['Remarks',28],
            ];
            $ws->getRowDimension($row)->setRowHeight(26);
            foreach ($cols as $col => [$label,$width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.$row, $label);
                $ws->getStyle($col.$row)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }
            $headerRow = $row;
            $row++;

            // ── Data Rows ──
            foreach ($items as $seq => $item) {
                $bg = $seq % 2 === 0 ? self::C_LIGHT : self::C_WHITE;
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->setCellValue('A'.$row, $seq + 1);
                $ws->setCellValue('B'.$row, $item->title ?? '—');
                $ws->setCellValue('C'.$row, $item->item_name);
                $ws->setCellValue('D'.$row, $item->part_number ?? '—');
                $ws->setCellValue('E'.$row, $item->quantity);
                $ws->setCellValue('F'.$row, $item->unit ?? '—');
                $ws->setCellValue('G'.$row, $this->mif->issuedBy?->name ?? '—');
                $ws->setCellValue('H'.$row, $item->remarks ?? '—');

                $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'font'      => ['size'=>10,'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'E0E0E0']]],
                ]);
                foreach (['A','E','F'] as $c) {
                    $ws->getStyle($c.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $ws->getStyle('B'.$row)->getFont()->setBold(true);
                $row++;
            }

            // ── Total Row ──
            $ws->getRowDimension($row)->setRowHeight(24);
            $ws->mergeCells('A'.$row.':D'.$row);
            $ws->setCellValue('A'.$row, 'TOTAL — ' . $items->count() . ' line items');
            $ws->setCellValue('E'.$row, $items->sum('quantity'));
            $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                'font'      => ['bold'=>true,'size'=>11,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
            ]);

            $ws->freezePane('A'.($headerRow + 1));
        }];
    }
}
