<?php
namespace App\Exports;

use App\Models\Subcon;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SubconMifSummarySheet implements WithTitle, WithEvents, ShouldAutoSize
{
    const C_DARK   = '1A1A2E';
    const C_ORANGE = 'FF6B35';
    const C_WHITE  = 'FFFFFF';
    const C_HDR    = '2D3748';
    const C_GREEN  = '28A745';
    const C_RED    = 'DC3545';
    const C_LIGHT  = 'F8F9FA';
    const C_BLUE   = '0D6EFD';

    public function __construct(private Subcon $subcon, private $mifs) {}

    public function title(): string { return 'Summary'; }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $e) {
            $ws = $e->sheet->getDelegate();
            $ws->setShowGridlines(false);

            // ── Title ──
            $ws->mergeCells('A1:H1');
            $ws->getRowDimension(1)->setRowHeight(44);
            $ws->setCellValue('A1', '📋  MATERIAL ISSUE FORM (MIF) — ' . strtoupper($this->subcon->name));
            $ws->getStyle('A1:H1')->applyFromArray([
                'font'      => ['bold'=>true,'size'=>16,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_DARK]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
            ]);

            // ── Subcon Info ──
            $ws->getRowDimension(2)->setRowHeight(22);
            $info = [
                'A2' => 'Zone: ' . ($this->subcon->zone->name ?? '—'),
                'C2' => 'Status: ' . ucfirst($this->subcon->status),
                'E2' => 'Period: ' . ($this->subcon->start_date?->format('d M Y') ?? '—') . ' → ' . ($this->subcon->end_date?->format('d M Y') ?? '—'),
                'G2' => 'Exported: ' . now()->format('d M Y H:i'),
            ];
            foreach ($info as $cell => $val) {
                $ws->setCellValue($cell, $val);
                $ws->getStyle($cell)->applyFromArray([
                    'font' => ['size'=>10,'name'=>'Arial','color'=>['rgb'=>'555555']],
                    'fill' => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'F0F0F0']],
                ]);
            }

            // ── KPI Row ──
            $totalForms = $this->mifs->count();
            $totalQty   = $this->mifs->sum(fn($m) => $m->items->sum('quantity'));
            $totalLines = $this->mifs->sum(fn($m) => $m->items->count());

            $ws->getRowDimension(3)->setRowHeight(36);
            $kpis = [
                ['A3:B3', "📋 Total Forms\n{$totalForms}", 'E8EAF6', self::C_DARK],
                ['C3:D3', "📦 Total Qty Issued\n{$totalQty}", 'FDECEA', self::C_RED],
                ['E3:F3', "📝 Total Line Items\n{$totalLines}", 'E8F5E9', self::C_GREEN],
            ];
            foreach ($kpis as [$rng, $txt, $bg, $fc]) {
                $ws->mergeCells($rng);
                $ws->setCellValue(explode(':', $rng)[0], $txt);
                $ws->getStyle($rng)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>13,'color'=>['rgb'=>$fc],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            $ws->getRowDimension(4)->setRowHeight(6);

            // ── Table Header ──
            $cols = ['A'=>['MIF Number',16],'B'=>['Date',13],'C'=>['Title',28],'D'=>['Item Name',28],
                     'E'=>['Part No.',15],'F'=>['Qty',8],'G'=>['Unit',10],'H'=>['Remarks',30]];
            $ws->getRowDimension(5)->setRowHeight(26);
            foreach ($cols as $col => [$label,$width]) {
                $ws->getColumnDimension($col)->setWidth($width);
                $ws->setCellValue($col.'5', $label);
                $ws->getStyle($col.'5')->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'color'=>['rgb'=>self::C_WHITE],'name'=>'Arial'],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_ORANGE]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
            }

            // ── Data Rows ──
            $row = 6;
            foreach ($this->mifs as $mif) {
                $itemCount = $mif->items->count();
                $firstRow  = $row;
                foreach ($mif->items as $seq => $item) {
                    $bg = $row % 2 === 0 ? self::C_LIGHT : self::C_WHITE;
                    $ws->getRowDimension($row)->setRowHeight(20);

                    if ($seq === 0) {
                        $ws->setCellValue('A'.$row, $mif->mif_number);
                        $ws->setCellValue('B'.$row, $mif->date->format('d M Y'));
                        $ws->getStyle('A'.$row)->getFont()->setBold(true)->setColor(
                            (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB(self::C_BLUE)
                        );
                    }

                    $ws->setCellValue('C'.$row, $item->title ?? '—');
                    $ws->setCellValue('D'.$row, $item->item_name);
                    $ws->setCellValue('E'.$row, $item->part_number ?? '—');
                    $ws->setCellValue('F'.$row, $item->quantity);
                    $ws->setCellValue('G'.$row, $item->unit ?? '—');
                    $ws->setCellValue('H'.$row, $item->remarks ?? '—');

                    $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                        'font'      => ['size'=>10,'name'=>'Arial'],
                        'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT,'vertical'=>Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'E0E0E0']]],
                    ]);
                    $ws->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('G'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $row++;
                }

                // Subtotal per MIF
                $ws->getRowDimension($row)->setRowHeight(20);
                $ws->mergeCells('A'.$row.':E'.$row);
                $ws->setCellValue('A'.$row, 'Subtotal — '.$mif->mif_number.' ('.$itemCount.' lines)');
                $ws->setCellValue('F'.$row, $mif->items->sum('quantity'));
                $ws->getStyle('A'.$row.':H'.$row)->applyFromArray([
                    'font'      => ['bold'=>true,'size'=>10,'name'=>'Arial','color'=>['rgb'=>self::C_WHITE]],
                    'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>self::C_HDR]],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['rgb'=>'CCCCCC']]],
                ]);
                $row++;
            }

            if ($this->mifs->isEmpty()) {
                $ws->mergeCells('A6:H6');
                $ws->setCellValue('A6', 'No MIF records found.');
                $ws->getStyle('A6:H6')->applyFromArray([
                    'font'      => ['italic'=>true,'color'=>['rgb'=>'999999'],'name'=>'Arial'],
                    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $ws->freezePane('A6');
        }];
    }
}
